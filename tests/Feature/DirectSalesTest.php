<?php

use App\Models\Factory;
use App\Models\Inventory;
use App\Models\Menu;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Invoice;
use App\Models\CustomerPurchaseOrder;
use App\Models\InvoicePayment;
use App\Models\DeliveryNote;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use App\Services\Sales\InvoicePaymentService;
use Database\Seeders\DirectSalesMenuSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-07 12:00:00', 'Asia/Jakarta'));
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php',
        '2026_04_26_062312_create_farmers_table.php',
        '2026_04_26_062526_create_harvest_transactions_table.php',
        '2026_04_26_153147_create_inventories_table.php',
        '2026_04_26_153352_create_factories_table.php',
        '2026_04_26_153448_create_sales_transactions_table.php',
        '2026_04_26_153534_create_stock_movements_table.php',
        '2026_05_02_000003_add_payment_fields_to_sales_transactions_table.php',
        '2026_10_06_000001_create_customer_purchase_orders_table.php',
        '2026_10_06_000004_create_delivery_notes_table.php',
        '2026_10_06_000006_create_invoices_table.php',
        '2026_10_06_000008_add_po_reference_to_invoices.php',
        '2026_10_06_225852_create_invoice_payments_table.php',
        '2026_10_07_000005_link_invoices_to_sales.php',
        '2026_10_21_000001_preserve_cart_deduction_rules.php',
        '2026_10_07_000006_add_delivery_confirmation_stock_flow.php',
        '2026_10_07_000007_create_quality_grades_table.php',
        '2026_10_07_000009_create_goods_receiving_flow.php',
        '2026_10_06_000003_add_factory_and_file_to_customer_purchase_orders.php',
        '2026_10_10_000001_link_sales_to_customer_purchase_orders.php',
        '2026_10_10_000002_create_company_profile_and_document_numbering.php',
        '2026_10_10_000003_add_delivery_note_template_fields.php',
        '2026_10_10_000006_add_customer_purchase_order_items.php',
        '2026_10_12_000001_add_cart_fields_to_sales_transactions.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    // The existing May migration also runs a MySQL-only ALTER on harvests.
    // Reproduce its sales columns in the SQLite fixture.
    Schema::table('sales_transactions', function (Blueprint $table) {
        $table->integer('sold_sacks')->nullable();
        $table->string('deduction_type')->default('none');
        $table->decimal('deduction_value', 12, 2)->default(0);
        $table->decimal('deduction_weight_kg', 12, 2)->default(0);
    });
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Tester', 'email' => 'direct@example.test', 'password' => 'password']));
    $this->grantModuleAccess(auth()->user(), ['manajemen-penjualan', 'po-konsumen', 'surat-jalan', 'invoice']);
    Permission::firstOrCreate(['name' => 'manajemen-penjualan.create', 'guard_name' => 'web']);
    auth()->user()->givePermissionTo('manajemen-penjualan.create');
    $this->factory = Factory::create(['name' => 'Pabrik Test']);
    $this->inventory = Inventory::create(['item_name' => 'Gudang Basah', 'current_stock_kg' => 1000, 'current_sack_count' => 40]);
    $this->payload = [
        'factory_id' => $this->factory->id, 'inventory_id' => $this->inventory->id,
        'sale_date' => '2026-10-07', 'sold_weight_kg' => 100, 'sold_sacks' => 4,
        'price_per_kg' => 10000, 'deduction_weight_kg' => 1,
        'deduction_type' => 'percentage', 'deduction_value' => 10,
    ];
    $this->po = CustomerPurchaseOrder::create([
        'po_number' => 'PO-LINK-001', 'po_date' => '2026-10-07', 'customer_name' => 'Pabrik Test',
        'customer_address' => 'Alamat Test', 'item_name' => 'Rumput Laut', 'quantity_kg' => 100, 'price_per_kg' => 10000,
    ]);
    $this->invoicePayload = [
        'customer_purchase_order_id' => $this->po->id, 'number' => 'INV-LINK-001',
        'invoice_date' => '2026-10-07', 'due_date' => '2026-11-07',
        'customer_name' => 'Pabrik Test', 'customer_address' => 'Alamat Test',
        'item_name' => 'Rumput Laut', 'quantity_kg' => 100, 'price_per_kg' => 10000,
        'payment_status' => 'unpaid',
    ];
    $this->shipmentPayload = [
        'number' => 'SJ-CONFIRM-001', 'delivery_date' => '2026-10-07',
        'customer_purchase_order_id' => $this->po->id, 'recipient_name' => 'Pabrik Test',
        'destination_address' => 'Alamat Test', 'origin' => 'Gudang Basah', 'item_name' => 'Rumput Laut',
        'quantity_kg' => 100, 'sack_count' => 4,
    ];
});

afterEach(function () { Carbon::setTestNow(); });

