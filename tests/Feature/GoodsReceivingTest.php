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
        '2026_10_10_000007_add_harvest_submission_key.php',
        '2026_10_07_000002_create_chart_of_accounts_table.php',
        '2026_10_10_000008_separate_harvest_receiving_and_payments.php',
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
    $this->grantModuleAccess($this->user, ['manajemen-gudang', 'manajemen-petani', 'manajemen-penjualan']);
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

it('splits receipt history by area and keeps unsupported legacy locations unknown', function () {
    prepareOpeningStockSchema();
    $second = WarehouseArea::create(['code' => 'HISTORY-IN', 'name' => 'Area Penerimaan Kedua', 'inventory_id' => $this->inventory->id, 'usage' => 'receiving', 'is_active' => true]);
    $payload = $this->payload;
    $payload['lines'] = [
        ['warehouse_area_id' => $this->area->id, 'quality_grade_id' => $this->grade->id, 'actual_weight_kg' => 100, 'sack_count' => 2],
        ['warehouse_area_id' => $second->id, 'quality_grade_id' => $this->grade->id, 'actual_weight_kg' => 200, 'sack_count' => 4],
    ];
    $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $receipt = GoodsReceipt::sole();
    $this->post(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertRedirect()->assertSessionHasNoErrors();
    StockMovement::create(['inventory_id' => $this->inventory->id, 'movement_date' => '2026-10-06', 'direction' => 'in', 'type' => 'in', 'weight_kg' => 10, 'sack_count' => 1, 'balance_after_kg' => 10, 'description' => 'Data lama']);
    (require database_path('migrations/2026_10_15_000001_create_stock_movement_locations.php'))->up();
    $service = app(\App\Services\Transaction\WarehouseHistoryService::class);
    $rows = $service->movements(['area_id' => $second->id]);
    expect($rows)->toHaveCount(1)->and($rows[0]['weight'])->toBe(200.0)->and($rows[0]['packages'])->toBe(4)
        ->and($rows[0]['inferred'])->toBeTrue()->and($service->movements([]))->toHaveCount(3)
        ->and($service->movements([])->last()['area'])->toBe('Area belum tercatat');
    // New receipts capture locations immediately, without inferring history.
    $payload['source_reference'] = 'NOTA-NEW';
    $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $receipt = GoodsReceipt::latest('id')->firstOrFail();
    $this->post(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertRedirect()->assertSessionHasNoErrors();
    expect($service->movements(['area_id' => $second->id])->first()['inferred'])->toBeFalse();
});

it('captures source areas for production and material stock adjustments', function () {
    prepareOpeningStockSchema();
    (require database_path('migrations/2026_10_15_000001_create_stock_movement_locations.php'))->up();
    app(\App\Services\Transaction\OpeningStockService::class)->save([
        'token' => (string) \Illuminate\Support\Str::uuid(), 'stock_date' => '2026-10-06', 'usage' => 'receiving',
        'items' => [['warehouse_area_id' => $this->area->id, 'weight_kg' => 100, 'package_count' => 4]],
    ], $this->user);
    $ready = WarehouseArea::where('usage', 'finished')->where('is_active', true)->firstOrFail();
    app(\App\Services\Transaction\ProductionService::class)->recordDaily([
        'date' => '2026-10-07', 'sources' => [['warehouse_area_id' => $this->area->id, 'sack_count' => 2]],
        'outputs' => [['warehouse_area_id' => $ready->id, 'weight_kg' => 40, 'package_count' => 2]],
    ], $this->user);
    $history = app(\App\Services\Transaction\WarehouseHistoryService::class);
    $production = $history->movements(['area_id' => $this->area->id])->first();
    expect($production['activity'])->toBe('Bahan ke proses')->and($production['weight'])->toBe(50.0);
    expect($history->movements(['area_id' => $ready->id])->first()['weight'])->toBe(40.0);
    $service = app(\App\Services\Transaction\AreaStockAdjustmentService::class);
    $item = collect($service->areas())->flatMap(fn ($area) => $area['items'])->firstWhere('kind', 'material');
    $item['actual_weight'] = 45;
    $service->save(['movement_date' => '2026-10-07', 'description' => 'Selisih timbang', 'items' => [$item]]);
    $adjustment = $history->movements(['area_id' => $this->area->id])->first();
    expect($adjustment['activity'])->toBe('Penyesuaian')->and($adjustment['weight'])->toBe(5.0)->and($adjustment['direction'])->toBe('out');
});

it('shows and saves integrated harvest receiving with only harvest read and create access', function () {
    enableHarvestFinance($this);
    $user = User::create(['name' => 'Gudang Terbatas', 'email' => 'harvest-only@example.test', 'password' => 'password']);
    foreach (['manajemen-panen.read', 'manajemen-panen.create'] as $name) {
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($name, 'web'));
    }
    $this->actingAs($user)->get(route('manajemen-panen.index'))->assertOk()->assertSee('Tambah Penerimaan Panen');
    expect($user->can('receiving.create'))->toBeFalse()->and($user->can('receiving.confirm'))->toBeFalse();
    $this->postJson(route('manajemen-panen.store'), unifiedHarvestPayload($this))->assertOk();
    expect(GoodsReceipt::firstOrFail()->status)->toBe('confirmed')->and(MaterialBatch::count())->toBe(1);
    $user->revokePermissionTo('manajemen-panen.create');
    $this->get(route('manajemen-panen.index'))->assertOk()->assertDontSee('Tambah Penerimaan Panen');
    $this->postJson(route('manajemen-panen.store'), unifiedHarvestPayload($this))->assertForbidden();
    $this->get(route('harvest-finance.index'))->assertForbidden();
});

it('shows and saves daily production with create access without separate confirmation access', function () {
    prepareOpeningStockSchema();
    $area = WarehouseArea::where('usage', 'finished')->where('is_active', true)->firstOrFail();
    app(\App\Services\Transaction\OpeningStockService::class)->save([
        'token' => (string) \Illuminate\Support\Str::uuid(), 'stock_date' => '2026-10-06', 'usage' => 'receiving',
        'items' => [['warehouse_area_id' => $this->area->id, 'weight_kg' => 100, 'package_count' => 4]],
    ], $this->user);
    $user = User::create(['name' => 'Proses Terbatas', 'email' => 'production-only@example.test', 'password' => 'password']);
    foreach (['production.read', 'production.create'] as $name) {
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($name, 'web'));
    }
    $this->actingAs($user)->get(route('produksi.index'))->assertOk()->assertSee('Catat Proses Harian');
    $this->get(route('produksi.create'))->assertOk();
    expect($user->can('production.confirm'))->toBeFalse();
    $this->post(route('produksi.store'), ['date' => '2026-10-07',
        'sources' => [['warehouse_area_id' => $this->area->id, 'sack_count' => 2]],
        'outputs' => [['warehouse_area_id' => $area->id, 'weight_kg' => 40, 'package_count' => 2]],
    ])->assertRedirect(route('produksi.index'))->assertSessionHasNoErrors();
    expect((int) MaterialBatch::sum('remaining_sacks'))->toBe(2)->and((float) \App\Models\FinishedBatch::sum('weight_kg'))->toBe(40.0);
});

