<?php

use App\Models\CustomerPurchaseOrder;
use App\Models\DeliveryNote;
use App\Models\Menu;
use App\Models\User;
use App\Models\Factory;
use App\Models\Inventory;
use App\Models\SalesTransaction;
use Database\Seeders\CustomerPurchaseOrderMenuSeeder;
use Database\Seeders\DeliveryNoteMenuSeeder;

beforeEach(function () {
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
        '2026_10_10_000002_create_company_profile_and_document_numbering.php',
        '2026_10_10_000003_add_delivery_note_template_fields.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Tester', 'email' => 'sj@example.test', 'password' => 'test-password']));
    $this->grantModuleAccess(auth()->user(), ['surat-jalan']);
    $this->payload = [
        'number' => 'SJ-2026-001', 'delivery_date' => '2026-10-06',
        'recipient_name' => 'PT Maju Jaya', 'destination_address' => 'Gudang Makassar',
        'origin' => 'Sidoarjo, Jawa Timur', 'item_name' => 'Gracilaria Kering',
        'quantity_kg' => '2000', 'packaging' => '40 bale', 'remarks' => 'Sesuai pengiriman',
        'notes' => 'Mohon periksa barang', 'sender_name' => 'Pengirim Uji', 'receiver_name' => 'Penerima Uji',
    ];
    $factory = Factory::create(['name' => 'PT Maju Jaya']);
    $inventory = Inventory::create(['item_name' => 'Gracilaria Kering', 'current_stock_kg' => 3000, 'current_sack_count' => 60]);
    $sale = SalesTransaction::create([
        'factory_id' => $factory->id, 'inventory_id' => $inventory->id,
        'invoice_number' => 'PJL-SJ-001', 'sale_date' => '2026-10-06', 'stock_mode' => 'delivery_note',
        'total_weight_kg' => 2000, 'price_per_kg' => 10000, 'total_sale_value' => 20000000,
    ]);
    $this->payload['sales_transaction_id'] = $sale->id;
});

it('creates, lists, edits, prints and deletes a delivery note', function () {
    $this->postJson(route('surat-jalan.store'), $this->payload)->assertOk();
    $note = DeliveryNote::firstOrFail();
    $this->getJson(route('surat-jalan.data'))->assertOk()->assertJsonPath('data.0.number', 'SJ-2026-001');
    $this->get(route('surat-jalan.index'))->assertOk()->assertSee('Cetak');
    $this->putJson(route('surat-jalan.update', $note), array_merge($this->payload, ['quantity_kg' => 1500]))->assertOk();
    $this->get(route('surat-jalan.print', $note))->assertOk()
        ->assertSee('PT ALBAGRO INDO SEJAHTERA')->assertSee('images/branding/albagro.png')
        ->assertSee('SJ-2026-001')->assertSee('PT Maju Jaya')->assertSee('1.500,00 kg')
        ->assertSee('40 bale')->assertSee('Pengirim Uji')->assertSee('window.print()');
    $this->deleteJson(route('surat-jalan.destroy', $note))->assertOk();
    $this->assertDatabaseMissing('delivery_notes', ['id' => $note->id]);
    $this->get(route('surat-jalan.print', $note))->assertNotFound();
});

it('validates numbers, quantities and PO references', function () {
    $this->postJson(route('surat-jalan.store'), $this->payload)->assertOk();
    $this->postJson(route('surat-jalan.store'), array_merge($this->payload, [
        'quantity_kg' => 0, 'customer_purchase_order_id' => 999,
        'delivery_date' => 'invalid', 'recipient_name' => '',
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'number', 'quantity_kg', 'customer_purchase_order_id', 'delivery_date', 'recipient_name',
    ]);
});

it('preserves the shipment snapshot when the linked PO is deleted and escapes printed input', function () {
    $po = CustomerPurchaseOrder::create([
        'po_number' => 'PO-001', 'po_date' => '2026-10-06', 'customer_name' => 'PT Maju Jaya',
        'item_name' => 'Seaweed', 'quantity_kg' => 2000, 'price_per_kg' => 10000,
    ]);
    $this->get(route('surat-jalan.index'))->assertOk()->assertSee('PO-001');
    $this->postJson(route('surat-jalan.store'), array_merge($this->payload, [
        'customer_purchase_order_id' => $po->id, 'notes' => '<script>alert(1)</script>',
    ]))->assertOk();
    $note = DeliveryNote::firstOrFail();
    $po->delete();
    expect($note->fresh()->customer_purchase_order_id)->toBeNull();
    $this->get(route('surat-jalan.print', $note))->assertOk()->assertSee('PT Maju Jaya')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('places Surat jalan after PO and protects all endpoints with login', function () {
    $this->seed(CustomerPurchaseOrderMenuSeeder::class);
    $this->seed(DeliveryNoteMenuSeeder::class);
    $this->seed(DeliveryNoteMenuSeeder::class);
    $parent = Menu::where('name', 'Penjualan')->firstOrFail();
    expect($parent->children->pluck('name')->all())->toBe(['PO konsumen', 'Surat jalan']);
    auth()->logout();
    $this->get(route('surat-jalan.index'))->assertRedirect(route('login'));
    $this->getJson(route('surat-jalan.data'))->assertUnauthorized();
    $this->postJson(route('surat-jalan.store'), $this->payload)->assertUnauthorized();
    $this->putJson(route('surat-jalan.update', 1), $this->payload)->assertUnauthorized();
    $this->deleteJson(route('surat-jalan.destroy', 1))->assertUnauthorized();
    $this->getJson(route('surat-jalan.print', 1))->assertUnauthorized();
});

it('automatically numbers shipments and stores optional transport and weighing details', function () {
    $payload = array_merge($this->payload, [
        'number' => '', 'vehicle_plate' => 'KT 1234 AB', 'vehicle_type' => 'Truk', 'driver_name' => 'Sopir Uji',
        'condition' => 'Kering', 'gross_weight_kg' => 2020, 'tare_weight_kg' => 20, 'moisture_percent' => 25.5,
    ]);
    $this->postJson(route('surat-jalan.store'), $payload)->assertOk();
    $note = DeliveryNote::firstOrFail();
    expect($note->number)->toBe('SJ/0001/10/2026')->and($note->vehicle_plate)->toBe('KT 1234 AB')
        ->and((float) $note->moisture_percent)->toBe(25.5);
    $this->get(route('surat-jalan.print', $note))->assertOk()->assertSee('KT 1234 AB')->assertSee('Sopir Uji')
        ->assertSee('2.020,00')->assertSee('NETTO (KG)');
    $this->postJson(route('surat-jalan.store'), array_merge($payload, ['moisture_percent' => 101]))
        ->assertUnprocessable()->assertJsonValidationErrors('moisture_percent');
    expect(DeliveryNote::count())->toBe(1);
});
