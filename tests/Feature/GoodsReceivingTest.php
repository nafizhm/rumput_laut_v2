<?php

use App\Models\Factory;
use App\Models\Farmer;
use App\Models\GoodsReceipt;
use App\Models\Inventory;
use App\Models\MaterialBatch;
use App\Models\Menu;
use App\Models\QualityGrade;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseArea;
use App\Services\Sidebar\SidebarService;
use App\Services\Transaction\HarvestTransactionService;
use Database\Seeders\GoodsReceivingSeeder;
use Database\Seeders\QualityGradeSeeder;
use Database\Seeders\WarehouseNavigationSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-07 12:00:00', 'Asia/Jakarta'));
    foreach ([
        '0001_01_01_000000_create_users_table.php', '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php', '2026_04_26_062312_create_farmers_table.php',
        '2026_04_26_062518_create_loans_table.php', '2026_04_26_062526_create_harvest_transactions_table.php',
        '2026_04_26_062535_create_farmer_ledgers_table.php', '2026_04_26_153147_create_inventories_table.php',
        '2026_04_26_153352_create_factories_table.php', '2026_04_26_153448_create_sales_transactions_table.php',
        '2026_04_26_153534_create_stock_movements_table.php',
        '2026_05_02_000002_add_sale_deduction_fields_to_harvest_transactions_table.php',
        '2026_05_02_000003_add_payment_fields_to_sales_transactions_table.php',
        '2026_10_06_000001_create_customer_purchase_orders_table.php', '2026_10_06_000004_create_delivery_notes_table.php',
        '2026_10_06_000006_create_invoices_table.php', '2026_10_06_000008_add_po_reference_to_invoices.php',
        '2026_10_06_225852_create_invoice_payments_table.php', '2026_10_07_000005_link_invoices_to_sales.php',
        '2026_10_07_000006_add_delivery_confirmation_stock_flow.php', '2026_10_07_000007_create_quality_grades_table.php',
        '2026_10_07_000009_create_goods_receiving_flow.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    // Existing May schema migration contains a MySQL-specific ALTER.
    Schema::table('harvest_transactions', fn (Blueprint $t) => $t->text('notes')->nullable());
    Role::create(['name' => 'owner', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Menu::create(['name' => 'Master', 'is_active' => true]);
    Menu::create(['name' => 'Manajemen Gudang', 'is_active' => true]);
    $this->seed(QualityGradeSeeder::class);
    $this->seed(GoodsReceivingSeeder::class);
    $this->user = User::create(['name' => 'Owner', 'email' => 'receiving@example.test', 'password' => 'password']);
    $this->user->assignRole('owner');
    $this->actingAs($this->user);
    $this->withoutVite();
    $this->farmer = Farmer::create(['name' => 'Petani Test', 'current_debt' => 100000]);
    $this->inventory = Inventory::where('stock_stage', 'material')->firstOrFail();
    $this->grade = QualityGrade::where('code', 'Q01')->firstOrFail();
    $this->area = WarehouseArea::where('code', 'BM-A')->firstOrFail();
    $this->payload = [
        'received_date' => '2026-10-07', 'farmer_id' => $this->farmer->id, 'inventory_id' => $this->inventory->id,
        'source_reference' => 'NOTA-001', 'note_weight_kg' => 3400,
        'lines' => [['quality_grade_id' => $this->grade->id, 'warehouse_area_id' => $this->area->id, 'actual_weight_kg' => 3386, 'sack_count' => 64]],
    ];
    $this->harvestPayload = [
        'farmer_id' => $this->farmer->id, 'harvest_date' => '2026-10-07', 'location' => 'Lokasi Test',
        'total_weight' => 3386, 'sack_count' => 64, 'extra_per_sack' => 1, 'price_per_kg' => 100, 'cash_payout' => 338600,
    ];
});
afterEach(function () {
    Carbon::setTestNow();
});

it('creates draft and confirms actual incoming weight and batches once', function () {
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $receipt = GoodsReceipt::firstOrFail();
    expect($receipt->status)->toBe('draft')->and(MaterialBatch::count())->toBe(0)->and(StockMovement::count())->toBe(0);
    $this->get(route('penerimaan-barang.create'))->assertOk()->assertSee('Rincian Barang Datang');
    $this->get(route('penerimaan-barang.edit', $receipt))->assertOk();
    $this->post(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertRedirect(route('penerimaan-barang.show', $receipt));
    expect($receipt->refresh()->status)->toBe('confirmed')->and($receipt->confirmed_by)->toBe($this->user->id)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(3386.0)->and($this->inventory->current_sack_count)->toBe(64)
        ->and(MaterialBatch::count())->toBe(1)->and(MaterialBatch::first()->remaining_sacks)->toBe(64)
        ->and(StockMovement::count())->toBe(1)->and((float) StockMovement::first()->discrepancy_kg)->toBe(14.0);
    $this->postJson(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertUnprocessable();
    $this->putJson(route('penerimaan-barang.update', $receipt), $this->payload)->assertUnprocessable();
    $this->deleteJson(route('penerimaan-barang.destroy', $receipt))->assertUnprocessable();
    $this->get(route('penerimaan-barang.show', $receipt))->assertOk()->assertSee('Grade A')->assertSee('BM-A');
    expect(StockMovement::count())->toBe(1)->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(3386.0);
});

it('records several quality areas from a single receipt without multiplying stock', function () {
    $payload = $this->payload;
    $payload['lines'][0]['actual_weight_kg'] = 2000;
    $payload['lines'][0]['sack_count'] = 40;
    $payload['lines'][] = ['quality_grade_id' => QualityGrade::where('code', 'Q02')->value('id'), 'warehouse_area_id' => WarehouseArea::where('code', 'BM-B')->value('id'), 'actual_weight_kg' => 1386, 'sack_count' => 24];
    $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect();
    $receipt = GoodsReceipt::first();
    $this->post(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertRedirect();
    expect(MaterialBatch::count())->toBe(2)->and(MaterialBatch::sum('incoming_sacks'))->toBe(64)
        ->and(StockMovement::count())->toBe(1)->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(3386.0);
});

it('rejects incorrect areas and rechecks inactive quality at confirmation', function () {
    $bad = $this->payload;
    $bad['lines'][0]['warehouse_area_id'] = WarehouseArea::where('code', 'BM-B')->value('id');
    $this->postJson(route('penerimaan-barang.store'), $bad)->assertUnprocessable();
    expect(GoodsReceipt::count())->toBe(0);
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $this->grade->update(['is_active' => false]);
    $this->postJson(route('penerimaan-barang.confirm', GoodsReceipt::first()), ['confirm' => 1])->assertUnprocessable();
    expect(MaterialBatch::count())->toBe(0)->and(StockMovement::count())->toBe(0)->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(0.0);
});

it('separates purchasing from physical receiving and prevents a second receipt for the purchase', function () {
    Inventory::create(['item_name' => 'Gudang Basah', 'current_stock_kg' => 500, 'current_sack_count' => 10]);
    $harvest = app(HarvestTransactionService::class)->createHarvest($this->harvestPayload);
    expect($harvest->receipt_mode)->toBe('separate')->and(StockMovement::count())->toBe(0)
        ->and((float) Inventory::where('item_name', 'Gudang Basah')->value('current_stock_kg'))->toBe(500.0);
    $payload = $this->payload;
    unset($payload['source_reference']);
    $payload['harvest_transaction_id'] = $harvest->id;
    $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect();
    $this->postJson(route('penerimaan-barang.store'), $payload)->assertUnprocessable();
    $this->post(route('penerimaan-barang.confirm', GoodsReceipt::first()), ['confirm' => 1])->assertRedirect();
    expect((bool) $harvest->refresh()->is_received)->toBeTrue()->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(3386.0)
        ->and((float) $harvest->warehouse_weight)->toBe(3450.0);
    expect(fn () => app(HarvestTransactionService::class)->deleteHarvest($harvest))->toThrow(ValidationException::class);
    $harvest->receipt_mode = 'legacy';
    $harvest->is_received = false;
    $harvest->save();
    $payload['source_reference'] = 'NEW-REFERENCE';
    $this->postJson(route('penerimaan-barang.store'), $payload)->assertUnprocessable();
});

it('shows clickable warehouse stages and farmer receiving history without guessing production figures', function () {
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $this->post(route('penerimaan-barang.confirm', GoodsReceipt::first()), ['confirm' => 1])->assertRedirect();
    $this->get(route('manajemen-gudang.index'))->assertOk()->assertSee('Barang Datang')->assertSee('Press + Timbang + Packing')->assertSee('Saldo Gudang Lama');
    $this->get(route('manajemen-gudang.stage', 'incoming'))->assertOk()->assertSee('Petani Test')->assertSee('Grade A')->assertSee('3.386,00');
    foreach (['process', 'wip', 'press', 'finished'] as $stage) {
        $this->get(route('manajemen-gudang.stage', $stage))->assertOk()->assertSee('Belum ada')->assertDontSee('Pencatatan tahap ini belum tersedia');
    }
    $this->get(route('penerimaan-barang.index', ['farmer_id' => $this->farmer->id]))->assertOk()->assertSee('Petani Test');
    $this->get(route('area-gudang.index'))->assertOk()->assertSee('BM-A');
    $this->get(route('area-gudang.edit', $this->area))->assertOk();
});

it('preserves seed edits and requires permission and explicit confirmation', function () {
    $this->area->update(['name' => 'Area Client']);
    $this->inventory->update(['current_stock_kg' => 123]);
    $this->seed(GoodsReceivingSeeder::class);
    expect($this->area->refresh()->name)->toBe('Area Client')->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(123.0)
        ->and(Menu::where('route', 'penerimaan-barang.index')->count())->toBe(1);
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $receipt = GoodsReceipt::first();
    $this->postJson(route('penerimaan-barang.confirm', $receipt), ['confirm' => false])->assertUnprocessable();
    $this->user->removeRole('owner');
    $this->get(route('penerimaan-barang.index'))->assertForbidden();
    $this->postJson(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertForbidden();
    $this->postJson(route('area-gudang.store'), [])->assertForbidden();
});

it('prevents raw materials from being sold or changed through obsolete stock entry points', function () {
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $this->post(route('penerimaan-barang.confirm', GoodsReceipt::first()), ['confirm' => 1])->assertRedirect();
    $factory = Factory::create(['name' => 'Pabrik Test']);
    $this->postJson(route('manajemen-penjualan.store'), [
        'factory_id' => $factory->id, 'inventory_id' => $this->inventory->id, 'sale_date' => '2026-10-07',
        'sold_weight_kg' => 100, 'price_per_kg' => 10000,
    ])->assertUnprocessable()->assertJsonPath('message', 'Bahan masuk belum menjadi barang jadi dan tidak dapat dijual.');
    $this->postJson(route('manajemen-gudang.receive'), [
        'inventory_id' => $this->inventory->id, 'movement_date' => '2026-10-07', 'actual_weight_kg' => 100, 'actual_sacks' => 2,
    ])->assertUnprocessable();
    $this->postJson(route('manajemen-gudang.adjustment'), [
        'inventory_id' => $this->inventory->id, 'movement_date' => '2026-10-07', 'adjustment_weight_kg' => -100, 'description' => 'Koreksi manual',
    ])->assertUnprocessable();
    expect((float) $this->inventory->refresh()->current_stock_kg)->toBe(3386.0)->and(MaterialBatch::first()->remaining_sacks)->toBe(64)->and(StockMovement::count())->toBe(1);
});

it('requires distinct source references and protects area location once receiving uses it', function () {
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $this->postJson(route('penerimaan-barang.store'), $this->payload)->assertUnprocessable()->assertJsonValidationErrors('source_reference');
    $otherInventory = Inventory::create(['item_name' => 'Gudang Lain']);
    $this->putJson(route('area-gudang.update', $this->area), [
        'code' => $this->area->code, 'name' => $this->area->name, 'inventory_id' => $otherInventory->id,
        'usage' => 'receiving', 'is_active' => true, 'grade_ids' => [$this->grade->id],
    ])->assertUnprocessable()->assertJsonValidationErrors('inventory_id');
    $receipt = GoodsReceipt::first();
    $this->delete(route('penerimaan-barang.destroy', $receipt))->assertRedirect();
    expect(GoodsReceipt::count())->toBe(0)->and(MaterialBatch::count())->toBe(0)->and(StockMovement::count())->toBe(0);
});

it('shows warehouse dashboard first and moves income and expenses under finance', function () {
    $warehouse = Menu::whereNull('parent_id')->where('name', 'Manajemen Gudang')->firstOrFail();
    Menu::create(['name' => 'Pemasukan & Pengeluaran', 'route' => 'manajemen-pemasukan-pengeluaran.index', 'parent_id' => $warehouse->id, 'is_active' => true]);
    $this->seed(WarehouseNavigationSeeder::class);
    $this->seed(WarehouseNavigationSeeder::class);
    expect($warehouse->fresh()->children->first()->name)->toBe('Dashboard Gudang')
        ->and(Menu::where('route', 'manajemen-gudang.index')->count())->toBe(1)
        ->and(Menu::where('route', 'manajemen-pemasukan-pengeluaran.index')->first()->parent->name)->toBe('Keuangan');
    $this->user->removeRole('owner');
    $this->user->givePermissionTo('receiving.read');
    $this->get(route('manajemen-gudang.index', ['inventory_id' => $this->inventory->id]))->assertOk()
        ->assertSee('Dashboard Gudang')->assertSee('Barang Datang sampai Barang Jadi')
        ->assertSee('Barang Datang')->assertSee('Press + Timbang + Packing');
    $menus = SidebarService::getSidebarMenu();
    expect($menus->firstWhere('name', 'Manajemen Gudang')->children->pluck('name')->all())->toContain('Dashboard Gudang');
});

it('summarizes confirmed batches per area and keeps received kg separate from remaining sacks', function () {
    $payload = $this->payload;
    $payload['lines'][0]['actual_weight_kg'] = 2000;
    $payload['lines'][0]['sack_count'] = 40;
    $payload['lines'][] = ['quality_grade_id' => QualityGrade::where('code', 'Q02')->value('id'), 'warehouse_area_id' => WarehouseArea::where('code', 'BM-B')->value('id'), 'actual_weight_kg' => 1386, 'sack_count' => 24];
    $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect();
    $dashboard = $this->get(route('manajemen-gudang.index'))->assertOk();
    $before = $dashboard->viewData('areas')->firstWhere('code', 'BM-A');
    expect($before->batch_count)->toBe(0)->and($before->remaining_sacks)->toBeNull();
    $this->post(route('penerimaan-barang.confirm', GoodsReceipt::first()), ['confirm' => 1])->assertRedirect();
    // Model a future process consuming part of the sacks: do not proportionally
    // estimate kilograms or hide stock because an area is inactive.
    MaterialBatch::whereHas('line', fn ($q) => $q->where('warehouse_area_id', $this->area->id))->update(['remaining_sacks' => 20]);
    $this->area->update(['is_active' => false]);
    $dashboard = $this->get(route('manajemen-gudang.index'))->assertOk()->assertSee('Jumlah Barang per Area');
    $areaA = $dashboard->viewData('areas')->firstWhere('code', 'BM-A');
    $areaB = $dashboard->viewData('areas')->firstWhere('code', 'BM-B');
    expect($areaA->batch_count)->toBe(1)->and((int) $areaA->remaining_sacks)->toBe(20)
        ->and((float) $areaA->received_weight)->toBe(2000.0)
        ->and((int) $areaB->remaining_sacks)->toBe(24)->and((float) $areaB->received_weight)->toBe(1386.0);
    $detail = $this->get(route('manajemen-gudang.area', $this->area))->assertOk()->assertSee('BM-A')->assertSee('2.000,00');
    expect($detail->viewData('batches')->count())->toBe(1)->and($detail->viewData('batches')->sum('remaining_sacks'))->toBe(20);
    $this->get(route('manajemen-gudang.area',999999))->assertNotFound();
});
