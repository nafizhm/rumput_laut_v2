<?php

use App\Models\ChartOfAccount;
use App\Models\Menu;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php',
        '2026_10_07_000002_create_chart_of_accounts_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Role::create(['name' => 'owner', 'guard_name' => 'web']);
    Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
    Menu::create(['name' => 'Keuangan', 'order' => 4, 'is_active' => true]);
    $this->seed(ChartOfAccountSeeder::class);
    $this->withoutVite();
    $this->user = User::create(['name' => 'Owner', 'email' => 'coa@example.test', 'password' => 'password']);
    $this->user->assignRole('owner');
    $this->actingAs($this->user);
});

it('seeds business accounts under finance without duplicating or overwriting edited accounts', function () {
    $count = ChartOfAccount::count();
    expect($count)->toBeGreaterThanOrEqual(50);
    ChartOfAccount::where('code', '11201')->update(['name' => 'Piutang Petani Mitra']);
    $this->seed(ChartOfAccountSeeder::class);
    expect(ChartOfAccount::count())->toBe($count)
        ->and(ChartOfAccount::where('code', '11201')->first()->name)->toBe('Piutang Petani Mitra')
        ->and(ChartOfAccount::where('code', '11201')->first()->category)->toBe('Harta')
        ->and(ChartOfAccount::where('code', '12201')->first()->normal_balance)->toBe('Kredit')
        ->and(ChartOfAccount::where('code', '31102')->first()->normal_balance)->toBe('Debit')
        ->and(ChartOfAccount::where('code', '41201')->first()->normal_balance)->toBe('Debit')
        ->and(Menu::where('route', 'coa.index')->count())->toBe(1)
        ->and(Menu::where('route', 'coa.index')->first()->parent->name)->toBe('Keuangan');
    foreach (ChartOfAccount::all() as $account) {
        expect(str_starts_with($account->code, ChartOfAccount::CATEGORIES[$account->category]))->toBeTrue();
    }
});

it('renders the list and forms and filters accounts by category and code', function () {
    $this->get(route('coa.index'))->assertOk()->assertSee('Piutang Petani')->assertSee('Harga Pokok Penjualan');
    $this->get(route('coa.index', ['category' => 'Utang']))->assertOk()->assertSee('Utang Usaha Pemasok')->assertDontSee('Piutang Petani');
    $this->get(route('coa.index', ['q' => '11201']))->assertOk()->assertSee('Piutang Petani')->assertDontSee('Persediaan Rumput Laut Basah');
    $this->get(route('coa.create'))->assertOk()->assertSee('Tambah Akun COA');
    $this->get(route('coa.edit', ChartOfAccount::first()))->assertOk()->assertSee('Edit Akun COA');
});

it('creates and deactivates an account and rejects duplicate or mismatched codes', function () {
    $data = ['code' => '11299', 'name' => 'Piutang Karyawan', 'category' => 'Harta',
        'account_group' => 'Piutang', 'normal_balance' => 'Debit', 'description' => 'Pinjaman karyawan', 'is_active' => true];
    $this->post(route('coa.store'), $data)->assertRedirect(route('coa.index'));
    $account = ChartOfAccount::where('code', '11299')->firstOrFail();
    $this->postJson(route('coa.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->postJson(route('coa.store'), array_replace($data, ['code' => '21299']))->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->postJson(route('coa.store'), array_replace($data, ['code' => 'ABC']))->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->put(route('coa.update', $account), array_replace($data, ['is_active' => false]))->assertRedirect(route('coa.index'));
    expect($account->refresh()->is_active)->toBeFalse();
});

it('enforces read and write permissions separately', function () {
    $this->user->removeRole('owner');
    $this->get(route('coa.index'))->assertForbidden();
    $this->get(route('coa.create'))->assertForbidden();
    $this->user->givePermissionTo('coa.read');
    $this->get(route('coa.index'))->assertOk();
    $this->postJson(route('coa.store'), [])->assertForbidden();
    $this->putJson(route('coa.update', ChartOfAccount::first()), [])->assertForbidden();
});
