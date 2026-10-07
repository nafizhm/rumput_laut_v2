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
        '2026_10_07_000006_add_delivery_confirmation_stock_flow.php',
        '2026_10_07_000007_create_quality_grades_table.php',
        '2026_10_07_000009_create_goods_receiving_flow.php',
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
        ->and($oldMenu->name)->toBe('Penjualan Langsung')
        ->and($historical->refresh()->invoice_number)->toBe('INV-LAMA-001')
        ->and($historical->remaining_amount)->toBe(0)
        ->and($historical->paid_amount)->toBeNull();
    $this->get(route('manajemen-penjualan.index'))->assertOk()->assertSee('Penjualan Langsung')->assertSee('INV-LAMA-001')->assertSee('Gudang Basah');
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
    $this->get(route('invoice.index'))->assertOk()->assertSee('Penjualan yang Ditagihkan');
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
    auth()->user()->revokePermissionTo('manajemen-penjualan.create');
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