function checkoutStock($test): array
{
    Schema::table('sales_transactions', fn (Blueprint $table) => $table->unsignedBigInteger('quality_grade_id')->nullable());
    foreach (['2026_10_07_000002_create_chart_of_accounts_table.php', '2026_10_07_000012_create_accounting_journals.php', '2026_10_07_000015_connect_production_and_operational_accounting.php', '2026_10_10_000010_enable_independent_warehouse_processing.php', '2026_10_11_000002_use_warehouse_locations.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Menu::create(['name' => 'Keuangan', 'is_active' => true]);
    $test->seed(\Database\Seeders\ChartOfAccountSeeder::class);
    (require database_path('migrations/2026_10_12_000002_add_batch_selection_to_delivery_notes.php'))->up();
    (require database_path('migrations/2026_10_10_000016_allow_multiple_shipment_batches.php'))->up();
    (require database_path('migrations/2026_10_12_000003_create_delivery_note_items.php'))->up();
    $inventory = Inventory::where('stock_stage', 'finished')->firstOrFail();
    $inventory->update(['current_stock_kg' => 200, 'current_sack_count' => 8]);
    $area = \App\Models\WarehouseArea::where('usage', 'finished')->firstOrFail();
    $run = \App\Models\ProductionRun::create(['number' => 'PRD-CART', 'started_date' => '2026-10-07', 'completed_date' => '2026-10-07', 'packed_date' => '2026-10-07', 'status' => 'packed', 'warehouse_area_id' => $area->id, 'pic' => 'Tester', 'work_type' => 'Packing', 'warehouse_only' => true, 'created_by_name' => 'Tester']);
    $batches = [];
    foreach (['A', 'B'] as $code) {
        $batches[] = \App\Models\FinishedBatch::create(['code' => 'FG-CART-'.$code, 'production_run_id' => $run->id, 'warehouse_area_id' => $area->id, 'inventory_id' => $inventory->id, 'weight_kg' => 100, 'remaining_weight_kg' => 100, 'package_count' => 4, 'remaining_packages' => 4, 'cost_cents' => 0, 'remaining_cost_cents' => 0]);
    }
    return [$inventory, $batches];
}

function checkoutRow($batch, string $name): array
{
    return ['item_name' => $name, 'sold_weight_kg' => 50, 'sold_sacks' => 2, 'price_per_kg' => 10000];
}

it('ships area stock across FIFO batches without deducting draft stock and rolls back shortages', function () {
    [$inventory, $batches] = checkoutStock($this);
    (require database_path('migrations/2026_10_28_000001_add_delivery_note_stock_area.php'))->up();
    $batches[0]->update(['remaining_cost_cents' => 10000]);
    $batches[1]->update(['remaining_cost_cents' => 20000]);
    $this->postJson(route('manajemen-penjualan.store'), [
        'sale_source'=>'direct', 'factory_id'=>$this->factory->id, 'sale_date'=>'2026-10-07',
        'deduction_type'=>'none', 'deduction_value'=>0, 'deduction_weight_kg'=>0,
        'items'=>[array_replace(checkoutRow($batches[0], 'Rumput Laut'), ['sold_weight_kg'=>150, 'sold_sacks'=>6])],
    ])->assertOk();
    $sale = SalesTransaction::sole();
    $payload = array_merge($this->shipmentPayload, ['sales_transaction_id'=>$sale->id, 'items'=>[
        ['sales_transaction_id'=>$sale->id, 'warehouse_area_id'=>$batches[0]->warehouse_area_id,
            'item_name'=>'Rumput Laut', 'quantity_kg'=>150, 'sack_count'=>6],
    ]]);
    $this->postJson(route('surat-jalan.store'), $payload)->assertOk();
    $note = DeliveryNote::sole();
    expect((float) $inventory->refresh()->current_stock_kg)->toBe(200.0)
        ->and((float) $batches[0]->refresh()->remaining_weight_kg)->toBe(100.0)
        ->and(StockMovement::count())->toBe(0)
        ->and($note->items->sole()->warehouse_area_id)->toBe($batches[0]->warehouse_area_id);
    $batches[1]->update(['remaining_weight_kg'=>25, 'remaining_packages'=>1]);
    $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date'=>'2026-10-07', 'confirm'=>true])->assertUnprocessable();
    expect((float) $batches[0]->refresh()->remaining_weight_kg)->toBe(100.0)
        ->and(StockMovement::count())->toBe(0)->and($note->fresh()->status)->toBe('draft');
    $batches[1]->update(['remaining_weight_kg'=>100, 'remaining_packages'=>4]);
    $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date'=>'2026-10-07', 'confirm'=>true])->assertOk();
    expect((float) $batches[0]->refresh()->remaining_weight_kg)->toBe(0.0)
        ->and((int) $batches[0]->remaining_packages)->toBe(0)
        ->and((float) $batches[1]->refresh()->remaining_weight_kg)->toBe(50.0)
        ->and((int) $batches[1]->remaining_packages)->toBe(2)
        ->and((float) $inventory->refresh()->current_stock_kg)->toBe(50.0)
        ->and((int) $inventory->current_sack_count)->toBe(2)
        ->and(StockMovement::count())->toBe(2)
        ->and((int) StockMovement::sum('cost_cents'))->toBe(20000);
    $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date'=>'2026-10-07', 'confirm'=>true])->assertUnprocessable();
    expect(StockMovement::count())->toBe(2);
});

it('deducts shipment weight and packages from area totals despite different batch ratios', function () {
    [$inventory, $batches] = checkoutStock($this);
    (require database_path('migrations/2026_10_28_000001_add_delivery_note_stock_area.php'))->up();
    $batches[0]->update(['remaining_weight_kg'=>5000, 'remaining_packages'=>200, 'remaining_cost_cents'=>10000]);
    $batches[1]->update(['remaining_weight_kg'=>6529.31, 'remaining_packages'=>185, 'remaining_cost_cents'=>20000]);
    $inventory->update(['current_stock_kg'=>11529.31, 'current_sack_count'=>385]);
    $this->postJson(route('manajemen-penjualan.store'), [
        'sale_source'=>'direct', 'factory_id'=>$this->factory->id, 'sale_date'=>'2026-10-07',
        'deduction_type'=>'none', 'deduction_value'=>0, 'deduction_weight_kg'=>0,
        'items'=>[array_replace(checkoutRow($batches[0], 'Rumput Laut'), ['sold_weight_kg'=>11529.31, 'sold_sacks'=>385])],
    ])->assertOk();
    $sale = SalesTransaction::sole();
    foreach ([[5219, 188], [6310.31, 197]] as $index => [$weight, $count]) {
        $payload = array_merge($this->shipmentPayload, ['number'=>'SJ-AREA-'.($index+1), 'sales_transaction_id'=>$sale->id, 'items'=>[
            ['sales_transaction_id'=>$sale->id, 'warehouse_area_id'=>$batches[0]->warehouse_area_id,
                'item_name'=>'Rumput Laut', 'quantity_kg'=>$weight, 'sack_count'=>$count],
        ]]);
        $this->postJson(route('surat-jalan.store'), $payload)->assertOk();
        $note = DeliveryNote::latest('id')->firstOrFail();
        $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date'=>'2026-10-07', 'confirm'=>true])->assertOk();
        if ($index === 0) {
            expect(round((float) $inventory->refresh()->current_stock_kg, 2))->toBe(6310.31)
                ->and((int) $inventory->current_sack_count)->toBe(197);
            $this->get(route('surat-jalan.edit', $note))->assertForbidden();
            expect(\App\Models\FinishedBatch::readyForSale(true)->sum('remaining_packages'))->toBe(197);
        }
    }
    expect(round((float) $inventory->refresh()->current_stock_kg, 2))->toBe(0.0)
        ->and((int) $inventory->current_sack_count)->toBe(0)
        ->and((float) \App\Models\FinishedBatch::sum('remaining_weight_kg'))->toBe(0.0)
        ->and((int) \App\Models\FinishedBatch::sum('remaining_packages'))->toBe(0)
        ->and((int) StockMovement::sum('sack_count'))->toBe(385)
        ->and(round((float) StockMovement::sum('weight_kg'), 2))->toBe(11529.31)
        ->and((int) StockMovement::sum('cost_cents'))->toBe(30000);
});

it('limits automatic shipment allocation to the chosen area and grade', function () {
    [$inventory, $batches] = checkoutStock($this);
    (require database_path('migrations/2026_10_28_000001_add_delivery_note_stock_area.php'))->up();
    $grade = \App\Models\QualityGrade::create(['code'=>'AREA-A', 'name'=>'Grade A', 'is_active'=>true]);
    $otherGrade = \App\Models\QualityGrade::create(['code'=>'AREA-B', 'name'=>'Grade B', 'is_active'=>true]);
    $batches[0]->update(['quality_grade_id'=>$grade->id]);
    $batches[1]->update(['quality_grade_id'=>$otherGrade->id]);
    $otherArea = \App\Models\WarehouseArea::create(['code'=>'SJ-OTHER', 'name'=>'Area Lain', 'usage'=>'finished', 'inventory_id'=>$inventory->id, 'is_active'=>true]);
    $other = $batches[0]->replicate();
    $other->code = 'FG-OTHER'; $other->warehouse_area_id = $otherArea->id; $other->save();
    $this->postJson(route('manajemen-penjualan.store'), [
        'sale_source'=>'direct', 'factory_id'=>$this->factory->id, 'sale_date'=>'2026-10-07',
        'deduction_type'=>'none', 'deduction_value'=>0, 'deduction_weight_kg'=>0,
        'items'=>[array_replace(checkoutRow($batches[0], 'Grade A'), ['quality_grade_id'=>$grade->id, 'sold_weight_kg'=>150, 'sold_sacks'=>6])],
    ])->assertOk();
    $sale = SalesTransaction::sole();
    // Pin the grade independently of the sales request's product mapping.
    $sale->update(['quality_grade_id'=>$grade->id]);
    $payload = array_merge($this->shipmentPayload, ['sales_transaction_id'=>$sale->id, 'items'=>[
        ['sales_transaction_id'=>$sale->id, 'warehouse_area_id'=>$batches[0]->warehouse_area_id,
            'item_name'=>'Grade A', 'quantity_kg'=>150, 'sack_count'=>6],
    ]]);
    $this->postJson(route('surat-jalan.store'), $payload)->assertUnprocessable();
    $payload['items'][0]['quantity_kg']=100; $payload['items'][0]['sack_count']=5;
    $this->postJson(route('surat-jalan.store'), $payload)->assertUnprocessable();
    $payload['items'][0]['sack_count']=4;
    $this->postJson(route('surat-jalan.store'), $payload)->assertOk();
    $note = DeliveryNote::sole();
    $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date'=>'2026-10-07', 'confirm'=>true])->assertOk();
    expect((float) $batches[0]->fresh()->remaining_weight_kg)->toBe(0.0)
        ->and((float) $batches[1]->fresh()->remaining_weight_kg)->toBe(100.0)
        ->and((float) $other->fresh()->remaining_weight_kg)->toBe(100.0)
        ->and(StockMovement::count())->toBe(1);
});

it('filters warehouse history and separates shipment drafts from actual stock movements', function () {
    [$inventory, $batches] = checkoutStock($this);
    (require database_path('migrations/2026_10_15_000001_create_stock_movement_locations.php'))->up();
    $this->grantModuleAccess(auth()->user(), ['manajemen-gudang']);
    $area = $batches[0]->area;
    $otherArea = \App\Models\WarehouseArea::create(['code' => 'HISTORY-OTHER', 'name' => 'Area Lain', 'usage' => 'finished', 'inventory_id' => $inventory->id, 'is_active' => true]);
    $this->postJson(route('manajemen-penjualan.store'), ['sale_source' => 'direct', 'factory_id' => $this->factory->id,
        'sale_date' => '2026-10-07', 'deduction_type' => 'none', 'deduction_value' => 0, 'deduction_weight_kg' => 0,
        'items' => [checkoutRow($batches[0], 'Barang Riwayat')]])->assertOk();
    $sale = SalesTransaction::sole();
    $this->postJson(route('surat-jalan.store'), array_merge($this->shipmentPayload, [
        'customer_purchase_order_id' => null, 'sales_transaction_id' => $sale->id,
        'items' => [['sales_transaction_id' => $sale->id, 'finished_batch_id' => $batches[0]->id,
            'item_name' => 'Barang Riwayat', 'quantity_kg' => 50, 'sack_count' => 2]],
    ]))->assertOk();
    $note = DeliveryNote::sole();
    $history = route('manajemen-gudang.movements');
    $filters = ['format' => 'json', 'inventory_id' => $inventory->id, 'area_id' => $area->id];
    $this->get($history)->assertOk()->assertSee('Draft Pengiriman')->assertSee('Saldo gudang saat transaksi');
    $this->getJson($history.'?'.http_build_query($filters + ['tab' => 'drafts']))->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'Draft · Belum terpotong')->assertJsonPath('data.0.weight', 50);
    $this->getJson($history.'?'.http_build_query($filters))->assertOk()->assertJsonCount(0, 'data');
    expect((float) $inventory->refresh()->current_stock_kg)->toBe(200.0);
    $this->getJson($history.'?'.http_build_query(['format' => 'json', 'tab' => 'drafts', 'area_id' => $otherArea->id]))->assertJsonCount(0, 'data');
    $this->getJson($history.'?'.http_build_query($filters + ['tab' => 'drafts', 'date_from' => '2026-10-08']))->assertJsonCount(0, 'data');
    $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date' => '2026-10-07', 'confirm' => 1])->assertOk();
    $originalName = $area->name;
    $area->update(['name' => 'Nama area baru']);
    $this->getJson($history.'?'.http_build_query($filters + ['tab' => 'drafts']))->assertJsonCount(0, 'data');
    $this->getJson($history.'?'.http_build_query($filters))->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.area', $originalName)->assertJsonPath('data.0.activity', 'Barang berangkat')
        ->assertJsonPath('data.0.document', $note->number)->assertJsonPath('data.0.status', 'Sudah terpotong');
    $this->getJson($history.'?'.http_build_query($filters + ['date_to' => '2026-10-06']))->assertOk()->assertJsonCount(0, 'data');
    $this->getJson($history.'?'.http_build_query(['date_from' => '2026-10-08', 'date_to' => '2026-10-07']))->assertUnprocessable();
});

it('records both transfer areas without counting the same area movement twice', function () {
    [$inventory, $batches] = checkoutStock($this);
    (require database_path('migrations/2026_10_15_000001_create_stock_movement_locations.php'))->up();
    $origin = $batches[0]->area;
    $target = \App\Models\WarehouseArea::create(['code' => 'HISTORY-TARGET', 'name' => 'Area Tujuan', 'usage' => 'finished', 'inventory_id' => $inventory->id, 'is_active' => true]);
    app(\App\Services\Transaction\AreaStockTransferService::class)->transfer([
        'usage' => 'finished', 'source_kind' => 'area', 'from_id' => $origin->id, 'to_area_id' => $target->id,
        'weight_kg' => 50, 'sack_count' => 2, 'transfer_date' => '2026-10-07', 'description' => 'Pindah area',
    ]);
    $service = app(\App\Services\Transaction\WarehouseHistoryService::class);
    $out = $service->movements(['area_id' => $origin->id]);
    $in = $service->movements(['area_id' => $target->id]);
    expect($out)->toHaveCount(1)->and($in)->toHaveCount(1)
        ->and($out[0]['direction'])->toBe('out')->and($in[0]['direction'])->toBe('in')
        ->and($out[0]['counterpart'])->toBe('Area Tujuan')
        ->and($out[0]['weight'])->toBe(50.0)->and($in[0]['weight'])->toBe(50.0)
        ->and((float) $inventory->refresh()->current_stock_kg)->toBe(200.0);
});

it('saves several checkout products without stock and applies the transaction discount only once', function () {
    [$inventory, $batches] = checkoutStock($this);
    $this->get(route('manajemen-penjualan.create'))->assertOk()->assertViewIs('pages.transaction.sales.create')->assertSee('Daftar Barang');
    $inventory->update(['current_stock_kg' => 0, 'current_sack_count' => 0]);
    $payload = ['sale_source' => 'direct', 'deduction_type' => 'nominal', 'deduction_value' => 100000, 'deduction_weight_kg' => 0, 'factory_id' => $this->factory->id, 'sale_date' => '2026-10-07',
        'items' => [checkoutRow($batches[0], 'Rumput Laut Kering'), checkoutRow($batches[1], 'Rumput Laut Bersih')]];
    $invalid = $payload;
    $invalid['deduction_value'] = 2000000;
    $this->postJson(route('manajemen-penjualan.store'), $invalid)->assertUnprocessable()->assertJsonValidationErrors('deduction_value');
    expect(SalesTransaction::count())->toBe(0)->and(StockMovement::count())->toBe(0)
        ->and((float) $inventory->refresh()->current_stock_kg)->toBe(0.0)
        ->and((float) $batches[0]->refresh()->remaining_weight_kg)->toBe(100.0);
    $this->postJson(route('manajemen-penjualan.store'), $payload)->assertOk()->assertJsonPath('data.item_count', 2);
    expect(SalesTransaction::count())->toBe(2)->and(SalesTransaction::distinct()->count('sale_group_number'))->toBe(1)
        ->and(SalesTransaction::orderBy('id')->get()->pluck('document_item_name')->all())->toBe(['Rumput Laut Kering', 'Rumput Laut Bersih'])
        ->and((float) $inventory->refresh()->current_stock_kg)->toBe(0.0)
        ->and((int) $inventory->current_sack_count)->toBe(0)->and(StockMovement::count())->toBe(0)
        ->and(SalesTransaction::where('cart_deduction_type', 'nominal')->count())->toBe(2)
        ->and(SalesTransaction::where('cart_deduction_value', 100000)->count())->toBe(2)
        ->and((int) SalesTransaction::sum('total_sale_value'))->toBe(900000)
        ->and(SalesTransaction::whereNotNull('finished_batch_id')->count())->toBe(0);
});

it('loads all PO rows and accepts edited checkout prices and partial quantities without changing the PO', function () {
    [$inventory, $batches] = checkoutStock($this);
    $po = app(\App\Services\Sales\CustomerPurchaseOrderService::class)->store([
        'po_number' => 'PO-CART', 'po_date' => '2026-10-07', 'factory_id' => $this->factory->id,
        'items' => [['item_name' => 'Rumput Laut Kering', 'quantity_kg' => 80, 'price_per_kg' => 10000], ['item_name' => 'Rumput Laut Bersih', 'quantity_kg' => 100, 'price_per_kg' => 12000]],
    ]);
    $this->getJson(route('manajemen-penjualan.purchase-orders'))->assertOk()->assertJsonCount(2, 'data.0.items');
    $items = $po->items()->orderBy('id')->get();
    $rows = [checkoutRow($batches[0], $items[0]->description), checkoutRow($batches[1], $items[1]->description)];
    foreach ($rows as $i => &$row) $row['customer_purchase_order_item_id'] = $items[$i]->id;
    unset($row);
    $rows[0]['price_per_kg'] = 9500;
    $payload = ['sale_source' => 'po', 'customer_purchase_order_id' => $po->id, 'deduction_type' => 'none', 'deduction_value' => 0, 'deduction_weight_kg' => 0, 'factory_id' => $this->factory->id, 'sale_date' => '2026-10-07', 'items' => $rows];
    $invalid = $payload;
    $invalid['items'][1]['sold_weight_kg'] = 101;
    $this->postJson(route('manajemen-penjualan.store'), $invalid)->assertUnprocessable();
    expect(SalesTransaction::count())->toBe(0);
    $this->postJson(route('manajemen-penjualan.store'), $payload)->assertOk()->assertJsonPath('data.item_count', 2);
    expect((float) SalesTransaction::oldest('id')->first()->price_per_kg)->toBe(9500.0)
        ->and((float) $items[0]->refresh()->price_per_kg)->toBe(10000.0)
        ->and((float) $inventory->refresh()->current_stock_kg)->toBe(200.0)->and(StockMovement::count())->toBe(0);
    $this->getJson(route('manajemen-penjualan.purchase-orders'))->assertOk()->assertJsonPath('data.0.items.0.remaining_kg', 30)->assertJsonPath('data.0.items.1.remaining_kg', 50);
});

it('lists one note and settles every unpaid product together using the remaining note amount', function () {
    [$inventory, $batches] = checkoutStock($this);
    $this->postJson(route('manajemen-penjualan.store'), ['sale_source' => 'direct', 'factory_id' => $this->factory->id,
        'sale_date' => '2026-10-07', 'deduction_type' => 'none', 'deduction_value' => 0, 'deduction_weight_kg' => 0,
        'items' => [checkoutRow($batches[0], 'Rumput Laut Kering'), checkoutRow($batches[1], 'Rumput Laut Bersih')]])->assertOk();
    $items = SalesTransaction::orderBy('id')->get();
    $items[0]->update(['paid_amount' => 100000]);
    $this->get(route('manajemen-penjualan.index', ['view' => 'products']))->assertOk()->assertViewIs('pages.transaction.sales.products');
    $this->getJson(route('manajemen-penjualan.data', ['view' => 'products']))->assertOk()->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.sale_reference', $items[0]->sale_reference);
    $this->getJson(route('manajemen-penjualan.data'))->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonCount(2, 'data.0.items')->assertJsonPath('data.0.total_sale_value', 1000000)
        ->assertJsonPath('data.0.paid_amount', 100000)->assertJsonPath('data.0.remaining_amount', 900000)
        ->assertJsonPath('data.0.can_pay', true);
    $this->postJson(route('manajemen-penjualan.pay', $items[1]), ['payment_date' => '2026-10-07', 'paid_amount' => 500000])->assertUnprocessable();
    expect((int) $items[0]->refresh()->paid_amount)->toBe(100000)->and((int) $items[1]->refresh()->paid_amount)->toBe(0);
    $this->postJson(route('manajemen-penjualan.pay', $items[1]), ['payment_date' => '2026-10-07', 'paid_amount' => 900000])->assertOk();
    expect(SalesTransaction::where('payment_status', 'paid')->count())->toBe(2)
        ->and((int) SalesTransaction::sum('paid_amount'))->toBe(1000000)
        ->and((float) $inventory->refresh()->current_stock_kg)->toBe(200.0);
    $this->getJson(route('manajemen-penjualan.data'))->assertJsonCount(1, 'data')->assertJsonPath('data.0.can_pay', false)
        ->assertJsonPath('data.0.payment_status', 'paid')->assertJsonPath('data.0.remaining_amount', 0);
    $this->postJson(route('manajemen-penjualan.pay', $items[0]), ['payment_date' => '2026-10-07', 'paid_amount' => 900000])->assertUnprocessable();
});

it('blocks note settlement when any product uses an invoice even when paying another product', function () {
    [$inventory, $batches] = checkoutStock($this);
    $this->postJson(route('manajemen-penjualan.store'), ['sale_source' => 'direct', 'factory_id' => $this->factory->id,
        'sale_date' => '2026-10-07', 'deduction_type' => 'none', 'deduction_value' => 0, 'deduction_weight_kg' => 0,
        'items' => [checkoutRow($batches[0], 'Rumput Laut Kering'), checkoutRow($batches[1], 'Rumput Laut Bersih')]])->assertOk();
    $items = SalesTransaction::orderBy('id')->get();
    $this->postJson(route('invoice.store'), array_merge($this->invoicePayload, ['sales_transaction_id' => $items[1]->id]))->assertOk();
    $this->getJson(route('manajemen-penjualan.data'))->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.has_invoice', true)->assertJsonPath('data.0.can_pay', false);
    foreach ($items as $item) {
        $this->postJson(route('manajemen-penjualan.pay', $item), ['payment_date' => '2026-10-07', 'paid_amount' => 1000000])
            ->assertUnprocessable()->assertJsonPath('message', 'Nota ini menggunakan invoice. Catat pembayaran melalui invoice terkait.');
    }
    expect((int) SalesTransaction::sum('paid_amount'))->toBe(0)->and(SalesTransaction::where('payment_status', 'paid')->count())->toBe(0);
});

it('checks stock on the delivery note and rechecks it before shipping an unallocated sale', function () {
    [$inventory, $batches] = checkoutStock($this);
    $row = checkoutRow($batches[0], 'Rumput Laut Kering');
    $row['sold_weight_kg'] = 250; $row['sold_sacks'] = 10;
    $this->postJson(route('manajemen-penjualan.store'), ['sale_source' => 'direct', 'factory_id' => $this->factory->id,
        'sale_date' => '2026-10-07', 'deduction_type' => 'none', 'deduction_value' => 0, 'deduction_weight_kg' => 0, 'items' => [$row]])->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $note = array_merge($this->shipmentPayload, ['sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => null,
        'delivery_date' => '2026-10-07', 'finished_batch_id' => $batches[0]->id, 'quantity_kg' => 150, 'sack_count' => 6]);
    $this->postJson(route('surat-jalan.store'), $note)->assertUnprocessable()->assertJsonValidationErrors('quantity_kg');
    $note['quantity_kg'] = 50; $note['sack_count'] = 2;
    $this->postJson(route('surat-jalan.store'), $note)->assertOk();
    $first = DeliveryNote::firstOrFail();
    $this->postJson(route('surat-jalan.dispatch', $first), ['shipped_date' => '2026-10-07', 'confirm' => 1])->assertOk();
    expect((float) $inventory->refresh()->current_stock_kg)->toBe(150.0)
        ->and((float) $batches[0]->refresh()->remaining_weight_kg)->toBe(50.0);
    $note['number'] = 'SJ-SECOND';
    $this->postJson(route('surat-jalan.store'), $note)->assertOk();
    $inventory->update(['current_stock_kg' => 0, 'current_sack_count' => 0]);
    $second = DeliveryNote::latest('id')->firstOrFail();
    $this->postJson(route('surat-jalan.dispatch', $second), ['shipped_date' => '2026-10-07', 'confirm' => 1])->assertUnprocessable();
    expect($second->refresh()->status)->toBe('draft')->and((float) $batches[0]->refresh()->remaining_weight_kg)->toBe(50.0)
        ->and(StockMovement::count())->toBe(1);
});

it('loads a sale note on a full page and ships its edited products in one document', function () {
    [$inventory,$batches]=checkoutStock($this);
    $this->postJson(route('manajemen-penjualan.store'), ['sale_source'=>'direct','factory_id'=>$this->factory->id,'sale_date'=>'2026-10-07','deduction_type'=>'none','deduction_value'=>0,'deduction_weight_kg'=>0,'items'=>[checkoutRow($batches[0],'Rumput Laut Kering'),checkoutRow($batches[1],'Rumput Laut Bersih')]])->assertOk();
    $sales=SalesTransaction::orderBy('id')->get();
    $this->get(route('surat-jalan.create'))->assertOk()->assertViewIs('pages.sales.delivery-note.form')->assertViewHas('sales',fn($options)=>$options->count()===1 && $options[0]['items']->count()===2);
    $payload=array_merge($this->shipmentPayload,['customer_purchase_order_id'=>null,'sales_transaction_id'=>$sales[0]->id,'delivery_date'=>'2026-10-07','items'=>[
        ['sales_transaction_id'=>$sales[0]->id,'finished_batch_id'=>$batches[0]->id,'item_name'=>'Rumput Laut Kering - Kirim Sebagian','quantity_kg'=>25,'sack_count'=>1],
        ['sales_transaction_id'=>$sales[1]->id,'finished_batch_id'=>$batches[1]->id,'item_name'=>'Rumput Laut Bersih','quantity_kg'=>50,'sack_count'=>2],
    ]]);
    $this->postJson(route('surat-jalan.store'),$payload)->assertOk();
    $note=DeliveryNote::firstOrFail();
    $this->get(route('surat-jalan.create'))->assertOk()->assertViewHas('sales', fn ($options) => $options->isEmpty());
    expect($note->items()->count())->toBe(2)->and((float)$note->quantity_kg)->toBe(75.0)->and((float)$inventory->refresh()->current_stock_kg)->toBe(200.0);
    $this->get(route('surat-jalan.edit',$note))->assertOk()->assertViewIs('pages.sales.delivery-note.form')
        ->assertViewHas('sales', fn ($options) => $options->count() === 1);
    $this->get(route('surat-jalan.print',$note))->assertOk()->assertSee('Rumput Laut Kering - Kirim Sebagian')->assertSee('Rumput Laut Bersih');
    $this->postJson(route('surat-jalan.dispatch',$note),['shipped_date'=>'2026-10-07','confirm'=>1])->assertOk();
    expect((float)$inventory->refresh()->current_stock_kg)->toBe(125.0)->and(StockMovement::count())->toBe(2)->and($note->refresh()->status)->toBe('shipped');
    $this->get(route('surat-jalan.create'))->assertOk()->assertViewHas('sales', fn ($options) => $options->isEmpty());
    $this->postJson(route('surat-jalan.dispatch',$note),['shipped_date'=>'2026-10-07','confirm'=>1])->assertUnprocessable();
    $this->get(route('surat-jalan.edit',$note))->assertForbidden();
});

it('records sales without invoices and reduces warehouse stock exactly once', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    expect(str_starts_with($sale->invoice_number, 'PJL-'))->toBeTrue()
        ->and((int) $sale->total_sale_value)->toBe(864000)
        ->and($sale->payment_status)->toBe('unpaid')
        ->and($sale->remaining_amount)->toBe(864000)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(900.0)
        ->and($this->inventory->current_sack_count)->toBe(36)
        ->and(StockMovement::count())->toBe(1)
        ->and(StockMovement::first()->sack_count)->toBe(4);

    $this->postJson(route('manajemen-penjualan.pay', $sale), [
        'payment_date' => '2026-10-08', 'paid_amount' => 864000,
    ])->assertOk();
    expect($sale->refresh()->payment_status)->toBe('paid')
        ->and($sale->remaining_amount)->toBe(0)
        ->and((int) $sale->paid_amount)->toBe(864000)
        ->and($sale->payment_date->format('Y-m-d'))->toBe('2026-10-08')
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(900.0)
        ->and(StockMovement::count())->toBe(1);
    $this->postJson(route('manajemen-penjualan.pay', $sale), [
        'payment_date' => '2026-10-09', 'paid_amount' => 864000,
    ])->assertUnprocessable()->assertJsonPath('message', 'Penjualan ini sudah lunas.');
    expect($sale->refresh()->payment_date->format('Y-m-d'))->toBe('2026-10-08');
});

