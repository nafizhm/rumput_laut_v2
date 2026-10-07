<?php

namespace Tests\Audit;

use App\Models\CustomerPurchaseOrder;
use App\Models\Factory;
use App\Models\Farmer;
use App\Models\FarmerLedger;
use App\Models\FinishedBatch;
use App\Models\GoodsReceipt;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\MaterialBatch;
use App\Models\Menu;
use App\Models\ProductionRun;
use App\Models\QualityGrade;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseArea;
use App\Services\Finance\FinancialReportService;
use App\Services\Finance\JournalService;
use App\Services\Finance\OperationalAccountingService;
use App\Services\Sales\DeliveryNoteService;
use App\Services\Sales\InvoicePaymentService;
use App\Services\Sales\InvoiceService;
use App\Services\Transaction\FarmerPaymentService;
use App\Services\Transaction\GoodsReceiptService;
use App\Services\Transaction\HarvestTransactionService;
use App\Services\Transaction\LoanService;
use App\Services\Transaction\ProductionService;
use App\Services\Transaction\SalesService;
use App\Services\Transaction\TransactionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Business acceptance and regression tests. Run against SQLite memory only.
 */
class BusinessFlowAuditTest extends TestCase
{
    private Farmer $farmer;

    private Factory $factory;

    private Inventory $material;

    private Inventory $legacy;

    private WarehouseArea $area;