function prepareOpeningStockSchema(): void
{
    foreach (['2026_10_07_000012_create_accounting_journals.php', '2026_10_07_000015_connect_production_and_operational_accounting.php',
        '2026_10_10_000010_enable_independent_warehouse_processing.php', '2026_10_11_000002_use_warehouse_locations.php',
        '2026_10_12_000004_allow_legacy_area_transfers.php', '2026_10_12_000005_create_opening_stock_entries.php',
        '2026_10_12_000006_make_opening_stock_area_based.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    (new \Database\Seeders\WarehouseLocationSeeder)->run();
}

it('records opening receiving stock without a purchase and prevents repeated submission', function () {
    prepareOpeningStockSchema();
    $payload = ['token' => (string) \Illuminate\Support\Str::uuid(), 'stock_date' => '2026-10-06',
        'usage' => 'receiving', 'items' => [['warehouse_area_id' => $this->area->id, 'weight_kg' => 250.75, 'package_count' => 5]]];
    $this->get(route('stok-awal.index'))->assertOk()->assertSee('Gudang Penerimaan')->assertSee('Gudang Ready')->assertDontSee('Kualitas Barang')->assertDontSee('name="quality_grade_id"', false);
    $this->post(route('stok-awal.store'), $payload)->assertRedirect(route('stok-awal.index'))->assertSessionHasNoErrors();
    $batch = MaterialBatch::firstOrFail();
    expect((float) $batch->remaining_weight_kg)->toBe(250.75)
        ->and($batch->line->receipt->status)->toBe('confirmed')
        ->and($batch->line->receipt->farmer_id)->toBeNull()
        ->and($batch->line->quality_grade_id)->toBeNull()
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(250.75)
        ->and((int) $this->inventory->current_sack_count)->toBe(5)
        ->and(StockMovement::count())->toBe(1)
        ->and(\Illuminate\Support\Facades\DB::table('journal_entries')->count())->toBe(0);
    $this->post(route('stok-awal.store'), $payload)->assertSessionHasErrors('token');
    expect(MaterialBatch::count())->toBe(1);
    $this->get(route('manajemen-gudang.index'))->assertOk();
});

it('makes opening ready stock available to sales without a grade or counting it as daily production', function () {
    prepareOpeningStockSchema();
    $area = WarehouseArea::where('usage', 'finished')->firstOrFail();
    $payload = ['token' => (string) \Illuminate\Support\Str::uuid(), 'stock_date' => '2026-10-06',
        'usage' => 'finished', 'items' => [['warehouse_area_id' => $area->id, 'weight_kg' => 100, 'package_count' => 4]]];
    $this->post(route('stok-awal.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $batch = \App\Models\FinishedBatch::firstOrFail();
    $this->get(route('manajemen-penjualan.batches'))->assertOk()->assertJsonFragment(['id' => $batch->id]);
    $dashboard = $this->get(route('manajemen-gudang.index'))->assertOk();
    expect((float) $dashboard->viewData('finishedWeight'))->toBe(100.0)
        ->and((float) $dashboard->viewData('packedWeight'))->toBe(0.0)
        ->and($dashboard->viewData('stageCounts')['process'])->toBe(0);
    $allocation = app(\App\Services\Transaction\ProductionService::class)->consumeFinished($batch->id, $area->inventory_id, 25, 1, '2026-10-07');
    expect($allocation['finished_batch_id'])->toBe($batch->id)
        ->and($batch->quality_grade_id)->toBeNull()
        ->and((float) $batch->refresh()->remaining_weight_kg)->toBe(75.0)
        ->and((int) $batch->remaining_packages)->toBe(3);
});

it('rejects future dates and inactive areas for opening stock without changing stock', function () {
    prepareOpeningStockSchema();
    $payload = ['token' => (string) \Illuminate\Support\Str::uuid(), 'stock_date' => '2026-10-08',
        'usage' => 'receiving', 'items' => [['warehouse_area_id' => $this->area->id, 'weight_kg' => 100, 'package_count' => 4]]];
    $this->post(route('stok-awal.store'), $payload)->assertSessionHasErrors('stock_date');
    $payload['stock_date'] = '2026-10-07';
    $this->area->update(['is_active' => false]);
    $this->post(route('stok-awal.store'), $payload)->assertSessionHasErrors('items.0.warehouse_area_id');
    expect(MaterialBatch::count())->toBe(0)->and(StockMovement::count())->toBe(0);
});

it('restricts opening stock input to users with its permission', function () {
    prepareOpeningStockSchema();
    $user = User::create(['name' => 'Viewer', 'email' => 'opening-viewer@example.test', 'password' => 'password']);
    $this->actingAs($user)->get(route('stok-awal.index'))->assertForbidden();
    $this->post(route('stok-awal.store'), [])->assertForbidden();
});

it('saves multiple opening areas together and skips empty rows', function () {
    prepareOpeningStockSchema();
    $areas = WarehouseArea::where('usage', 'receiving')->where('is_active', true)->orderBy('id')->get();
    $payload = ['token' => (string) \Illuminate\Support\Str::uuid(), 'stock_date' => '2026-10-06', 'usage' => 'receiving',
        'items' => [
            ['warehouse_area_id' => $areas[0]->id, 'weight_kg' => 100.25, 'package_count' => 4],
            ['warehouse_area_id' => $areas[1]->id, 'weight_kg' => 50, 'package_count' => 2],
            ['warehouse_area_id' => $areas[2]->id, 'weight_kg' => '', 'package_count' => ''],
        ]];
    $this->post(route('stok-awal.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    expect(MaterialBatch::count())->toBe(2)->and(StockMovement::count())->toBe(2)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(150.25)
        ->and((int) $this->inventory->current_sack_count)->toBe(6);
    $this->post(route('stok-awal.store'), $payload)->assertSessionHasErrors('token');
    expect(MaterialBatch::count())->toBe(2);
    $this->get(route('stok-awal.index'))->assertOk()->assertSee('150', false)->assertDontSee('Kualitas Barang');
});

it('rolls back all opening areas when a row has incomplete quantities and rejects the wrong warehouse', function () {
    prepareOpeningStockSchema();
    $area = WarehouseArea::where('usage', 'receiving')->where('is_active', true)->where('id', '!=', $this->area->id)->firstOrFail();
    $payload = ['token' => (string) \Illuminate\Support\Str::uuid(), 'stock_date' => '2026-10-06', 'usage' => 'receiving',
        'items' => [
            ['warehouse_area_id' => $this->area->id, 'weight_kg' => 100, 'package_count' => 4],
            ['warehouse_area_id' => $area->id, 'weight_kg' => 50, 'package_count' => 0],
        ]];
    $this->post(route('stok-awal.store'), $payload)->assertSessionHasErrors('items');
    expect(MaterialBatch::count())->toBe(0)->and(StockMovement::count())->toBe(0)
        ->and(\Illuminate\Support\Facades\DB::table('opening_stock_entries')->count())->toBe(0)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(0.0);
    $payload['items'][1]['package_count'] = 2;
    $payload['usage'] = 'finished';
    $this->post(route('stok-awal.store'), $payload)->assertSessionHasErrors('items.0.warehouse_area_id');
    $payload['usage'] = 'receiving';
    $payload['items'] = [['warehouse_area_id' => $area->id, 'weight_kg' => 0, 'package_count' => 0]];
    $this->post(route('stok-awal.store'), $payload)->assertSessionHasErrors('items');
});

it('records daily area transfers atomically and allocates oldest received stock first', function () {
    foreach (['2026_10_07_000012_create_accounting_journals.php', '2026_10_07_000015_connect_production_and_operational_accounting.php', '2026_10_10_000010_enable_independent_warehouse_processing.php', '2026_10_11_000002_use_warehouse_locations.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->seed(\Database\Seeders\WarehouseLocationSeeder::class);
    foreach (['2026-10-06', '2026-10-05'] as $date) {
        $payload = $this->payload;
        $payload['received_date'] = $date;
        unset($payload['source_reference']);
        $payload['lines'][0]['actual_weight_kg'] = 100;
        $payload['lines'][0]['sack_count'] = 4;
        $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $receipt = GoodsReceipt::latest('id')->firstOrFail();
        $this->post(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertRedirect()->assertSessionHasNoErrors();
    }
    $payload = ['date' => '2026-10-07',
        'sources' => [['warehouse_area_id' => $this->area->id, 'sack_count' => 5]],
        'outputs' => [['warehouse_area_id' => $this->area->id, 'weight_kg' => 80, 'package_count' => 2]],
    ];
    $this->get(route('produksi.create'))->assertOk()->assertSee('Area 1')->assertSee('Area D')->assertDontSee('Batch sumber');
    $this->postJson(route('produksi.store'), $payload)->assertUnprocessable();
    expect(\App\Models\ProductionRun::count())->toBe(0)
        ->and((int) MaterialBatch::sum('remaining_sacks'))->toBe(8)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(200.0);
    $payload['outputs'][0]['warehouse_area_id'] = WarehouseArea::where('code', 'BJ-D')->value('id');
    $this->post(route('produksi.store'), $payload)->assertRedirect(route('produksi.index'))->assertSessionHasNoErrors();
    $run = \App\Models\ProductionRun::firstOrFail();
    expect($run->status)->toBe('packed')->and((int) $run->input_sacks)->toBe(5)
        ->and((int) MaterialBatch::oldest('id')->first()->remaining_sacks)->toBe(3)
        ->and((int) MaterialBatch::latest('id')->first()->remaining_sacks)->toBe(0)
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(75.0)
        ->and((float) \App\Models\FinishedBatch::sum('weight_kg'))->toBe(80.0);
    $this->get(route('produksi.index'))->assertOk()->assertSee('Area 1')->assertSee('Area D')->assertSee('5 karung');
    $dashboard = $this->get(route('manajemen-gudang.index'))->assertOk()->assertSee('Area 1')->assertSee('Area D');
    expect((int) $dashboard->viewData('sacks'))->toBe(3)
        ->and((float) $dashboard->viewData('remainingWeight'))->toBe(75.0)
        ->and((int) $dashboard->viewData('processedSacks'))->toBe(5)
        ->and((float) $dashboard->viewData('packedWeight'))->toBe(80.0)
        ->and((float) $dashboard->viewData('finishedWeight'))->toBe(80.0)
        ->and((int) $dashboard->viewData('finishedPackages'))->toBe(2)
        ->and($dashboard->viewData('stageCounts')['process'])->toBe(1);
    $this->postJson(route('produksi.store'), $payload)->assertUnprocessable();
    expect(\App\Models\ProductionRun::count())->toBe(1);
});

it('receives and packs by dynamic locations without selecting quality', function () {
    foreach (['2026_10_07_000012_create_accounting_journals.php', '2026_10_07_000015_connect_production_and_operational_accounting.php', '2026_10_10_000010_enable_independent_warehouse_processing.php', '2026_10_11_000002_use_warehouse_locations.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->seed(\Database\Seeders\WarehouseLocationSeeder::class);
    expect(WarehouseArea::where('usage', 'finished')->count())->toBe(4);
    $payload = $this->payload;
    unset($payload['lines'][0]['quality_grade_id']);
    $this->get(route('area-barang-masuk.index'))->assertOk()->assertSee('Area 1')->assertDontSee('Area A');
    $this->get(route('area-barang-ready.index'))->assertOk()->assertSee('Area A')->assertSee('Area D')->assertDontSee('Area 1');
    $this->get(route('penerimaan-barang.create'))->assertOk()->assertDontSee('Pilih kualitas');
    $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect();
    $receipt = GoodsReceipt::firstOrFail();
    $this->post(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertRedirect();
    expect($receipt->lines()->first()->quality_grade_id)->toBeNull();
    $this->post(route('produksi.store'), [
        'started_date' => '2026-10-07', 'warehouse_area_id' => WarehouseArea::where('usage', 'processing')->value('id'),
        'pic' => 'PIC', 'work_type' => 'Jemur', 'confirm' => 1,
        'inputs' => [['material_batch_id' => MaterialBatch::first()->id, 'sack_count' => 64]],
    ])->assertRedirect();
    $run = \App\Models\ProductionRun::firstOrFail();
    $this->post(route('produksi.complete', $run), ['completed_date' => '2026-10-07', 'confirm' => 1])->assertRedirect();
    $outputs = [
        ['warehouse_area_id' => WarehouseArea::where('code', 'BJ-A')->value('id'), 'weight_kg' => 1000, 'package_count' => 20],
        ['warehouse_area_id' => WarehouseArea::where('code', 'BJ-D')->value('id'), 'weight_kg' => 1500, 'package_count' => 30],
    ];
    $invalid = $outputs;
    $invalid[0]['warehouse_area_id'] = $this->area->id;
    $this->postJson(route('produksi.pack', $run), ['packed_date' => '2026-10-07', 'confirm' => 1, 'outputs' => $invalid])->assertUnprocessable();
    expect(\App\Models\FinishedBatch::count())->toBe(0);
    $this->post(route('produksi.pack', $run), ['packed_date' => '2026-10-07', 'confirm' => 1, 'outputs' => $outputs])->assertRedirect();
    expect(\App\Models\FinishedBatch::whereNull('quality_grade_id')->count())->toBe(2)
        ->and((float) Inventory::where('stock_stage', 'finished')->value('current_stock_kg'))->toBe(2500.0);
    $this->get(route('produksi.show', $run))->assertOk()->assertSee('Area D');
    $this->get(route('manajemen-gudang.stage', 'finished'))->assertOk()->assertSee('Area A')->assertSee('Area D');
    $this->getJson(route('manajemen-penjualan.batches'))->assertOk()->assertJsonCount(2, 'data');
    $batch = \App\Models\FinishedBatch::firstOrFail();
    app(\App\Services\Transaction\ProductionService::class)->consumeFinished($batch->id, (int) $batch->inventory_id, 100, 2, '2026-10-07');
    expect((float) $batch->refresh()->remaining_weight_kg)->toBe(900.0)->and($batch->remaining_packages)->toBe(18);
    $this->post(route('area-gudang.store'), ['code' => 'BM-4', 'name' => 'Area 4', 'inventory_id' => $this->inventory->id, 'usage' => 'receiving', 'is_active' => 1])->assertRedirect();
    expect(WarehouseArea::where('code', 'BM-4')->exists())->toBeTrue();
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
    $this->get(route('penerimaan-barang.show', $receipt))->assertOk()->assertDontSee('>Kualitas<', false)->assertSee('Area 1');
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

it('rejects incorrect areas and rechecks inactive areas at confirmation', function () {
    $bad = $this->payload;
    $bad['lines'][0]['warehouse_area_id'] = WarehouseArea::create(['code'=>'OTHER', 'name'=>'Wrong Area', 'inventory_id'=>$this->inventory->id, 'usage'=>'processing', 'is_active'=>true])->id;
    $this->postJson(route('penerimaan-barang.store'), $bad)->assertUnprocessable();
    expect(GoodsReceipt::count())->toBe(0);
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $this->area->update(['is_active' => false]);
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
    $this->get(route('manajemen-gudang.index'))->assertOk()->assertSee('Penerimaan')->assertSee('Proses Harian')->assertSee('Bahan Siap Jual')->assertDontSee('Saldo Gudang Lama');
    $this->get(route('manajemen-gudang.stage', 'incoming'))->assertOk()->assertSee('Petani Test')->assertDontSee('>Kualitas<', false)->assertSee('3.386,00');
    foreach (['process', 'wip', 'press', 'finished'] as $stage) {
        $this->get(route('manajemen-gudang.stage', $stage))->assertOk()->assertSee('Belum ada')->assertDontSee('Pencatatan tahap ini belum tersedia');
    }
    $this->get(route('penerimaan-barang.index', ['farmer_id' => $this->farmer->id]))->assertOk()->assertSee('Petani Test');
    $this->get(route('area-gudang.index'))->assertOk()->assertSee('BM-A');
    $this->get(route('area-gudang.edit', $this->area))->assertOk();
});

it('opens a finished area containing migrated stock without a production record', function () {
    Schema::create('production_runs', function (Blueprint $table) {
        $table->id();
        $table->string('number');
        $table->string('status')->default('packed');
        $table->date('packed_date')->nullable();
        $table->timestamps();
    });
    Schema::create('finished_batches', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->unsignedBigInteger('production_run_id')->nullable();
        $table->unsignedBigInteger('inventory_id')->nullable();
        $table->unsignedBigInteger('warehouse_area_id');
        $table->unsignedBigInteger('quality_grade_id')->nullable();
        $table->decimal('remaining_weight_kg', 12, 2);
        $table->unsignedInteger('remaining_packages');
        $table->unsignedBigInteger('remaining_cost_cents')->default(0);
        $table->timestamps();
    });
    $ready = Inventory::create(['item_name' => 'Barang Jadi', 'stock_stage' => 'finished']);
    $area = WarehouseArea::create(['code' => 'BJ-A', 'name' => 'Area A', 'inventory_id' => $ready->id, 'usage' => 'finished', 'is_active' => true]);
    \App\Models\FinishedBatch::create(['code' => 'LEGACY-GUDANG-KERING-23-24', 'production_run_id' => null,
        'inventory_id' => $ready->id, 'warehouse_area_id' => $area->id, 'quality_grade_id' => null, 'remaining_weight_kg' => 42159.5, 'remaining_packages' => 1872]);
    $run = \App\Models\ProductionRun::forceCreate(['number' => 'PRD-TEST']);
    \App\Models\FinishedBatch::create(['code' => 'BAT-PRODUCTION', 'production_run_id' => $run->id,
        'warehouse_area_id' => $area->id, 'quality_grade_id' => null, 'remaining_weight_kg' => 100, 'remaining_packages' => 2]);
    $this->get(route('manajemen-gudang.area', $area))->assertOk()
        ->assertSee('LEGACY-GUDANG-KERING-23-24')->assertSee('42.159,50')->assertSee('1872')
        ->assertSee(route('produksi.show', $run), false);
    $this->get(route('manajemen-gudang.stage', 'finished'))->assertOk()
        ->assertSee('LEGACY-GUDANG-KERING-23-24')->assertSee('42.159,50')
        ->assertSee(route('produksi.show', $run), false);
    $this->grantModuleAccess($this->user, ['surat-jalan']);
    $this->get(route('surat-jalan.create'))->assertOk()
        ->assertViewHas('batches', fn ($batches) => $batches->contains(fn ($batch) => str_contains($batch['label'], 'LEGACY-GUDANG-KERING-23-24')));
    $run->update(['status' => 'processing']);
    expect(\App\Models\FinishedBatch::readyForSale()->pluck('code')->all())->toBe(['LEGACY-GUDANG-KERING-23-24']);
    Schema::table('stock_movements', fn (Blueprint $table) => $table->unsignedBigInteger('finished_batch_id')->nullable());
    $legacy = \App\Models\FinishedBatch::where('code', 'LEGACY-GUDANG-KERING-23-24')->sole();
    $allocation = app(\App\Services\Transaction\ProductionService::class)->consumeFinished($legacy->id, $ready->id, 5000, 100, '2026-10-07');
    expect($allocation['finished_batch_id'])->toBe($legacy->id);
    expect((float) $legacy->fresh()->remaining_weight_kg)->toBe(37159.5);
    expect((int) $legacy->fresh()->remaining_packages)->toBe(1772);
    Schema::create('delivery_note_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_note_id');
        $table->unsignedBigInteger('sales_transaction_id');
        $table->unsignedBigInteger('finished_batch_id')->nullable();
        $table->string('item_name');
        $table->decimal('quantity_kg', 12, 2);
        $table->unsignedInteger('sack_count');
        $table->timestamps();
    });
    $factory = Factory::create(['name' => 'Customer Edit']);
    $sale = \App\Models\SalesTransaction::create(['factory_id' => $factory->id, 'inventory_id' => $ready->id,
        'invoice_number' => 'PJL-EDIT', 'sale_date' => '2026-10-07', 'stock_mode' => 'delivery_note',
        'total_weight_kg' => 5000, 'price_per_kg' => 1000, 'total_sale_value' => 5000000]);
    $note = \App\Models\DeliveryNote::create(['number' => 'SJ-EDIT', 'delivery_date' => '2026-10-07',
        'sales_transaction_id' => $sale->id, 'recipient_name' => 'Customer Edit', 'destination_address' => 'Gudang',
        'origin' => 'Area A', 'item_name' => 'Rumput Laut', 'quantity_kg' => 5000]);
    $note->items()->create(['sales_transaction_id' => $sale->id, 'finished_batch_id' => $legacy->id,
        'item_name' => 'Rumput Laut', 'quantity_kg' => 5000, 'sack_count' => 100]);
    $note->forceFill(['status' => 'draft'])->save();
    $legacy->update(['remaining_weight_kg' => 0, 'remaining_packages' => 0]);
    $this->get(route('surat-jalan.edit', $note))->assertOk()
        ->assertViewHas('batches', fn ($batches) => $batches->contains('id', $legacy->id))
        ->assertViewHas('note', fn ($saved) => $saved->items->sole()->finished_batch_id === $legacy->id)
        ->assertSee(':selected="String(batch.id) === String(row.finished_batch_id)"', false);
});

it('preserves seed edits and requires permission and explicit confirmation', function () {
    $this->area->update(['name' => 'Area Client']);
    $this->inventory->update(['current_stock_kg' => 123]);
    $this->seed(GoodsReceivingSeeder::class);
    expect($this->area->refresh()->name)->toBe('Area Client')->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(123.0)
        ->and(Menu::where('route', 'penerimaan-barang.index')->count())->toBe(0);
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
        ->assertSee('Dashboard Gudang')->assertSee('Penerimaan sampai Siap Jual')
        ->assertSee('Penerimaan')->assertSee('Proses Harian')->assertSee('Bahan Siap Jual');
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
    expect($areaA)->toBeNull()
        ->and((int) $areaB->remaining_sacks)->toBe(24)->and((float) $areaB->received_weight)->toBe(1386.0);
    $detail = $this->get(route('manajemen-gudang.area', $this->area))->assertOk()->assertSee('Area 1')->assertSee('2.000,00');
    expect($detail->viewData('batches')->count())->toBe(1)->and($detail->viewData('batches')->sum('remaining_sacks'))->toBe(20);
    $this->get(route('manajemen-gudang.area',999999))->assertNotFound();
});

it('hides references on create and generates distinct stable references without changing stock', function () {
    $this->get(route('penerimaan-barang.create'))->assertOk()
        ->assertDontSee('Referensi Pembelian Panen')->assertDontSee('Referensi Nota / Kedatangan');
    $payload = $this->payload;
    unset($payload['source_reference'], $payload['harvest_transaction_id']);
    $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect();
    $first = GoodsReceipt::firstOrFail();
    $reference = $first->source_reference;
    expect($reference)->not->toBeEmpty()->and($first->harvest_transaction_id)->toBeNull();
    $this->post(route('penerimaan-barang.store'), $payload)->assertRedirect();
    expect(GoodsReceipt::latest('id')->first()->source_reference)->not->toBe($reference)
        ->and(StockMovement::count())->toBe(0);
    $this->put(route('penerimaan-barang.update', $first), $payload)->assertRedirect();
    expect($first->refresh()->source_reference)->toBe($reference);
});

function unifiedHarvestPayload($test): array
{
    foreach (['manajemen-panen.create', 'manajemen-panen.read'] as $name) {
        $test->user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($name, 'web'));
    }
    return $test->harvestPayload + [
        'submission_key' => (string) \Illuminate\Support\Str::uuid(), 'confirm' => true,
        'inventory_id' => $test->inventory->id,
        'lines' => [['quality_grade_id' => $test->grade->id, 'warehouse_area_id' => $test->area->id, 'actual_weight_kg' => 3450, 'sack_count' => 64]],
    ];
}

function enableHarvestFinance($test): void
{
    (require database_path('migrations/2026_10_07_000012_create_accounting_journals.php'))->up();
    Schema::table('journal_entries', function (Blueprint $t) { $t->nullableMorphs('source'); $t->boolean('is_automatic')->default(false); });
    Schema::create('operational_events', function (Blueprint $t) {
        $t->id(); $t->string('source_key')->unique(); $t->string('source_type'); $t->unsignedBigInteger('source_id');
        $t->string('event'); $t->json('snapshot'); $t->foreignId('journal_entry_id')->nullable()->constrained();
        $t->unsignedInteger('version')->default(0); $t->boolean('cancelled')->default(false); $t->timestamps();
    });
    Schema::table('material_batches', function (Blueprint $t) {
        $t->decimal('remaining_weight_kg', 12, 2)->default(0); $t->unsignedBigInteger('remaining_cost_cents')->default(0); $t->boolean('is_costed')->default(false);
    });
    \App\Models\AccountingSetting::find(1)->update(['start_date' => '2026-10-07']);
    $test->seed(\Database\Seeders\HarvestFinanceSeeder::class);
}

function receiveHarvestForFinance($test): \App\Models\HarvestTransaction
{
    $test->postJson(route('manajemen-panen.store'), unifiedHarvestPayload($test))->assertSuccessful();
    return \App\Models\HarvestTransaction::firstOrFail();
}

function harvestPricePayload(): array
{
    return ['confirm' => 1, 'purchase_date' => '2026-10-07', 'price_per_kg' => 100, 'sale_deduction_type' => 'none', 'sale_deduction_value' => 0];
}

function harvestPaymentPayload(): array
{
    return ['confirm' => 1, 'submission_key' => (string) \Illuminate\Support\Str::uuid(), 'payment_date' => '2026-10-07', 'amount' => 0, 'debt_deduction' => 0];
}

it('receives physical harvest once without financial posting or trusting injected money', function () {
    enableHarvestFinance($this);
    $payload = unifiedHarvestPayload($this);
    $payload['price_per_kg'] = 5000;
    unset($payload['inventory_id']); // The selected area determines the warehouse.
    $payload['cash_payout'] = 200000;
    $payload['debt_deduction'] = 50000;
    $this->postJson(route('manajemen-panen.store'), $payload)->assertSuccessful();
    $harvest = \App\Models\HarvestTransaction::firstOrFail();
    expect((bool) $harvest->is_received)->toBeTrue()->and($harvest->separate_payment)->toBeTrue()
        ->and((int) $harvest->total_harvest_value)->toBe(0)->and((int) $harvest->price_per_kg)->toBe(0)
        ->and((int) $harvest->cash_payout)->toBe(0)->and((int) $harvest->debt_deduction)->toBe(0)
        ->and((int) $this->farmer->refresh()->current_debt)->toBe(100000)
        ->and($harvest->payment_status)->toBe('Menunggu Penetapan Harga')
        ->and((float) $this->inventory->refresh()->current_stock_kg)->toBe(3450.0)
        ->and((bool) MaterialBatch::first()->is_costed)->toBeFalse()
        ->and(\App\Models\JournalEntry::count())->toBe(0)->and(\App\Models\FarmerLedger::count())->toBe(0);
    $this->postJson(route('manajemen-panen.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('confirm');
    expect(StockMovement::count())->toBe(1)->and(GoodsReceipt::count())->toBe(1);
    $page = $this->get(route('manajemen-panen.index'))->assertOk()->assertDontSee('Ringkasan Hutang')->assertDontSee('Harga / Kg')
        ->assertDontSee('Pilih gudang bahan');
    expect($page->viewData('areas')->firstWhere('id', $this->area->id)['name'])->toBe('Area 1');
    $row = $this->getJson(route('harvest-data'))->assertOk()->json('data.0');
    expect($row)->not->toHaveKeys(['cash_payout', 'price_per_kg', 'debt_deduction', 'total_harvest_value', 'remaining_debt_after_payment'])
        ->and($row['farmer'])->not->toHaveKey('current_debt');
});

it('rolls back physical receipt on invalid area or mismatched totals', function () {
    $payload = unifiedHarvestPayload($this);
    $payload['lines'][0]['warehouse_area_id'] = WarehouseArea::create(['code'=>'WRONG', 'name'=>'Ready', 'inventory_id'=>$this->inventory->id, 'usage'=>'finished', 'is_active'=>true])->id;
    $this->postJson(route('manajemen-panen.store'), $payload)->assertUnprocessable();
    $payload['lines'][0]['warehouse_area_id'] = $this->area->id;
    $payload['lines'][0]['actual_weight_kg'] = 3400;
    $this->postJson(route('manajemen-panen.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('lines');
    expect(\App\Models\HarvestTransaction::count())->toBe(0)->and(GoodsReceipt::count())->toBe(0)
        ->and(StockMovement::count())->toBe(0)->and((int) $this->farmer->refresh()->current_debt)->toBe(100000);
});

it('prices inventory against payable then settles loan and cash with separate balanced journals', function () {
    enableHarvestFinance($this);
    $harvest = receiveHarvestForFinance($this);
    $this->get(route('harvest-finance.index'))->assertOk()->assertSee('Menunggu Penetapan Harga');
    $this->get(route('harvest-finance.show', $harvest))->assertOk()->assertSee('Tetapkan Nilai Pembelian');
    $this->post(route('harvest-finance.price', $harvest), harvestPricePayload())->assertRedirect();
    expect($harvest->refresh()->payment_status)->toBe('Belum Terbayar')->and($harvest->outstanding_payable)->toBe(338600)
        ->and((bool) MaterialBatch::first()->is_costed)->toBeTrue()->and((int) MaterialBatch::first()->remaining_cost_cents)->toBe(33860000)
        ->and((int) $this->farmer->refresh()->current_debt)->toBe(100000);
    $purchase = \App\Models\JournalEntry::with('lines')->firstOrFail();
    expect((int) $purchase->lines->where('account_code','11301')->sum('debit_cents'))->toBe(33860000)
        ->and((int) $purchase->lines->where('account_code','21101')->sum('credit_cents'))->toBe(33860000);
    $cash = \App\Models\ChartOfAccount::create(['code'=>'11109','name'=>'Bank Pembayaran','category'=>'Harta','account_group'=>'Kas dan Bank','normal_balance'=>'Debit','is_active'=>true]);
    $payload = harvestPaymentPayload();
    $payload['amount'] = 50000; $payload['debt_deduction'] = 50000; $payload['cash_account_id'] = $cash->id;
    $this->post(route('harvest-finance.pay', $harvest), $payload)->assertRedirect();
    expect($harvest->refresh()->payment_status)->toBe('Terbayar Sebagian')->and($harvest->outstanding_payable)->toBe(238600)
        ->and((int) $this->farmer->refresh()->current_debt)->toBe(50000)->and(\App\Models\JournalEntry::count())->toBe(3);
    $this->get(route('harvest-finance.show',$harvest))->assertOk()->assertSee('Riwayat Pembayaran')->assertSee('238.600');
    $this->postJson(route('harvest-finance.pay',$harvest),$payload)->assertUnprocessable()->assertJsonValidationErrors('amount');
    $payload['submission_key'] = (string) \Illuminate\Support\Str::uuid(); $payload['amount'] = 238600; $payload['debt_deduction'] = 0;
    $this->post(route('harvest-finance.pay',$harvest),$payload)->assertRedirect()->assertSessionHasNoErrors();
    expect($harvest->refresh()->payment_status)->toBe('Sudah Terbayar')->and($harvest->outstanding_payable)->toBe(0)
        ->and((int) $harvest->cash_payout)->toBe(288600)->and(\App\Models\HarvestPayment::count())->toBe(2)
        ->and(StockMovement::count())->toBe(1)->and(\App\Models\JournalEntry::count())->toBe(4);
    foreach (\App\Models\JournalEntry::with('lines')->get() as $journal) {
        expect($journal->status)->toBe('posted')->and((int) $journal->lines->sum('debit_cents'))->toBe((int) $journal->lines->sum('credit_cents'));
    }
    $offset = \App\Models\JournalEntry::whereHas('lines', fn($q)=>$q->where('account_code','11201'))->with('lines')->firstOrFail();
    expect((int) $offset->lines->where('account_code','11201')->sum('credit_cents'))->toBe(5000000)
        ->and((int) $offset->lines->where('account_code','21101')->sum('debit_cents'))->toBe(5000000);
    $flow = app(\App\Services\Finance\CashFlowService::class)->summary(Carbon::parse('2026-10-07'),Carbon::parse('2026-10-07'),'all');
    expect(collect($flow)->firstWhere('id',2)['amount'])->toBe(288600);
    $this->get(route('harvest-finance.index',['status'=>'paid']))->assertOk()->assertSee($harvest->batch_code);
    $this->get(route('harvest-finance.index',['status'=>'unpaid']))->assertOk()->assertDontSee($harvest->batch_code);
    $this->postJson(route('harvest-finance.price',$harvest),harvestPricePayload())->assertUnprocessable();
    $payload['submission_key'] = (string) \Illuminate\Support\Str::uuid(); $payload['amount'] = 1;
    $this->postJson(route('harvest-finance.pay',$harvest),$payload)->assertUnprocessable();
});

it('records an independent loan offset without inventing cash payment', function () {
    enableHarvestFinance($this);
    $harvest = receiveHarvestForFinance($this);
    $this->post(route('harvest-finance.price',$harvest),harvestPricePayload())->assertRedirect();
    $payload = harvestPaymentPayload(); $payload['debt_deduction'] = 100000;
    $this->post(route('harvest-finance.pay',$harvest),$payload)->assertRedirect();
    expect((int) $this->farmer->refresh()->current_debt)->toBe(0)->and((int) $harvest->refresh()->cash_payout)->toBe(0)
        ->and($harvest->outstanding_payable)->toBe(238600)->and(\App\Models\JournalEntry::count())->toBe(2);
});

it('rejects payment before pricing, excess amounts, excess loan offset and inactive bank without mutations', function () {
    enableHarvestFinance($this);
    $harvest = receiveHarvestForFinance($this);
    $payload = harvestPaymentPayload(); $payload['amount'] = 1000;
    $this->postJson(route('harvest-finance.pay',$harvest),$payload)->assertUnprocessable();
    $this->post(route('harvest-finance.price',$harvest),harvestPricePayload())->assertRedirect();
    $payload['amount'] = 338601;
    $this->postJson(route('harvest-finance.pay',$harvest),$payload)->assertUnprocessable()->assertJsonValidationErrors('amount');
    $payload['amount'] = 0; $payload['debt_deduction'] = 100001;
    $this->postJson(route('harvest-finance.pay',$harvest),$payload)->assertUnprocessable()->assertJsonValidationErrors('debt_deduction');
    $cash = \App\Models\ChartOfAccount::create(['code'=>'11102','name'=>'Bank','category'=>'Harta','account_group'=>'Kas dan Bank','normal_balance'=>'Debit','is_active'=>false]);
    $payload['amount'] = 1000; $payload['debt_deduction'] = 50000; $payload['cash_account_id'] = $cash->id;
    $this->postJson(route('harvest-finance.pay',$harvest),$payload)->assertUnprocessable()->assertJsonValidationErrors('cash_account_id');
    expect(\App\Models\HarvestPayment::count())->toBe(0)->and(\App\Models\FarmerLedger::count())->toBe(0)
        ->and((int) $this->farmer->refresh()->current_debt)->toBe(100000)->and($harvest->refresh()->outstanding_payable)->toBe(338600)
        ->and(\App\Models\JournalEntry::count())->toBe(1);
});

it('rolls back pricing and settlement when accounting rejects posting', function () {
    enableHarvestFinance($this);
    $harvest = receiveHarvestForFinance($this);
    $payable = \App\Models\ChartOfAccount::create(['code'=>'21101','name'=>'Utang Petani','category'=>'Utang','account_group'=>'Utang Lancar','normal_balance'=>'Kredit','is_active'=>false]);
    $this->postJson(route('harvest-finance.price',$harvest),harvestPricePayload())->assertUnprocessable();
    expect($harvest->refresh()->priced_at)->toBeNull()->and((bool) MaterialBatch::first()->is_costed)->toBeFalse()
        ->and(\App\Models\JournalEntry::count())->toBe(0);
    $payable->update(['is_active'=>true]);
    $this->post(route('harvest-finance.price',$harvest),harvestPricePayload())->assertRedirect();
    \App\Models\ChartOfAccount::create(['code'=>'11201','name'=>'Piutang Petani','category'=>'Harta','account_group'=>'Piutang','normal_balance'=>'Debit','is_active'=>false]);
    $payload = harvestPaymentPayload(); $payload['debt_deduction'] = 50000;
    $this->postJson(route('harvest-finance.pay',$harvest),$payload)->assertUnprocessable();
    expect((int) $this->farmer->refresh()->current_debt)->toBe(100000)->and(\App\Models\HarvestPayment::count())->toBe(0)
        ->and(\App\Models\FarmerLedger::count())->toBe(0)->and($harvest->refresh()->outstanding_payable)->toBe(338600)
        ->and(\App\Models\JournalEntry::count())->toBe(1);
});

it('separates warehouse and finance access on pages and submissions', function () {
    enableHarvestFinance($this);
    $harvest = receiveHarvestForFinance($this);
    $warehouse = User::create(['name'=>'Gudang','email'=>'gudang@example.test','password'=>'password']); $warehouse->assignRole('gudang');
    $this->actingAs($warehouse);
    $this->get(route('manajemen-panen.index'))->assertOk()->assertDontSee('Harga / Kg');
    $this->getJson(route('harvest-data'))->assertOk();
    $this->get(route('harvest-finance.index'))->assertForbidden();
    $this->postJson(route('harvest-finance.price',$harvest),harvestPricePayload())->assertForbidden();
    $this->postJson(route('harvest-finance.pay',$harvest),harvestPaymentPayload())->assertForbidden();
    $finance = User::create(['name'=>'Keuangan','email'=>'finance@example.test','password'=>'password']); $finance->assignRole('keuangan');
    $this->actingAs($finance);
    $this->get(route('harvest-finance.index'))->assertOk();
    $this->get(route('harvest-finance.show',$harvest))->assertOk();
    $this->get(route('manajemen-panen.index'))->assertForbidden();
    $this->postJson(route('manajemen-panen.store'),unifiedHarvestPayload($this))->assertForbidden();
    $this->post(route('harvest-finance.price',$harvest),harvestPricePayload())->assertRedirect();
});

it('preserves old purchase journals and prevents legacy double payment', function () {
    enableHarvestFinance($this);
    $harvest = app(HarvestTransactionService::class)->createHarvest($this->harvestPayload);
    expect((bool) $harvest->separate_payment)->toBeFalse()->and((int) $harvest->cash_payout)->toBe(338600);
    $this->get(route('harvest-finance.show',$harvest))->assertOk()->assertSee('alur lama');
    $this->postJson(route('harvest-finance.price',$harvest),harvestPricePayload())->assertUnprocessable();
    $this->postJson(route('harvest-finance.pay',$harvest),harvestPaymentPayload())->assertUnprocessable();
    expect(\App\Models\HarvestPayment::count())->toBe(0)->and(\App\Models\JournalEntry::count())->toBe(1);
});


it('uses database area names and only active areas in the dashboard and adjustment form', function () {
    prepareOpeningStockSchema();
    $this->area->update(['name' => 'Penerimaan Utama']);
    $ready = WarehouseArea::where('usage', 'finished')->firstOrFail();
    $ready->update(['name' => 'Siap Kirim Utama']);
    $inactive = WarehouseArea::where('usage', 'receiving')->where('id', '!=', $this->area->id)->firstOrFail();
    $inactive->update(['name' => 'Area Tidak Aktif', 'is_active' => false]);
    $duplicate = WarehouseArea::create(['code' => 'BM-DUP', 'name' => 'Penerimaan Utama',
        'inventory_id' => $this->inventory->id, 'usage' => 'receiving', 'is_active' => true]);
    $page = $this->get(route('manajemen-gudang.index'))->assertOk()
        ->assertSee('Penerimaan Utama')->assertSee('Siap Kirim Utama')->assertDontSee('Area Tidak Aktif');
    expect($page->viewData('areas')->pluck('id')->contains($inactive->id))->toBeFalse();
    $rows = collect($this->getJson(route('manajemen-gudang.adjustment-data'))->assertOk()->json('data'));
    expect($rows->firstWhere('key', 'area-'.$this->area->id)['label'])->toBe('Penerimaan Utama')
        ->and($rows->firstWhere('key', 'area-'.$ready->id)['label'])->toBe('Siap Kirim Utama')
        ->and($rows->firstWhere('key', 'area-'.$inactive->id))->toBeNull()
        ->and($rows->firstWhere('key', 'area-'.$duplicate->id)['label'])->toBe('Penerimaan Utama')
        ->and($rows->pluck('key')->unique()->count())->toBe($rows->count());
});

it('keeps daily production source and destination names aligned with warehouse areas', function () {
    prepareOpeningStockSchema();
    $this->area->update(['name' => 'Penerimaan Utama']);
    $ready = WarehouseArea::where('usage', 'finished')->firstOrFail();
    $ready->update(['name' => 'Siap Kirim Utama']);

    $page = $this->get(route('produksi.create'))->assertOk();
    expect($page->viewData('areas')->pluck('id')->contains($this->area->id))->toBeTrue()
        ->and($page->viewData('areas')->pluck('id')->contains($ready->id))->toBeFalse()
        ->and($page->viewData('finishedAreas')->pluck('id')->contains($ready->id))->toBeTrue()
        ->and($page->viewData('finishedAreas')->pluck('id')->contains($this->area->id))->toBeFalse();
    $html = $page->getContent();
    $source = explode('Masuk bahan siap jual', $html)[0];
    $destination = explode('Masuk bahan siap jual', $html)[1];
    expect($source)->toContain('Penerimaan Utama')->not->toContain('Siap Kirim Utama');
    expect($destination)->toContain('Siap Kirim Utama')->not->toContain('Penerimaan Utama');
    expect($page->viewData('areaLabels')[$this->area->id])->toBe('Penerimaan Utama')
        ->and($page->viewData('areaLabels')[$ready->id])->toBe('Siap Kirim Utama');
});

it('shows legacy warehouse balances in Area Lama without moving or duplicating stock', function () {
    $legacy = Inventory::create(['item_name' => 'Gudang Basah', 'stock_stage' => 'legacy', 'current_stock_kg' => 150, 'current_sack_count' => 3]);
    $response = $this->getJson(route('manajemen-gudang.adjustment-data'))->assertOk();
    $area = collect($response->json('data'))->firstWhere('label', 'Area Lama');
    $row = collect($area['items'])->firstWhere('id', $legacy->id);
    expect($row['actual_weight'])->toBe(150)->and($legacy->fresh()->current_stock_kg)->toEqual(150);
    $this->postJson(route('manajemen-gudang.adjustment'), [
        'movement_date' => '2026-10-07', 'description' => 'Hitung fisik',
        'items' => [array_merge($row, ['actual_weight' => 120, 'actual_count' => 2])],
    ])->assertOk();
    expect($legacy->fresh()->current_stock_kg)->toEqual(120)->and($legacy->fresh()->current_sack_count)->toBe(2);
    $movement = StockMovement::where('inventory_id', $legacy->id)->firstOrFail();
    expect($movement->type)->toBe('adjustment')->and($movement->direction)->toBe('out')
        ->and($movement->description)->toContain('Area Lama', '150 ? 120', 'Hitung fisik');
    $this->postJson(route('manajemen-gudang.adjustment'), [
        'movement_date' => '2026-10-07', 'description' => 'Form lama', 'items' => [$row],
    ])->assertUnprocessable()->assertJsonValidationErrors('items');
    expect($legacy->fresh()->current_stock_kg)->toEqual(120);
});

it('records unchanged stock counts as dated adjustment history', function () {
    $legacy = Inventory::create(['item_name' => 'Gudang Kering', 'stock_stage' => 'legacy', 'current_stock_kg' => 100, 'current_sack_count' => 2]);
    $areas = app(\App\Services\Transaction\AreaStockAdjustmentService::class)->areas();
    $row = collect(collect($areas)->firstWhere('label', 'Area Lama')['items'])->firstWhere('id', $legacy->id);
    $this->postJson(route('manajemen-gudang.adjustment'), ['movement_date' => '2026-10-07', 'description' => 'Sesuai fisik', 'items' => [$row]])->assertOk();
    expect(StockMovement::where('inventory_id', $legacy->id)->where('type', 'adjustment')->count())->toBe(1);
    expect($legacy->fresh()->current_stock_kg)->toEqual(100);
});

it('formats adjustment rows safely when historical batch grades are missing', function () {
    $service = app(\App\Services\Transaction\AreaStockAdjustmentService::class);
    $method = new ReflectionMethod($service, 'row');
    $method->setAccessible(true);
    $line = new \App\Models\GoodsReceiptLine();
    $line->setRelation('grade', null);
    $material = new MaterialBatch(['code' => 'OLD-MATERIAL', 'remaining_sacks' => 2, 'remaining_weight_kg' => 100]);
    $material->setRelation('line', $line);
    $finished = new \App\Models\FinishedBatch(['code' => 'OLD-READY', 'remaining_packages' => 2, 'remaining_weight_kg' => 50]);
    $finished->setRelation('grade', null);
    $finished->setRelation('inventory', new Inventory(['item_name' => 'Barang Jadi']));
    expect($method->invoke($service, 'material', $material, 'Area 1')['name'])->toBe('Barang masuk tanpa grade');
    expect($method->invoke($service, 'finished', $finished, 'Area A')['name'])->toBe('Barang Jadi');
});

it('uses saved receiving area names in the harvest receiving form', function () {
    prepareOpeningStockSchema();
    $this->user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('manajemen-panen.read', 'web'));
    $this->area->update(['name' => 'Penerimaan Panen Z']);
    $ready = WarehouseArea::where('usage', 'finished')->firstOrFail();
    $ready->update(['name' => 'Ready Panen']);
    $inactive = WarehouseArea::create(['code' => 'PANEN-INACTIVE', 'name' => 'Panen Nonaktif',
        'usage' => 'receiving', 'inventory_id' => $this->inventory->id, 'is_active' => false]);

    $areas = $this->get(route('manajemen-panen.index'))->assertOk()->viewData('areas');
    expect($areas->firstWhere('id', $this->area->id)['name'])->toBe('Penerimaan Panen Z')
        ->and($areas->firstWhere('id', $this->area->id)['inventory_id'])->toBe($this->inventory->id)
        ->and($areas->firstWhere('id', $ready->id))->toBeNull()
        ->and($areas->firstWhere('id', $inactive->id))->toBeNull();
});

it('records area activities without changing stock or creating production and journals', function () {
    prepareOpeningStockSchema();
    (require database_path('migrations/2026_10_16_000001_create_area_activities_table.php'))->up();
    $this->area->update(['name' => 'Area Jemur']);
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $this->post(route('penerimaan-barang.confirm', GoodsReceipt::firstOrFail()), ['confirm' => 1])->assertRedirect();
    $stock = $this->inventory->fresh()->getAttributes();
    $batches = MaterialBatch::get()->toArray();
    $movements = StockMovement::count();
    $journals = \App\Models\JournalEntry::count();
    $payload = ['activity_date' => '2026-10-07', 'warehouse_area_id' => $this->area->id,
        'work_type' => 'Jemur', 'sack_count' => 10, 'pic' => 'Petugas Jemur', 'notes' => 'Balik bahan sore'];
    $this->get(route('produksi.activity.create'))->assertOk()->assertSee('Area Jemur');
    $this->post(route('produksi.activity.store'), $payload)->assertRedirect(route('produksi.index'))->assertSessionHasNoErrors();
    expect(\App\Models\AreaActivity::sole()->created_by)->toBe($this->user->id)
        ->and($this->inventory->fresh()->getAttributes())->toBe($stock)
        ->and(MaterialBatch::get()->toArray())->toBe($batches)
        ->and(StockMovement::count())->toBe($movements)
        ->and(\App\Models\JournalEntry::count())->toBe($journals)
        ->and(\App\Models\ProductionRun::count())->toBe(0);
    $this->get(route('produksi.index'))->assertOk()->assertSee('Petugas Jemur')->assertSee('Balik bahan sore');
    $ready = WarehouseArea::where('usage', 'finished')->firstOrFail();
    $this->postJson(route('produksi.activity.store'), array_replace($payload, ['warehouse_area_id' => $ready->id]))->assertUnprocessable();
    $this->area->update(['is_active' => false]);
    $this->postJson(route('produksi.activity.store'), $payload)->assertUnprocessable();
    $this->postJson(route('produksi.activity.store'), array_replace($payload, ['activity_date' => '2026-10-08', 'sack_count' => 0]))->assertUnprocessable();
    expect(\App\Models\AreaActivity::count())->toBe(1);
    $user = User::create(['name' => 'No access', 'email' => 'activity-no-access@example.test', 'password' => 'password']);
    $this->actingAs($user)->get(route('produksi.activity.create'))->assertForbidden();
    $this->postJson(route('produksi.activity.store'), $payload)->assertForbidden();
});

it('uses saved area names and warehouse types in stock transfer options', function () {
    prepareOpeningStockSchema();
    $this->area->update(['name' => 'Masuk Area Z']);
    $ready = WarehouseArea::where('usage', 'finished')->firstOrFail();
    $ready->update(['name' => 'Ready Area 99']);
    $inactive = WarehouseArea::create(['code' => 'TRF-INACTIVE', 'name' => 'Area Nonaktif',
        'usage' => 'receiving', 'inventory_id' => $this->inventory->id, 'is_active' => false]);

    $areas = collect($this->getJson(route('manajemen-gudang.transfer-data'))->assertOk()->json('data.areas'));
    expect($areas->firstWhere('id', $this->area->id)['label'])->toBe('Masuk Area Z')
        ->and($areas->firstWhere('id', $this->area->id)['usage'])->toBe('receiving')
        ->and($areas->firstWhere('id', $ready->id)['label'])->toBe('Ready Area 99')
        ->and($areas->firstWhere('id', $ready->id)['usage'])->toBe('finished')
        ->and($areas->firstWhere('id', $inactive->id))->toBeNull();
});

it('transfers area stock within one warehouse type while preserving totals and history', function () {
    Schema::table('material_batches', fn (Blueprint $table) => $table->decimal('remaining_weight_kg', 12, 2)->default(0));
    Schema::table('material_batches', fn (Blueprint $table) => $table->unsignedBigInteger('remaining_cost_cents')->default(0));
    Schema::table('material_batches', fn (Blueprint $table) => $table->boolean('is_costed')->default(false));
    $this->post(route('penerimaan-barang.store'), $this->payload)->assertRedirect();
    $receipt = GoodsReceipt::firstOrFail();
    $this->post(route('penerimaan-barang.confirm', $receipt), ['confirm' => 1])->assertRedirect();
    $target = WarehouseArea::create(['code' => 'TRF-TARGET', 'name' => 'Area Tujuan', 'inventory_id' => $this->inventory->id, 'usage' => 'receiving', 'is_active' => true]);
    $batch = MaterialBatch::firstOrFail();
    $payload = ['usage' => 'receiving', 'source_kind' => 'area', 'from_id' => $this->area->id, 'to_area_id' => $target->id,
        'transfer_date' => '2026-10-07', 'weight_kg' => 100, 'sack_count' => 2, 'description' => 'Pindah lokasi'];
    $before = (float) $this->inventory->fresh()->current_stock_kg;
    $this->postJson(route('manajemen-gudang.transfer'), $payload)->assertOk();
    expect((float) $this->inventory->fresh()->current_stock_kg)->toBe($before);
    expect(MaterialBatch::whereHas('line', fn ($query) => $query->where('warehouse_area_id', $target->id))->sum('remaining_weight_kg'))->toEqual(100);
    expect(StockMovement::where('type', 'transfer')->count())->toBe(2);
    $this->postJson(route('manajemen-gudang.transfer'), array_merge($payload, ['usage' => 'finished']))->assertUnprocessable();
    $this->postJson(route('manajemen-gudang.transfer'), array_merge($payload, ['to_area_id' => $this->area->id]))->assertUnprocessable();
});

it('moves legacy stock into an incoming area and rejects legacy destinations', function () {
    Schema::table('goods_receipts', fn (Blueprint $table) => $table->unsignedBigInteger('farmer_id')->nullable()->change());
    Schema::table('material_batches', fn (Blueprint $table) => $table->decimal('remaining_weight_kg', 12, 2)->default(0));
    $legacy = Inventory::create(['item_name' => 'Gudang Basah', 'stock_stage' => 'legacy', 'current_stock_kg' => 100, 'current_sack_count' => 2]);
    $payload = ['usage' => 'receiving', 'source_kind' => 'legacy', 'from_id' => $legacy->id, 'to_area_id' => $this->area->id,
        'transfer_date' => '2026-10-07', 'weight_kg' => 50, 'sack_count' => 1, 'description' => 'Stok lama'];
    $this->postJson(route('manajemen-gudang.transfer'), $payload)->assertOk();
    expect((float) $legacy->fresh()->current_stock_kg)->toBe(50.0)->and($legacy->fresh()->current_sack_count)->toBe(1);
    expect(MaterialBatch::sum('remaining_weight_kg'))->toEqual(50);
    expect(GoodsReceipt::firstOrFail()->status)->toBe('confirmed');
    $this->postJson(route('manajemen-gudang.transfer'), array_merge($payload, ['source_kind' => 'legacy-destination']))->assertUnprocessable();
});