it('rejects invalid stock, bonus weight, and payment without leaving partial mutations', function () {
    foreach ([['sold_weight_kg' => 1001], ['sold_sacks' => 41], ['deduction_weight_kg' => 30], ['payment_status' => 'paid']] as $changes) {
        $this->postJson(route('manajemen-penjualan.store'), array_replace($this->payload, $changes))->assertUnprocessable();
        expect(SalesTransaction::count())->toBe(0)
            ->and(StockMovement::count())->toBe(0)
            ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(1000.0)
            ->and($this->inventory->current_sack_count)->toBe(40);
    }
    $this->postJson(route('manajemen-penjualan.store'), $this->payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    foreach ([['paid_amount' => 900000], ['paid_amount' => 800000], ['payment_date' => '2026-10-06']] as $changes) {
        $this->postJson(route('manajemen-penjualan.pay', $sale), array_replace([
            'paid_amount' => 864000, 'payment_date' => '2026-10-07',
        ], $changes))->assertUnprocessable();
        expect($sale->refresh()->payment_status)->toBe('unpaid')->and((int) $sale->paid_amount)->toBe(0);
    }
});

it('moves the old menu without duplicating it and retains historical sales references', function () {
    $warehouse = Menu::create(['name' => 'Manajemen Gudang', 'is_active' => true]);
    $parent = Menu::create(['name' => 'Penjualan', 'is_active' => true]);
    Menu::create(['name' => 'PO konsumen', 'route' => 'po-konsumen.index', 'parent_id' => $parent->id, 'order' => 1]);
    $oldMenu = Menu::create(['name' => 'Manajemen Penjualan', 'route' => 'manajemen-penjualan.index', 'parent_id' => $warehouse->id]);
    $historical = SalesTransaction::create([
        'factory_id' => $this->factory->id, 'inventory_id' => $this->inventory->id,
        'invoice_number' => 'INV-LAMA-001', 'sale_date' => '2026-05-01',
        'total_weight_kg' => 10, 'price_per_kg' => 10000, 'total_sale_value' => 100000,
        'payment_status' => 'paid',
    ]);
    $this->seed(DirectSalesMenuSeeder::class);
    $this->seed(DirectSalesMenuSeeder::class);
    expect(Menu::where('route', 'manajemen-penjualan.index')->count())->toBe(1)
        ->and($oldMenu->refresh()->parent_id)->toBe($parent->id)
        ->and($oldMenu->name)->toBe('penjualan')
        ->and($historical->refresh()->invoice_number)->toBe('INV-LAMA-001')
        ->and($historical->remaining_amount)->toBe(0)
        ->and($historical->paid_amount)->toBeNull();
    $this->get(route('manajemen-penjualan.index'))->assertOk()->assertSee('Tambah Penjualan')->assertSee('INV-LAMA-001')->assertSee('Gudang Basah');
});

it('links invoices to the existing sale and bills its net value without another stock movement', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $data = $this->invoicePayload + ['sales_transaction_id' => $sale->id];
    $this->postJson(route('invoice.store'), array_replace($data, ['quantity_kg' => 999, 'price_per_kg' => 1, 'payment_status' => 'paid']))->assertOk();
    $invoice = Invoice::firstOrFail();
    expect($invoice->sales_transaction_id)->toBe($sale->id)
        ->and($invoice->total_amount)->toBe(864000.0)
        ->and($invoice->deduction_amount)->toBe(136000.0)
        ->and($invoice->quantity_kg)->toBe('100.00')
        ->and($invoice->payment_status)->toBe('unpaid')
        ->and(SalesTransaction::count())->toBe(1)
        ->and(StockMovement::count())->toBe(1)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(900.0);
    $this->get(route('invoice.index'))->assertOk()->assertSee('Konsumen / Penjualan')->assertSee($sale->invoice_number);
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee($sale->invoice_number)->assertSee('Rp864.000,00')->assertSee('Rp136.000,00');
    $this->postJson(route('invoice.store'), array_replace($data, ['number' => 'INV-DUPLICATE']))->assertUnprocessable()->assertJsonValidationErrors('sales_transaction_id');
    $this->postJson(route('manajemen-penjualan.pay', $sale), ['payment_date' => '2026-10-08', 'paid_amount' => 864000])->assertUnprocessable();
    $this->deleteJson(route('manajemen-penjualan.destroy', $sale))->assertUnprocessable();
    $this->deleteJson(route('invoice.destroy', $invoice))->assertOk();
    expect(SalesTransaction::count())->toBe(1)->and(StockMovement::count())->toBe(1)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(900.0);
});

