<?php

use App\Models\CustomerPurchaseOrder;
use App\Models\Menu;
use App\Models\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Database\Seeders\CustomerPurchaseOrderMenuSeeder;

beforeEach(function () {
    // Use only this feature's schema: older stock migrations require MySQL ENUM syntax.
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php',
        '2026_04_26_153352_create_factories_table.php',
        '2026_10_06_000001_create_customer_purchase_orders_table.php',
        '2026_10_06_000003_add_factory_and_file_to_customer_purchase_orders.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'PO Tester', 'email' => 'po@example.test', 'password' => 'test-password']));
    Storage::fake('local');
    $this->factory = Factory::create(['name' => 'Pabrik Uji', 'phone' => '08123456', 'address' => 'Alamat pabrik']);
    $this->payload = [
        'factory_id' => $this->factory->id,
        'po_number' => 'PO-001', 'po_date' => '2026-10-06',
        'customer_name' => 'Konsumen Uji', 'item_name' => 'Rumput Laut Kering',
        'quantity_kg' => '12.50', 'price_per_kg' => '20000',
        'delivery_date' => '2026-10-08', 'notes' => 'Pesanan uji',
    ];
});

it('creates, lists, edits and deletes a consumer PO', function () {
    $this->postJson(route('po-konsumen.store'), $this->payload)->assertOk();
    $order = CustomerPurchaseOrder::firstOrFail();
    $this->getJson(route('po-konsumen.data'))->assertOk()
        ->assertJsonPath('data.0.po_number', 'PO-001')
        ->assertJsonPath('data.0.po_date', '2026-10-06')
        ->assertJsonPath('data.0.total_amount', 250000);
    $this->get(route('po-konsumen.index'))->assertOk()->assertSee('PO konsumen');
    $this->putJson(route('po-konsumen.update', $order), array_merge($this->payload, [
        'quantity_kg' => '20', 'total_amount' => 1,
    ]))->assertOk();
    expect($order->fresh()->total_amount)->toBe(400000.0);
    $this->deleteJson(route('po-konsumen.destroy', $order))->assertOk();
    $this->assertDatabaseMissing('customer_purchase_orders', ['id' => $order->id]);
    $this->deleteJson(route('po-konsumen.destroy', $order))->assertNotFound();
});

it('rejects duplicate numbers and invalid quantities or dates', function () {
    $this->postJson(route('po-konsumen.store'), $this->payload)->assertOk();
    $this->postJson(route('po-konsumen.store'), $this->payload)
        ->assertUnprocessable()->assertJsonValidationErrors('po_number');
    $this->postJson(route('po-konsumen.store'), array_merge($this->payload, [
        'po_number' => 'PO-002', 'quantity_kg' => 0, 'price_per_kg' => -10,
        'delivery_date' => '2026-10-01', 'factory_id' => 99999,
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'quantity_kg', 'price_per_kg', 'delivery_date', 'factory_id',
    ]);
    $this->assertDatabaseCount('customer_purchase_orders', 1);
});

it('places the menu after dashboard without duplicating menus on rerun', function () {
    Menu::create(['name' => 'Dashboard', 'route' => 'dashboard', 'order' => 1]);
    Menu::create(['name' => 'Transaksi', 'order' => 2]);
    $this->seed(CustomerPurchaseOrderMenuSeeder::class);
    $this->seed(CustomerPurchaseOrderMenuSeeder::class);
    expect(Menu::whereNull('parent_id')->orderBy('order')->pluck('name')->all())
        ->toBe(['Dashboard', 'Penjualan', 'Transaksi']);
    $this->assertDatabaseCount('menus', 4);
    $this->get(route('po-konsumen.index'))->assertOk()->assertSee('Penjualan');
});

it('requires login for every PO endpoint', function () {
    auth()->logout();
    $this->get(route('po-konsumen.index'))->assertRedirect(route('login'));
    $this->getJson(route('po-konsumen.data'))->assertUnauthorized();
    $this->postJson(route('po-konsumen.store'), $this->payload)->assertUnauthorized();
    $this->putJson(route('po-konsumen.update', 1), $this->payload)->assertUnauthorized();
    $this->deleteJson(route('po-konsumen.destroy', 1))->assertUnauthorized();
});


it('copies consumer details from the selected factory and ignores posted contact details', function () {
    $this->postJson(route('po-konsumen.store'), array_merge($this->payload, [
        'customer_name' => 'Forged name', 'customer_phone' => 'wrong', 'customer_address' => 'wrong',
    ]))->assertOk();
    $order = CustomerPurchaseOrder::firstOrFail();
    expect($order->customer_name)->toBe('Pabrik Uji');
    expect($order->customer_phone)->toBe('08123456');
    expect($order->customer_address)->toBe('Alamat pabrik');
    $factory = Factory::create(['name' => 'Pabrik Baru', 'phone' => null, 'address' => null]);
    $this->putJson(route('po-konsumen.update', $order), array_merge($this->payload, ['factory_id' => $factory->id]))->assertOk();
    expect($order->fresh()->customer_name)->toBe('Pabrik Baru');
    expect($order->fresh()->customer_phone)->toBeNull();
});

it('uploads, previews, retains, replaces and removes a PO attachment', function () {
    $this->post(route('po-konsumen.store'), array_merge($this->payload, [
        'po_file' => UploadedFile::fake()->create('po.pdf', 10, 'application/pdf'),
    ]))->assertOk();
    $order = CustomerPurchaseOrder::firstOrFail();
    $original = $order->po_file_path;
    Storage::disk('local')->assertExists($original);
    $this->get(route('po-konsumen.file', $order))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->putJson(route('po-konsumen.update', $order), $this->payload)->assertOk();
    expect($order->fresh()->po_file_path)->toBe($original);
    $this->post(route('po-konsumen.update', $order), array_merge($this->payload, [
        '_method' => 'PUT', 'po_file' => UploadedFile::fake()->image('replacement.png'),
    ]))->assertOk();
    Storage::disk('local')->assertMissing($original);
    $replacement = $order->fresh()->po_file_path;
    Storage::disk('local')->assertExists($replacement);
    $this->putJson(route('po-konsumen.update', $order), array_merge($this->payload, ['remove_po_file' => true]))->assertOk();
    Storage::disk('local')->assertMissing($replacement);
    expect($order->fresh()->po_file_url)->toBeNull();
    $this->get(route('po-konsumen.file', $order))->assertNotFound();
});

it('rejects invalid uploads and deletes the attachment with its PO', function () {
    foreach ([UploadedFile::fake()->create('large.pdf', 4097, 'application/pdf'), UploadedFile::fake()->create('bad.txt', 1, 'text/plain')] as $file) {
        $this->postJson(route('po-konsumen.store'), array_merge($this->payload, ['po_file' => $file]))
            ->assertUnprocessable()->assertJsonValidationErrors('po_file');
    }
    $this->assertDatabaseCount('customer_purchase_orders', 0);
    $this->postJson(route('po-konsumen.store'), array_merge($this->payload, [
        'po_file' => UploadedFile::fake()->image('po.png'),
    ]))->assertOk();
    $order = CustomerPurchaseOrder::firstOrFail();
    $path = $order->po_file_path;
    $this->deleteJson(route('po-konsumen.destroy', $order))->assertOk();
    Storage::disk('local')->assertMissing($path);
    auth()->logout();
    $this->getJson(route('po-konsumen.file', $order))->assertUnauthorized();
});