    private QualityGrade $grade;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Audit requires an isolated SQLite memory database.');
        }
        Carbon::setTestNow(Carbon::parse('2026-10-07 12:00:00', 'Asia/Jakarta'));
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_01_01_130309_create_permission_tables.php',
            '2026_01_01_130425_create_menus_table.php',
            '2026_04_26_062312_create_farmers_table.php',
            '2026_04_26_062518_create_loans_table.php',
            '2026_04_26_062526_create_harvest_transactions_table.php',
            '2026_04_26_062535_create_farmer_ledgers_table.php',
            '2026_04_26_111858_create_expense_categories_table.php',
            '2026_04_26_111907_create_expenses_table.php',
            '2026_04_26_153147_create_inventories_table.php',
            '2026_04_26_153352_create_factories_table.php',
            '2026_04_26_153448_create_sales_transactions_table.php',
            '2026_04_26_153534_create_stock_movements_table.php',
            '2026_05_02_000002_add_sale_deduction_fields_to_harvest_transactions_table.php',
            '2026_05_02_000003_add_payment_fields_to_sales_transactions_table.php',
            '2026_07_13_141853_create_farmer_payments_table.php',
            '2026_07_13_142525_create_incomes_table.php',
            '2026_07_14_000002_create_transactions_table.php',
            '2026_10_06_000001_create_customer_purchase_orders_table.php',
            '2026_10_06_000003_add_factory_and_file_to_customer_purchase_orders.php',
            '2026_10_06_000004_create_delivery_notes_table.php',
            '2026_10_06_000006_create_invoices_table.php',
            '2026_10_06_000008_add_po_reference_to_invoices.php',
            '2026_10_06_225852_create_invoice_payments_table.php',
            '2026_10_07_000002_create_chart_of_accounts_table.php',
            '2026_10_07_000005_link_invoices_to_sales.php',
            '2026_10_07_000006_add_delivery_confirmation_stock_flow.php',
            '2026_10_07_000007_create_quality_grades_table.php',
            '2026_10_07_000009_create_goods_receiving_flow.php',
            '2026_10_07_000012_create_accounting_journals.php',
            '2026_10_07_000015_connect_production_and_operational_accounting.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Equivalent columns for the existing MySQL-only May ALTER migration.
        Schema::table('harvest_transactions', fn (Blueprint $t) => $t->text('notes')->nullable());
        Schema::table('sales_transactions', function (Blueprint $t) {
            $t->integer('sold_sacks')->nullable();
            $t->string('deduction_type')->default('none');
            $t->decimal('deduction_value', 12, 2)->default(0);
            $t->decimal('deduction_weight_kg', 12, 2)->default(0);
        });
        $this->user = User::create(['name' => 'Audit', 'email' => 'audit@example.test', 'password' => 'password']);
        $this->actingAs($this->user);
        $this->farmer = Farmer::create(['name' => 'Petani Audit', 'current_debt' => 0]);
        $this->factory = Factory::create(['name' => 'Pabrik Audit', 'address' => 'Alamat Audit']);
        $this->material = Inventory::create(['item_name' => 'Bahan Masuk', 'current_stock_kg' => 0, 'current_sack_count' => 0]);
        $this->material->stock_stage = 'material';
        $this->material->save();
        // This is an independent existing-stock fixture, NOT production output.
        $this->legacy = Inventory::create(['item_name' => 'Gudang Kering', 'current_stock_kg' => 1000, 'current_sack_count' => 40]);
        $this->grade = QualityGrade::create(['code' => 'Q01', 'name' => 'Grade A', 'is_active' => true]);
        $this->area = WarehouseArea::create(['code' => 'BM-A', 'name' => 'Area A', 'inventory_id' => $this->material->id, 'usage' => 'receiving', 'is_active' => true]);
        $this->area->grades()->attach($this->grade);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function loan(int $amount = 1000000)
    {
        return app(LoanService::class)->createLoan(['farmer_id' => $this->farmer->id, 'loan_date' => '2026-10-01', 'amount' => $amount]);
    }

    private function harvestData(array $changes = []): array
    {
        return array_replace(['farmer_id' => $this->farmer->id, 'harvest_date' => '2026-10-02', 'location' => 'Lokasi Audit',
            'total_weight' => 100, 'sack_count' => 4, 'extra_per_sack' => 1, 'warehouse_weight' => 104,
            'price_per_kg' => 10000, 'cash_payout' => 700000], $changes);
    }

    private function receipt(HarvestTransaction $harvest): GoodsReceipt
    {
        return app(GoodsReceiptService::class)->save(['farmer_id' => $this->farmer->id, 'harvest_transaction_id' => $harvest->id,
            'received_date' => '2026-10-03', 'inventory_id' => $this->material->id, 'note_weight_kg' => 104,
            'lines' => [['quality_grade_id' => $this->grade->id, 'warehouse_area_id' => $this->area->id, 'actual_weight_kg' => 102, 'sack_count' => 4]]]);
    }

    private function sale(string $mode = 'delivery_note'): SalesTransaction
    {
        return app(SalesService::class)->createSale(['factory_id' => $this->factory->id, 'inventory_id' => $this->legacy->id,
            'sale_date' => '2026-10-04', 'sold_weight_kg' => 100, 'sold_sacks' => 4, 'price_per_kg' => 10000, 'stock_mode' => $mode]);
    }

    private function po(): CustomerPurchaseOrder
    {
        return CustomerPurchaseOrder::create(['po_number' => 'PO-AUDIT', 'po_date' => '2026-10-04', 'factory_id' => $this->factory->id,
            'customer_name' => $this->factory->name, 'customer_address' => 'Alamat Audit', 'item_name' => 'Rumput Laut', 'quantity_kg' => 100, 'price_per_kg' => 10000]);
    }

    private function invoice(SalesTransaction $sale, ?CustomerPurchaseOrder $po = null): Invoice
    {
        return app(InvoiceService::class)->store(['sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => ($po ?? $this->po())->id,
            'number' => 'INV-AUDIT', 'invoice_date' => '2026-10-04', 'due_date' => '2026-11-04',
            'customer_name' => 'Pabrik Audit', 'customer_address' => 'Alamat Audit', 'item_name' => 'Rumput Laut', 'quantity_kg' => 100, 'price_per_kg' => 10000]);
    }

    private function cashIn(string $category, string $start = '2026-10-01', string $end = '2026-10-31'): int
    {
        $rows = $this->getJson(route('cash-flow.data', ['start_date' => $start, 'end_date' => $end]))->assertOk()->json();

        return (int) collect($rows)->where('category', $category)->sum('amount');
    }

    public function test_loan_harvest_and_confirmed_receipt_reconcile_without_double_stock(): void
    {
        $this->loan();
        $harvest = app(HarvestTransactionService::class)->createHarvest($this->harvestData());
        $this->assertSame(700000, (int) $this->farmer->refresh()->current_debt);
        $this->assertSame(300000, (int) $harvest->debt_deduction);
        $this->assertSame(0, StockMovement::count());
        $receipt = $this->receipt($harvest);
        $this->assertSame(0.0, (float) $this->material->refresh()->current_stock_kg);
        app(GoodsReceiptService::class)->confirm($receipt, $this->user);
        $this->assertSame(102.0, (float) $this->material->refresh()->current_stock_kg);
        $this->assertSame(4, (int) $this->material->current_sack_count);
        $this->assertSame($harvest->id, $receipt->harvest_transaction_id);
        $this->assertSame($receipt->id, StockMovement::firstOrFail()->goods_receipt_id);
        $this->assertSame(2.0, (float) StockMovement::firstOrFail()->discrepancy_kg);
        $this->assertSame($this->farmer->id, MaterialBatch::firstOrFail()->line->receipt->farmer_id);
        $this->assertTrue((bool) $harvest->refresh()->is_received);
        $this->assertSame(2, FarmerLedger::count());
    }

    public function test_existing_stock_sale_shipment_invoice_and_installments_reconcile(): void
    {
        $sale = $this->sale();
        $po = $this->po();
        $note = app(DeliveryNoteService::class)->store(['sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => $po->id,
            'number' => 'SJ-AUDIT', 'delivery_date' => '2026-10-04', 'destination_address' => 'Alamat Audit', 'origin' => 'Gudang', 'quantity_kg' => 100, 'sack_count' => 4]);
        $this->assertSame(0, StockMovement::count());
        app(DeliveryNoteService::class)->dispatch($note, '2026-10-04', $this->user);
        $invoice = $this->invoice($sale, $po);
        $this->assertSame(900.0, (float) $this->legacy->refresh()->current_stock_kg);
        $this->assertSame(1, StockMovement::count());
        $this->assertSame($sale->id, $invoice->sales_transaction_id);
        $this->assertSame($po->id, $invoice->customer_purchase_order_id);
        $this->assertSame($note->id, $invoice->delivery_note_id);
        $this->assertSame(1000000.0, $invoice->total_amount);
        app(InvoicePaymentService::class)->recordPayment(['invoice_id' => $invoice->id, 'payment_date' => '2026-10-05', 'amount' => 300000]);
        $this->assertSame('partial', $invoice->refresh()->payment_status);
        $this->assertSame(700000, $sale->refresh()->remaining_amount);
        app(InvoicePaymentService::class)->recordPayment(['invoice_id' => $invoice->id, 'payment_date' => '2026-10-06', 'amount' => 700000]);
        $this->assertSame('paid', $invoice->refresh()->payment_status);
        $this->assertSame('paid', $sale->refresh()->payment_status);
        $this->assertSame(1, StockMovement::count());
    }

    public function test_farmer_cash_repayment_and_reversal_reconcile(): void
    {
        $this->loan();
        $payment = app(FarmerPaymentService::class)->createPayment(['farmer_id' => $this->farmer->id, 'payment_date' => '2026-10-03', 'amount' => 200000]);
        $this->assertSame(800000, (int) $this->farmer->refresh()->current_debt);
        app(FarmerPaymentService::class)->deletePayment($payment);
        $this->assertSame(1000000, (int) $this->farmer->refresh()->current_debt);
        $this->assertSame(1, FarmerLedger::count());
    }

    public function test_unpaid_sale_must_not_be_counted_as_cash_received(): void
    {
        $this->invoice($this->sale());
        $this->assertSame(0, $this->cashIn('Penjualan Rumput Laut'), 'Unpaid invoice is a receivable, not cash.');
    }

    public function test_cash_flow_uses_each_installment_date_and_amount(): void
    {
        $invoice = $this->invoice($this->sale());
        app(InvoicePaymentService::class)->recordPayment(['invoice_id' => $invoice->id, 'payment_date' => '2026-11-05', 'amount' => 300000]);
        $this->assertSame(300000, $this->cashIn('Penjualan Rumput Laut', '2026-11-01', '2026-11-30'));
    }

    public function test_farmer_cash_repayment_appears_in_cash_flow(): void
    {
        $this->loan();
        app(FarmerPaymentService::class)->createPayment(['farmer_id' => $this->farmer->id, 'payment_date' => '2026-10-03', 'amount' => 200000]);
        $rows = $this->getJson(route('cash-flow.data'))->assertOk()->json();
        $this->assertSame(200000, (int) collect($rows)->where('type', 'Cash In')->sum('amount'));
    }

    public function test_operational_transactions_have_linked_accounting_entries(): void
    {
        $this->loan();
        $harvest = app(HarvestTransactionService::class)->createHarvest($this->harvestData());
        app(GoodsReceiptService::class)->confirm($this->receipt($harvest), $this->user);
        $invoice = $this->invoice($this->sale('immediate'));
        app(InvoicePaymentService::class)->recordPayment(['invoice_id' => $invoice->id, 'payment_date' => '2026-10-05', 'amount' => 300000]);
        $this->assertGreaterThan(0, JournalEntry::whereNotNull('source_reference')->count(), 'Operational transactions never reach the accounting ledger.');
    }

    public function test_harvest_debt_deduction_cannot_exceed_outstanding_debt(): void
    {
        $this->loan(100000);
        $this->postJson(route('manajemen-panen.store'), $this->harvestData())->assertUnprocessable();
        $this->assertSame(100000, (int) $this->farmer->refresh()->current_debt);
    }

    public function test_changing_loan_farmer_moves_the_balance_to_the_correct_farmer(): void
    {
        $loan = $this->loan();
        $other = Farmer::create(['name' => 'Petani Lain', 'current_debt' => 0]);
        $response = $this->putJson(route('manajemen-pinjaman.update', $loan), ['farmer_id' => $other->id, 'loan_date' => '2026-10-01', 'amount' => 1000000]);
        if ($response->status() === 422) {
            $this->assertSame($this->farmer->id, $loan->refresh()->farmer_id);
            $this->assertSame(1000000, (int) $this->farmer->refresh()->current_debt);
            $this->assertSame(0, (int) $other->refresh()->current_debt);

            return;
        }
        $response->assertOk();
        $this->assertSame(0, (int) $this->farmer->refresh()->current_debt);
        $this->assertSame(1000000, (int) $other->refresh()->current_debt);
    }

    public function test_changing_harvest_farmer_reconciles_both_debt_balances(): void
    {
        $this->loan();
        $harvest = app(HarvestTransactionService::class)->createHarvest($this->harvestData());
        $other = Farmer::create(['name' => 'Petani Lain', 'current_debt' => 1000000]);
        $response = $this->putJson(route('manajemen-panen.update', $harvest), $this->harvestData(['farmer_id' => $other->id]));
        if ($response->status() === 422) {
            $this->assertSame($this->farmer->id, $harvest->refresh()->farmer_id);
            $this->assertSame(700000, (int) $this->farmer->refresh()->current_debt);
            $this->assertSame(1000000, (int) $other->refresh()->current_debt);

            return;
        }
        $response->assertOk();
        $this->assertSame(1000000, (int) $this->farmer->refresh()->current_debt);
        $this->assertSame(700000, (int) $other->refresh()->current_debt);
    }

    public function test_deleting_a_repaid_loan_cannot_create_negative_debt(): void
    {
        $loan = $this->loan();
        app(FarmerPaymentService::class)->createPayment(['farmer_id' => $this->farmer->id, 'payment_date' => '2026-10-03', 'amount' => 1000000]);
        $this->deleteJson(route('manajemen-pinjaman.destroy', $loan))->assertUnprocessable();
        $this->assertSame(0, (int) $this->farmer->refresh()->current_debt);
    }

    public function test_dashboard_includes_expenses_entered_through_the_current_finance_menu(): void
    {
        app(TransactionService::class)->create(['type' => 'expense', 'date' => '2026-10-04', 'amount' => 50000, 'description' => 'Biaya Audit']);
        $this->getJson(route('dashboard.stats'))->assertOk()->assertJsonPath('weeklyExpense.value', 50000);
    }

    public function test_immediate_sale_invoice_does_not_reference_another_sales_shipment(): void
    {
        $po = $this->po();
        $other = $this->sale();
        $note = app(DeliveryNoteService::class)->store(['sales_transaction_id' => $other->id, 'customer_purchase_order_id' => $po->id,
            'number' => 'SJ-OTHER', 'delivery_date' => '2026-10-04', 'destination_address' => 'Alamat Audit', 'origin' => 'Gudang', 'quantity_kg' => 100, 'sack_count' => 4]);
        app(DeliveryNoteService::class)->dispatch($note, '2026-10-04', $this->user);
        $invoice = $this->invoice($this->sale('immediate'), $po);
        $this->assertNull($invoice->delivery_note_id, 'Same PO does not make a different sale shipment belong to this invoice.');
    }

    public function test_receiving_seed_migration_creates_a_material_inventory_under_normal_model_guards(): void
    {
        $this->area->grades()->detach();
        $this->area->delete();
        $this->material->delete();
        Menu::create(['name' => 'Master', 'is_active' => true]);
        Menu::create(['name' => 'Manajemen Gudang', 'is_active' => true]);
        (require database_path('migrations/2026_10_07_000010_seed_goods_receiving_flow.php'))->up();
        $this->assertSame(1, Inventory::where('stock_stage', 'material')->count(), 'Calling the seeder directly from the migration must preserve stock_stage.');
        $this->assertSame('material', WarehouseArea::where('code', 'BM-A')->firstOrFail()->inventory->stock_stage);
    }

    private function production(MaterialBatch $batch, int $sacks = 4): ProductionRun
    {
        return app(ProductionService::class)->start(['started_date' => '2026-10-03', 'warehouse_area_id' => WarehouseArea::where('usage', 'processing')->firstOrFail()->id,
            'pic' => 'PIC Audit', 'work_type' => 'Blower dan jemur', 'processing_cost' => '50000',
            'inputs' => [['material_batch_id' => $batch->id, 'sack_count' => $sacks]]], $this->user);
    }

    private function packing(ProductionRun $run, array $changes = []): ProductionRun
    {
        $area = WarehouseArea::where('usage', 'finished')->firstOrFail();
        $area->grades()->syncWithoutDetaching([$this->grade->id]);

        return app(ProductionService::class)->pack($run, array_replace(['packed_date' => '2026-10-04', 'packing_cost' => '50000',
            'outputs' => [['warehouse_area_id' => $area->id, 'quality_grade_id' => $this->grade->id, 'weight_kg' => 80, 'package_count' => 4]]], $changes));
    }

    private function sourceBatch(): MaterialBatch
    {
        $this->loan();
        $harvest = app(HarvestTransactionService::class)->createHarvest($this->harvestData());
        app(GoodsReceiptService::class)->confirm($this->receipt($harvest), $this->user);

        return MaterialBatch::firstOrFail();
    }

    public function test_full_flow_from_zero_stock_with_real_production_partial_shipments_and_balanced_finance(): void
    {
        $this->legacy->update(['current_stock_kg' => 0, 'current_sack_count' => 0]);
        app(JournalService::class)->startZero('2026-10-01', $this->user);
        $batch = $this->sourceBatch();
        $run = $this->production($batch);
        $this->assertSame(0, (int) $batch->refresh()->remaining_sacks);
        $this->assertSame(0.0, (float) $this->material->refresh()->current_stock_kg);
        app(ProductionService::class)->complete($run, '2026-10-03');
        $run = $this->packing($run);
        $finished = $run->outputs()->firstOrFail();
        $this->assertSame(80.0, (float) $finished->weight_kg);
        $this->assertSame(110000000, (int) $finished->cost_cents);
        $this->assertSame($this->farmer->id, $finished->run->inputs->first()->batch->line->receipt->farmer_id);
        $sale = app(SalesService::class)->createSale(['factory_id' => $this->factory->id, 'inventory_id' => $finished->inventory_id, 'finished_batch_id' => $finished->id,
            'sale_date' => '2026-10-04', 'sold_weight_kg' => 80, 'sold_sacks' => 4, 'price_per_kg' => 20000, 'stock_mode' => 'delivery_note']);
        $po = $this->po();
        $invoice = $this->invoice($sale, $po);
        app(InvoicePaymentService::class)->recordPayment(['invoice_id' => $invoice->id, 'payment_date' => '2026-10-04', 'amount' => 300000]);
        $this->assertSame(30000000, $this->accountNet('21201', true));
        foreach ([['SJ-PART-1', 40, 2, '2026-10-05'], ['SJ-PART-2', 40, 2, '2026-10-06']] as [$number,$kg,$packs,$date]) {
            $note = app(DeliveryNoteService::class)->store(['number' => $number, 'delivery_date' => $date, 'sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => $po->id,
                'quantity_kg' => $kg, 'sack_count' => $packs, 'destination_address' => 'Alamat Audit', 'origin' => 'Gudang']);
            app(DeliveryNoteService::class)->dispatch($note, $date, $this->user);
        }
        app(InvoicePaymentService::class)->recordPayment(['invoice_id' => $invoice->id, 'payment_date' => '2026-10-07', 'amount' => 1300000]);
        $this->assertSame(0.0, (float) $finished->refresh()->remaining_weight_kg);
        $this->assertSame(0, (int) $finished->remaining_packages);
        $this->assertSame(0, (int) $finished->remaining_cost_cents);
        $this->assertSame(0.0, (float) $finished->inventory->current_stock_kg);
        $this->assertSame('paid', $sale->refresh()->payment_status);
        $this->assertCount(2, $invoice->refresh()->delivery_note_references);
        $this->assertSame(1600000, $this->cashIn('Penjualan Rumput Laut'));
        $this->assertSame(0, $this->accountNet('21201', true));
        $this->assertSame(0, $this->accountNet('11202'));
        $this->assertSame(0, $this->accountNet('11301'));
        $this->assertSame(0, $this->accountNet('11304'));
        $this->assertSame(0, $this->accountNet('11305'));
        $report = app(FinancialReportService::class)->month('2026-10');
        $this->assertSame(110000000, $report['totals']['cogs']);
        $this->assertSame(50000000, $report['net_profit']);
        $this->assertSame(0, $report['difference']);
        foreach (JournalEntry::where('status', 'posted')->with('lines')->get() as $entry) {
            $this->assertSame((int) $entry->lines->sum('debit_cents'), (int) $entry->lines->sum('credit_cents'));
        }
        $this->getJson(route('dashboard.stats'))->assertOk()->assertJsonPath('netProfit', 500000);
    }

    public function test_packing_small_cost_across_equal_outputs_never_creates_negative_batch_cost(): void
    {
        $run = $this->production($this->sourceBatch());
        app(ProductionService::class)->complete($run, '2026-10-03');
        $run->update(['material_cost_cents' => 0, 'processing_cost_cents' => 0]);
        $area = WarehouseArea::where('usage', 'finished')->firstOrFail();
        $area->grades()->syncWithoutDetaching([$this->grade->id]);
        $output = ['warehouse_area_id' => $area->id, 'quality_grade_id' => $this->grade->id, 'weight_kg' => 1, 'package_count' => 1];
        $this->packing($run, ['packing_cost' => '0.02', 'outputs' => array_fill(0, 4, $output)]);

        $this->assertSame(2, (int) $run->outputs()->sum('cost_cents'));
        $this->assertSame(0, $run->outputs()->where('cost_cents', '<', 0)->count());
        $this->assertSame(0, $run->outputs()->where('remaining_cost_cents', '<', 0)->count());
    }

    public function test_receipt_cost_rounding_does_not_leave_negative_cost_on_small_final_batch(): void
    {
        $harvest = app(HarvestTransactionService::class)->createHarvest($this->harvestData([
            'price_per_kg' => 0.01, 'cash_payout' => 1,
        ]));
        $lines = [];
        foreach ([16.66, 16.66, 16.66, 16.66, 16.66, 16.66, 0.04] as $weight) {
            $lines[] = ['quality_grade_id' => $this->grade->id, 'warehouse_area_id' => $this->area->id,
                'actual_weight_kg' => $weight, 'sack_count' => 1];
        }
        $receipt = app(GoodsReceiptService::class)->save(['farmer_id' => $this->farmer->id,
            'harvest_transaction_id' => $harvest->id, 'received_date' => '2026-10-03',
            'inventory_id' => $this->material->id, 'note_weight_kg' => 100, 'lines' => $lines]);
        app(GoodsReceiptService::class)->confirm($receipt, $this->user);

        $this->assertSame(100, (int) MaterialBatch::sum('remaining_cost_cents'));
        $this->assertSame(0, MaterialBatch::where('remaining_cost_cents', '<', 0)->count());
    }

    private function accountNet(string $code, bool $credit = false): int
    {
        $rows = DB::table('journal_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')->where('journal_entries.status', 'posted')->where('journal_lines.account_code', $code);

        return (int) ($credit ? $rows->sum('credit_cents') - $rows->sum('debit_cents') : $rows->sum('debit_cents') - $rows->sum('credit_cents'));
    }

    public function test_production_cannot_reuse_consumed_material_or_pack_twice(): void
    {
        $batch = $this->sourceBatch();
        $run = $this->production($batch);
        try {
            $this->production($batch);
            $this->fail('Consumed source accepted twice.');
        } catch (ValidationException $e) {
        }
        $this->assertSame(1, ProductionRun::count());
        app(ProductionService::class)->complete($run, '2026-10-03');
        $this->packing($run);
        try {
            $this->packing($run);
            $this->fail('Packing accepted twice.');
        } catch (ValidationException $e) {
        }
        $this->assertSame(1, FinishedBatch::count());
    }

    public function test_source_without_purchase_is_linked_and_costed_before_production_without_duplicate_stock(): void
    {
        $receipt = app(GoodsReceiptService::class)->save(['farmer_id' => $this->farmer->id, 'source_reference' => 'NOTA-AUDIT', 'received_date' => '2026-10-03', 'inventory_id' => $this->material->id,
            'lines' => [['quality_grade_id' => $this->grade->id, 'warehouse_area_id' => $this->area->id, 'actual_weight_kg' => 102, 'sack_count' => 4]]]);
        app(GoodsReceiptService::class)->confirm($receipt, $this->user);
        $batch = MaterialBatch::firstOrFail();
        try {
            $this->production($batch);
            $this->fail('Uncosted source accepted.');
        } catch (ValidationException $e) {
        }
        $this->assertSame(0, ProductionRun::count());
        $this->loan();
        $harvest = app(HarvestTransactionService::class)->createHarvest($this->harvestData());
        app(GoodsReceiptService::class)->linkPurchase($receipt, $harvest->id);
        $this->assertSame(1, StockMovement::count());
        $this->assertSame(102.0, (float) $this->material->refresh()->current_stock_kg);
        $this->assertSame(100000000, (int) $batch->refresh()->remaining_cost_cents);
        $this->production($batch);
        $this->assertSame(1, ProductionRun::count());
    }

    public function test_pending_journals_post_after_opening_and_posted_corrections_reverse_once(): void
    {
        $this->legacy->update(['current_stock_kg' => 0, 'current_sack_count' => 0]);
        $loan = $this->loan();
        $entry = $loan->journals()->firstOrFail();
        $this->assertSame('draft', $entry->status);
        app(JournalService::class)->startZero('2026-10-01', $this->user);
        $this->assertSame('posted', $entry->refresh()->status);
        app(LoanService::class)->updateLoan($loan, ['farmer_id' => $this->farmer->id, 'loan_date' => '2026-10-01', 'amount' => 1500000]);
        $this->assertNotNull($entry->refresh()->reversal);
        $this->assertSame(150000000, $this->accountNet('11201'));
        $count = JournalEntry::count();
        app(OperationalAccountingService::class)->loan($loan->refresh());
        $this->assertSame($count, JournalEntry::count());
        app(LoanService::class)->deleteLoan($loan);
        $this->assertSame(0, $this->accountNet('11201'));
        $this->assertSame(0, $this->accountNet('11101'));
        $this->assertSame(0, FarmerLedger::count());
    }

    public function test_production_forms_stage_pages_permissions_and_confirmation(): void
    {
        foreach (['production.read', 'production.create', 'production.confirm', 'receiving.read', 'journal.read', 'journal.post'] as $permission) {
            $p = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($p);
        }
        $this->withoutVite();
        $batch = $this->sourceBatch();
        $this->get(route('produksi.create'))->assertOk()->assertSee($batch->code);
        $payload = ['started_date' => '2026-10-03', 'warehouse_area_id' => WarehouseArea::where('usage', 'processing')->firstOrFail()->id, 'pic' => 'PIC', 'work_type' => 'Jemur', 'processing_cost' => 0, 'inputs' => [['material_batch_id' => $batch->id, 'sack_count' => 4]]];
        $this->postJson(route('produksi.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('confirm');
        $this->post(route('produksi.store'), $payload + ['confirm' => 1])->assertRedirect();
        $run = ProductionRun::firstOrFail();
        $this->get(route('produksi.show', $run))->assertOk()->assertSee('Proses aktif');
        $this->post(route('produksi.complete', $run), ['completed_date' => '2026-10-03', 'confirm' => 1])->assertRedirect();
        $this->get(route('produksi.show', $run))->assertOk()->assertSee('Press, timbang aktual');
        $area = WarehouseArea::where('usage', 'finished')->firstOrFail();
        $area->grades()->syncWithoutDetaching([$this->grade->id]);
        $this->post(route('produksi.pack', $run), ['packed_date' => '2026-10-04', 'packing_cost' => 0, 'outputs' => [['warehouse_area_id' => $area->id, 'quality_grade_id' => $this->grade->id, 'weight_kg' => 80, 'package_count' => 4]], 'confirm' => 1])->assertRedirect();
        $this->get(route('produksi.show', $run))->assertOk()->assertSee('Batch barang jadi');
        foreach (['process', 'wip', 'press', 'finished'] as $stage) {
            $this->get(route('manajemen-gudang.stage', $stage))->assertOk();
        }
        $this->get(route('manajemen-penjualan.index'))->assertOk()->assertSee('Batch barang jadi / kualitas / area');
        $this->get(route('jurnal.show', JournalEntry::where('kind', 'operational')->firstOrFail()))->assertOk()->assertSee('Jurnal dari transaksi operasional');
        $this->user->revokePermissionTo('production.create');
        $this->get(route('produksi.create'))->assertForbidden();
    }

    public function test_partial_material_use_and_split_output_preserve_every_cent_and_reference_weight(): void
    {
        $batch = $this->sourceBatch();
        $run = $this->production($batch, 3);
        $this->assertSame(1, (int) $batch->refresh()->remaining_sacks);
        $this->assertSame(25.5, (float) $batch->remaining_weight_kg);
        $this->assertSame(25000000, (int) $batch->remaining_cost_cents);
        $this->assertSame(75000000, (int) $run->material_cost_cents);
        app(ProductionService::class)->complete($run, '2026-10-03');
        $area = WarehouseArea::where('usage', 'finished')->firstOrFail();
        $area->grades()->attach($this->grade->id);
        $this->packing($run, ['outputs' => [
            ['warehouse_area_id' => $area->id, 'quality_grade_id' => $this->grade->id, 'weight_kg' => 20.01, 'package_count' => 1],
            ['warehouse_area_id' => $area->id, 'quality_grade_id' => $this->grade->id, 'weight_kg' => 39.99, 'package_count' => 2],
        ]]);
        $this->assertSame(85000000, (int) FinishedBatch::sum('cost_cents'));
        $this->assertSame(60.0, (float) FinishedBatch::sum('weight_kg'));
        $this->assertSame(3, (int) FinishedBatch::sum('package_count'));
    }

    public function test_invalid_packing_and_overselling_roll_back_stock_costs_and_journals(): void
    {
        $batch = $this->sourceBatch();
        $run = $this->production($batch);
        app(ProductionService::class)->complete($run, '2026-10-03');
        $area = WarehouseArea::where('usage', 'finished')->firstOrFail();
        $area->grades()->attach($this->grade->id);
        $area->update(['is_active' => false]);
        $journals = JournalEntry::count();
        try {
            $this->packing($run);
            $this->fail('Inactive output area accepted.');
        } catch (ValidationException $e) {
        }
        $this->assertSame('wip', $run->refresh()->status);
        $this->assertSame(0, FinishedBatch::count());
        $this->assertSame($journals, JournalEntry::count());
        $area->update(['is_active' => true]);
        $this->packing($run);
        $finished = FinishedBatch::firstOrFail();
        try {
            app(SalesService::class)->createSale(['factory_id' => $this->factory->id, 'inventory_id' => $finished->inventory_id, 'finished_batch_id' => $finished->id,
                'sale_date' => '2026-10-04', 'sold_weight_kg' => 81, 'sold_sacks' => 4, 'price_per_kg' => 20000]);
            $this->fail('Overselling accepted.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('melebihi', $e->getMessage());
        }
        $this->assertSame(0, SalesTransaction::count());
        $this->assertSame(80.0, (float) $finished->refresh()->remaining_weight_kg);
    }

    public function test_automatic_journals_cannot_be_manually_changed_deleted_posted_or_reversed(): void
    {
        $entry = $this->loan()->journals()->firstOrFail();
        foreach (['delete', 'post'] as $method) {
            try {
                app(JournalService::class)->$method($entry, $this->user);
                $this->fail('Automatic journal accepted a manual action.');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('otomatis', implode(' ', array_merge(...array_values($e->errors()))));
            }
        }
        $this->legacy->update(['current_stock_kg' => 0, 'current_sack_count' => 0]);
        app(JournalService::class)->startZero('2026-10-01', $this->user);
        try {
            app(JournalService::class)->reverse($entry, '2026-10-07', 'Pembalik manual', $this->user);
            $this->fail('Automatic reversal accepted.');
        } catch (ValidationException $e) {
        }
        $this->assertNull($entry->refresh()->reversal);
    }

    public function test_posted_payment_reversal_reconciles_cash_receivables_and_invoice(): void
    {
        $this->legacy->update(['current_stock_kg' => 0, 'current_sack_count' => 0]);
        app(JournalService::class)->startZero('2026-10-01', $this->user);
        $run = $this->production($this->sourceBatch());
        app(ProductionService::class)->complete($run, '2026-10-03');
        $this->packing($run);
        $batch = FinishedBatch::firstOrFail();
        $sale = app(SalesService::class)->createSale(['factory_id' => $this->factory->id, 'inventory_id' => $batch->inventory_id, 'finished_batch_id' => $batch->id, 'sale_date' => '2026-10-04',
            'sold_weight_kg' => 80, 'sold_sacks' => 4, 'price_per_kg' => 20000]);
        $invoice = $this->invoice($sale);
        $payment = app(InvoicePaymentService::class)->recordPayment(['invoice_id' => $invoice->id, 'payment_date' => '2026-10-05', 'amount' => 600000]);
        app(InvoicePaymentService::class)->deletePayment($payment);
        $this->assertSame('unpaid', $invoice->refresh()->payment_status);
        $this->assertSame(1600000, $sale->refresh()->remaining_amount);
        $this->assertSame(0, $this->cashIn('Penjualan Rumput Laut'));
        $this->assertSame(160000000, $this->accountNet('11202'));
        $this->assertSame(0, app(FinancialReportService::class)->month('2026-10')['difference']);
    }

    public function test_posted_loan_correction_keeps_prior_cash_period_and_puts_difference_in_correction_period(): void
    {
        $this->legacy->update(['current_stock_kg' => 0, 'current_sack_count' => 0]);
        app(JournalService::class)->startZero('2026-10-01', $this->user);
        $loan = $this->loan();
        Carbon::setTestNow(Carbon::parse('2026-11-07 12:00:00', 'Asia/Jakarta'));
        app(LoanService::class)->updateLoan($loan, ['farmer_id' => $this->farmer->id, 'loan_date' => '2026-10-01', 'amount' => 1500000]);
        $oct = $this->getJson(route('cash-flow.data',['start_date' => '2026-10-01', 'end_date' => '2026-10-31']))->assertOk()->json();
        $nov = $this->getJson(route('cash-flow.data',['start_date' => '2026-11-01', 'end_date' => '2026-11-30']))->assertOk()->json();
        $this->assertSame(1000000,(int) collect($oct)->where('category','Pinjaman Petani (Modal)')->sum('amount'));
        $this->assertSame(500000,(int) collect($nov)->where('category','Pinjaman Petani (Modal)')->sum('amount'));
    }
}