it('synchronizes invoice installments and their reversal with the sale and protects paid invoices', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $this->postJson(route('invoice.store'), $this->invoicePayload + ['sales_transaction_id' => $sale->id])->assertOk();
    $invoice = Invoice::firstOrFail();
    $this->postJson(route('invoice.pay', $invoice), ['invoice_id' => $invoice->id, 'payment_date' => '2026-10-08', 'amount' => 300000])->assertOk();
    expect($invoice->refresh()->payment_status)->toBe('partial')
        ->and((int) $sale->refresh()->paid_amount)->toBe(300000)
        ->and($sale->remaining_amount)->toBe(564000)
        ->and($sale->payment_status_label)->toBe('Cicilan');
    $this->deleteJson(route('invoice.destroy', $invoice))->assertUnprocessable();
    $this->postJson(route('invoice.pay', $invoice), ['invoice_id' => $invoice->id, 'payment_date' => '2026-10-09', 'amount' => 564001])->assertUnprocessable();
    $this->postJson(route('invoice.pay', $invoice), ['invoice_id' => $invoice->id, 'payment_date' => '2026-10-09', 'amount' => 564000])->assertOk();
    expect($sale->refresh()->payment_status)->toBe('paid')->and($sale->remaining_amount)->toBe(0)
        ->and((int) $sale->paid_amount)->toBe(864000)->and(StockMovement::count())->toBe(1);
    app(InvoicePaymentService::class)->deletePayment(InvoicePayment::orderByDesc('id')->first());
    expect($invoice->refresh()->payment_status)->toBe('partial')
        ->and((int) $sale->refresh()->paid_amount)->toBe(300000)->and($sale->remaining_amount)->toBe(564000)
        ->and($sale->payment_date->format('Y-m-d'))->toBe('2026-10-08');
    $this->putJson(route('invoice.update', $invoice), array_replace($this->invoicePayload, [
        'sales_transaction_id' => $sale->id, 'invoice_date' => '2026-10-09',
    ]))->assertUnprocessable()->assertJsonValidationErrors('invoice_date');
});

it('preserves unlinked legacy invoices and only links matching unpaid history', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $legacy = Invoice::create($this->invoicePayload);
    $this->getJson(route('invoice.data'))->assertOk()->assertJsonPath('data.0.sales_transaction_id', null);
    $this->get(route('invoice.index'))->assertOk()->assertSee('Belum terhubung (data lama)');
    $this->putJson(route('invoice.update', $legacy), $this->invoicePayload + ['sales_transaction_id' => $sale->id])
        ->assertUnprocessable()->assertJsonValidationErrors('sales_transaction_id');
    $legacy->update(['quantity_kg' => 100, 'price_per_kg' => 8640]);
    $this->putJson(route('invoice.update', $legacy), $this->invoicePayload + ['sales_transaction_id' => $sale->id])->assertOk();
    expect($legacy->refresh()->sales_transaction_id)->toBe($sale->id)->and($legacy->total_amount)->toBe(864000.0)
        ->and(StockMovement::count())->toBe(1);
});

it('rejects missing sales, mismatched customers, invalid dates and relinking paid legacy records', function () {
    $this->postJson(route('invoice.store'), $this->invoicePayload)->assertUnprocessable()->assertJsonValidationErrors('sales_transaction_id');
    $this->postJson(route('manajemen-penjualan.store'), $this->payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $data = $this->invoicePayload + ['sales_transaction_id' => $sale->id];
    $this->postJson(route('invoice.store'), array_replace($data, ['invoice_date' => '2026-10-06']))->assertUnprocessable()->assertJsonValidationErrors('invoice_date');
    $this->po->update(['customer_name' => 'Pabrik Lain']);
    $this->postJson(route('invoice.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('customer_purchase_order_id');
    $this->po->update(['customer_name' => 'Pabrik Test']);
    $legacy = Invoice::create($this->invoicePayload + ['paid_amount' => 100]);
    $this->putJson(route('invoice.update', $legacy), $data)->assertUnprocessable()->assertJsonValidationErrors('sales_transaction_id');
    expect($legacy->refresh()->sales_transaction_id)->toBeNull()->and(StockMovement::count())->toBe(1);
    $legacy->delete();
    $this->postJson(route('invoice.store'), $data)->assertOk();
    $invoice = Invoice::firstOrFail();
    $this->postJson(route('manajemen-penjualan.store'), $this->payload)->assertOk();
    $other = SalesTransaction::orderByDesc('id')->first();
    $this->putJson(route('invoice.update', $invoice), array_replace($data, ['sales_transaction_id' => $other->id]))->assertUnprocessable()->assertJsonValidationErrors('sales_transaction_id');
    $this->postJson(route('invoice.pay', $invoice), ['invoice_id' => $invoice->id, 'payment_date' => '2026-10-06', 'amount' => 1])->assertUnprocessable();
});

it('keeps draft sales and shipment documents from changing stock and confirms exactly once', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload + ['stock_mode' => 'delivery_note'])->assertOk();
    $sale = SalesTransaction::firstOrFail();
    expect(StockMovement::count())->toBe(0)->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(1000.0);
    $this->postJson(route('surat-jalan.store'), $this->shipmentPayload + [
        'sales_transaction_id' => $sale->id, 'status' => 'shipped', 'shipped_at' => '2026-10-07',
    ])->assertOk();
    $note = DeliveryNote::firstOrFail();
    expect($note->status)->toBe('draft')->and($note->shipped_at)->toBeNull()->and(StockMovement::count())->toBe(0);
    $this->get(route('surat-jalan.print', $note))->assertOk()->assertSee('DRAFT — BELUM DIKIRIM');
    $this->postJson(route('invoice.store'), $this->invoicePayload + ['sales_transaction_id' => $sale->id])->assertOk();
    $invoice = Invoice::firstOrFail();
    expect($invoice->delivery_note_references)->toBe([])->and(StockMovement::count())->toBe(0);
    $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertOk();
    expect($note->refresh()->status)->toBe('shipped')->and($note->shipped_by)->toBe(auth()->id())
        ->and($note->shipped_by_name)->toBe('Tester')->and($note->shipped_date->format('Y-m-d'))->toBe('2026-10-07')
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(900.0)
        ->and($this->inventory->current_sack_count)->toBe(36)
        ->and(StockMovement::count())->toBe(1)->and(StockMovement::first()->delivery_note_id)->toBe($note->id)
        ->and($invoice->fresh()->delivery_note_references[0]['number'])->toBe($note->number);
    $movement = StockMovement::firstOrFail();
    expect(fn () => StockMovement::create($movement->only([
        'inventory_id', 'sales_transaction_id', 'delivery_note_id', 'movement_date',
        'direction', 'type', 'weight_kg', 'sack_count', 'balance_after_kg',
    ])))->toThrow(\Illuminate\Database\QueryException::class);
    $this->get(route('surat-jalan.print', $note))->assertOk()->assertSee('DIKIRIM')->assertSee('Tester');
    $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertUnprocessable();
    $this->putJson(route('surat-jalan.update', $note), $this->shipmentPayload + ['sales_transaction_id' => $sale->id])->assertUnprocessable();
    $this->deleteJson(route('surat-jalan.destroy', $note))->assertUnprocessable();
    expect(StockMovement::count())->toBe(1)->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(900.0);
});

it('limits partial shipments by total weight and sacks and rolls back insufficient stock', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload + ['stock_mode' => 'delivery_note'])->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $data = $this->shipmentPayload + ['sales_transaction_id' => $sale->id];
    $this->postJson(route('surat-jalan.store'), array_replace($data, ['quantity_kg' => 60, 'sack_count' => 3]))->assertOk();
    $first = DeliveryNote::firstOrFail();
    $this->postJson(route('surat-jalan.dispatch', $first), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertOk();
    $this->postJson(route('surat-jalan.store'), array_replace($data, ['number' => 'SJ-OVER-SACKS', 'quantity_kg' => 10, 'sack_count' => 2]))->assertOk();
    $overSacks = DeliveryNote::orderByDesc('id')->first();
    $this->postJson(route('surat-jalan.dispatch', $overSacks), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertUnprocessable();
    $this->postJson(route('surat-jalan.store'), array_replace($data, ['number' => 'SJ-SECOND', 'quantity_kg' => 40, 'sack_count' => 1]))->assertOk();
    $second = DeliveryNote::orderByDesc('id')->first();
    $this->inventory->update(['current_stock_kg' => 30]);
    $this->postJson(route('surat-jalan.dispatch', $second), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertUnprocessable();
    expect($second->refresh()->status)->toBe('draft')->and(StockMovement::count())->toBe(1)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(30.0)->and($this->inventory->current_sack_count)->toBe(37);
    $this->inventory->update(['current_stock_kg' => 940]);
    $this->postJson(route('surat-jalan.dispatch', $second), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertOk();
    $this->postJson(route('surat-jalan.store'), array_replace($data, ['number' => 'SJ-EXCESS', 'quantity_kg' => 1, 'sack_count' => 0]))->assertOk();
    $excess = DeliveryNote::orderByDesc('id')->first();
    $this->postJson(route('surat-jalan.dispatch', $excess), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertUnprocessable();
    expect(StockMovement::count())->toBe(2)->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(900.0)
        ->and($this->inventory->current_sack_count)->toBe(36);
});

it('prevents legacy or immediate sales from deducting stock through a shipment', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $this->postJson(route('surat-jalan.store'), $this->shipmentPayload + ['sales_transaction_id' => $sale->id])->assertUnprocessable();
    $legacy = DeliveryNote::create($this->shipmentPayload);
    expect($legacy->refresh()->status)->toBe('legacy');
    $this->postJson(route('surat-jalan.dispatch', $legacy), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertUnprocessable();
    expect(StockMovement::count())->toBe(1)->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(900.0);
    $this->putJson(route('surat-jalan.update', $legacy), array_replace($this->shipmentPayload, ['notes' => 'Perbaikan dokumen lama']))->assertOk();
    expect($legacy->refresh()->status)->toBe('legacy')->and($legacy->sales_transaction_id)->toBeNull();
});

it('requires permission, explicit confirmation and valid actual dates before shipping', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload + ['stock_mode' => 'delivery_note'])->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $this->postJson(route('surat-jalan.store'), $this->shipmentPayload + ['sales_transaction_id' => $sale->id])->assertOk();
    $note = DeliveryNote::firstOrFail();
    foreach ([['confirm' => false], ['shipped_date' => '2026-10-06'], ['shipped_date' => '2026-10-08']] as $change) {
        $this->postJson(route('surat-jalan.dispatch', $note), array_replace(['shipped_date' => '2026-10-07', 'confirm' => true], $change))->assertUnprocessable();
    }
    auth()->user()->revokePermissionTo('surat-jalan.update');
    $this->postJson(route('surat-jalan.dispatch', $note), ['shipped_date' => '2026-10-07', 'confirm' => true])->assertForbidden();
    expect(StockMovement::count())->toBe(0)->and($note->refresh()->status)->toBe('draft');
});

it('does not restore nonexistent stock when a deferred sale without shipments is removed', function () {
    $this->postJson(route('manajemen-penjualan.store'), $this->payload + ['stock_mode' => 'delivery_note'])->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $this->deleteJson(route('manajemen-penjualan.destroy', $sale))->assertOk();
    expect((float) $this->inventory->refresh()->current_stock_kg)->toBe(1000.0)
        ->and($this->inventory->current_sack_count)->toBe(40)->and(StockMovement::count())->toBe(0);
});

it('draws consumer and price from a PO and limits partial sales to its remaining quantity', function () {
    $this->po->update(['factory_id' => $this->factory->id, 'price_per_kg' => 12345.67]);
    $other = Factory::create(['name' => 'Pabrik Lain']);
    $payload = array_merge($this->payload, [
        'sale_source' => 'po', 'customer_purchase_order_id' => $this->po->id,
        'factory_id' => $other->id, 'price_per_kg' => 1, 'sold_weight_kg' => 60,
        'stock_mode' => 'delivery_note', 'deduction_weight_kg' => 0, 'deduction_type' => 'none',
    ]);
    $this->postJson(route('manajemen-penjualan.store'), $payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    expect($sale->customer_purchase_order_id)->toBe($this->po->id)
        ->and($sale->factory_id)->toBe($this->factory->id)
        ->and((float) $sale->price_per_kg)->toBe(12345.67)
        ->and((int) $sale->total_sale_value)->toBe(740740)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(1000.0);
    $this->getJson(route('manajemen-penjualan.purchase-orders'))->assertOk()->assertJsonPath('data.0.remaining_kg', 40);
    $this->postJson(route('manajemen-penjualan.store'), array_merge($payload, ['sold_weight_kg' => 41]))
        ->assertUnprocessable()->assertJsonPath('message', 'Berat penjualan melebihi sisa jumlah PO konsumen.');
    expect(SalesTransaction::count())->toBe(1);
    $this->postJson(route('manajemen-penjualan.store'), array_merge($payload, ['sold_weight_kg' => 40]))->assertOk();
    $this->getJson(route('manajemen-penjualan.purchase-orders'))->assertOk()->assertJsonCount(0, 'data');
    $this->deleteJson(route('manajemen-penjualan.destroy', $sale))->assertOk();
    $this->getJson(route('manajemen-penjualan.purchase-orders'))->assertOk()->assertJsonPath('data.0.remaining_kg', 60);
});

it('requires a PO for PO sales and rejects a PO on direct sales', function () {
    $this->postJson(route('manajemen-penjualan.store'), array_merge($this->payload, ['sale_source' => 'po']))
        ->assertUnprocessable()->assertJsonValidationErrors('customer_purchase_order_id');
    $this->postJson(route('manajemen-penjualan.store'), array_merge($this->payload, [
        'sale_source' => 'direct', 'customer_purchase_order_id' => $this->po->id,
    ]))->assertUnprocessable()->assertJsonValidationErrors('customer_purchase_order_id');
    $this->postJson(route('manajemen-penjualan.store'), array_merge($this->payload, [
        'sale_source' => 'po', 'customer_purchase_order_id' => $this->po->id,
    ]))->assertUnprocessable()->assertJsonPath('message', 'Lengkapi pabrik pada PO sebelum membuat penjualan.');
    expect(SalesTransaction::count())->toBe(0);
});

it('keeps the sale PO on shipment and invoice documents', function () {
    $this->po->update(['factory_id' => $this->factory->id]);
    $this->postJson(route('manajemen-penjualan.store'), array_merge($this->payload, [
        'sale_source' => 'po', 'customer_purchase_order_id' => $this->po->id, 'stock_mode' => 'delivery_note',
    ]))->assertOk();
    $sale = SalesTransaction::firstOrFail();
    $other = CustomerPurchaseOrder::create([
        'po_number' => 'PO-OTHER', 'po_date' => '2026-10-07', 'factory_id' => $this->factory->id,
        'customer_name' => 'Pabrik Test', 'item_name' => 'Rumput Laut', 'quantity_kg' => 100, 'price_per_kg' => 10000,
    ]);
    $this->postJson(route('surat-jalan.store'), array_merge($this->shipmentPayload, [
        'sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => $other->id,
    ]))->assertUnprocessable()->assertJsonValidationErrors('customer_purchase_order_id');
    $this->postJson(route('surat-jalan.store'), array_merge($this->shipmentPayload, [
        'sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => null,
    ]))->assertOk();
    expect(DeliveryNote::firstOrFail()->customer_purchase_order_id)->toBe($this->po->id);
    $this->postJson(route('invoice.store'), array_merge($this->invoicePayload, [
        'sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => $other->id,
    ]))->assertUnprocessable()->assertJsonValidationErrors('customer_purchase_order_id');
});

it('protects a linked PO from deletion and incompatible edits', function () {
    $this->po->update(['factory_id' => $this->factory->id]);
    $this->postJson(route('manajemen-penjualan.store'), array_merge($this->payload, [
        'sale_source' => 'po', 'customer_purchase_order_id' => $this->po->id, 'sold_weight_kg' => 60,
    ]))->assertOk();
    $payload = array_merge($this->po->toArray(), ['factory_id' => $this->factory->id]);
    unset($payload['items']); // This scenario exercises the legacy single-item request.
    $this->deleteJson(route('po-konsumen.destroy', $this->po))->assertUnprocessable()->assertJsonValidationErrors('po');
    $this->putJson(route('po-konsumen.update', $this->po), array_merge($payload, ['quantity_kg' => 59]))
        ->assertUnprocessable()->assertJsonValidationErrors('quantity_kg');
    $this->putJson(route('po-konsumen.update', $this->po), array_merge($payload, ['price_per_kg' => 11000]))
        ->assertUnprocessable()->assertJsonValidationErrors('price_per_kg');
    $this->putJson(route('po-konsumen.update', $this->po), array_merge($payload, ['quantity_kg' => 120]))->assertOk();
});

it('fulfills PO grades independently and carries the grade through shipment and invoice', function () {
    $a = \App\Models\QualityGrade::create(['code' => 'A', 'name' => 'Grade A', 'is_active' => true]);
    $b = \App\Models\QualityGrade::create(['code' => 'B', 'name' => 'Grade B', 'is_active' => true]);
    Schema::create('finished_batches', function (Blueprint $table) {
        $table->id(); $table->unsignedBigInteger('inventory_id'); $table->unsignedBigInteger('quality_grade_id');
        // Include production columns so Eloquent's guardable-column cache is
        // consistent with the production schema in later tests in this process.
        $table->string('code')->nullable(); $table->unsignedBigInteger('production_run_id')->nullable();
        $table->unsignedBigInteger('warehouse_area_id')->nullable(); $table->decimal('weight_kg',12,2)->nullable();
        $table->integer('package_count')->nullable(); $table->unsignedBigInteger('cost_cents')->default(0);
        $table->unsignedBigInteger('remaining_cost_cents')->default(0);
        $table->decimal('remaining_weight_kg', 12, 2); $table->integer('remaining_packages');
        $table->timestamps();
    });
    Schema::table('sales_transactions', fn (Blueprint $table) => $table->unsignedBigInteger('finished_batch_id')->nullable());
    $this->inventory->update(['stock_stage' => 'finished']);
    $batchA = \App\Models\FinishedBatch::create(['inventory_id' => $this->inventory->id, 'quality_grade_id' => $a->id, 'remaining_weight_kg' => 1000, 'remaining_packages' => 40]);
    $batchB = \App\Models\FinishedBatch::create(['inventory_id' => $this->inventory->id, 'quality_grade_id' => $b->id, 'remaining_weight_kg' => 1000, 'remaining_packages' => 40]);
    $po = app(\App\Services\Sales\CustomerPurchaseOrderService::class)->store([
        'factory_id' => $this->factory->id, 'po_number' => 'PO-MULTI', 'po_date' => '2026-10-07',
        'items' => [
            ['quality_grade_id' => $a->id, 'quantity_kg' => 100, 'price_per_kg' => 15000],
            ['quality_grade_id' => $b->id, 'quantity_kg' => 50, 'price_per_kg' => 12000],
        ],
    ]);
    $itemA = $po->items()->where('quality_grade_id', $a->id)->firstOrFail();
    $itemB = $po->items()->where('quality_grade_id', $b->id)->firstOrFail();
    $payload = array_merge($this->payload, [
        'sale_source' => 'po', 'stock_mode' => 'delivery_note', 'customer_purchase_order_id' => $po->id,
        'customer_purchase_order_item_id' => $itemA->id, 'finished_batch_id' => $batchA->id,
        'sold_weight_kg' => 60, 'deduction_weight_kg' => 0, 'deduction_type' => 'none',
    ]);
    $this->postJson(route('manajemen-penjualan.store'), array_merge($payload, ['finished_batch_id' => $batchB->id]))
        ->assertUnprocessable()->assertJsonPath('message', 'Grade batch barang jadi harus sesuai grade pada baris PO yang dipilih.');
    $this->postJson(route('manajemen-penjualan.store'), $payload)->assertOk();
    $sale = SalesTransaction::firstOrFail();
    expect((float) $sale->price_per_kg)->toBe(15000.0)->and($sale->customer_purchase_order_item_id)->toBe($itemA->id);
    $this->postJson(route('manajemen-penjualan.store'), array_merge($payload, ['sold_weight_kg' => 41]))->assertUnprocessable();
    $this->postJson(route('manajemen-penjualan.store'), array_merge($payload, [
        'customer_purchase_order_item_id' => $itemB->id, 'finished_batch_id' => $batchB->id, 'sold_weight_kg' => 50,
    ]))->assertOk();
    expect((float) SalesTransaction::latest('id')->first()->price_per_kg)->toBe(12000.0);
    $this->postJson(route('surat-jalan.store'), array_merge($this->shipmentPayload, [
        'sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => $po->id, 'quantity_kg' => 60,
    ]))->assertOk();
    $note = DeliveryNote::firstOrFail();
    expect($note->item_name)->toBe('Rumput Laut - Grade A');
    $this->get(route('surat-jalan.print', $note))->assertOk()->assertSee('Rumput Laut - Grade A');
    $this->postJson(route('invoice.store'), array_merge($this->invoicePayload, [
        'sales_transaction_id' => $sale->id, 'customer_purchase_order_id' => $po->id,
    ]))->assertOk();
    $invoice = Invoice::firstOrFail();
    expect($invoice->item_name)->toBe('Rumput Laut - Grade A')->and($invoice->total_amount)->toBe(900000.0);
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('Rumput Laut - Grade A')->assertSee('Rp900.000,00');
    $poPayload = ['factory_id' => $this->factory->id, 'po_number' => 'PO-MULTI', 'po_date' => '2026-10-07',
        'items' => $po->items->map(fn ($item) => $item->only(['id', 'quality_grade_id', 'quantity_kg', 'price_per_kg']))->all()];
    $poPayload['items'][0]['quantity_kg'] = 59;
    $this->putJson(route('po-konsumen.update', $po), $poPayload)->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity_kg');
    $poPayload['items'] = [$poPayload['items'][1]];
    $this->putJson(route('po-konsumen.update', $po), $poPayload)->assertUnprocessable()->assertJsonValidationErrors('items');
});
