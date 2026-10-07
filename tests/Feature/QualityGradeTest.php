<?php

use App\Models\QualityGrade;
use App\Models\Menu;
use App\Models\User;
use Database\Seeders\QualityGradeSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php',
        '2026_10_07_000007_create_quality_grades_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    foreach (['owner', 'superadmin', 'admin'] as $name) Role::create(['name' => $name, 'guard_name' => 'web']);
    Menu::create(['name' => 'Master', 'is_active' => true]);
    $this->seed(QualityGradeSeeder::class);
    $this->withoutVite();
    $this->user = User::create(['name' => 'Admin', 'email' => 'quality@example.test', 'password' => 'password']);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
    $this->payload = ['code' => 'Q06', 'name' => 'Kualitas Khusus', 'description' => 'Sesuai pembeli',
        'criteria' => 'Kriteria disepakati client', 'sort_order' => 6, 'is_active' => true];
});

it('seeds six classifications and preserves edits when seeded again', function () {
    expect(QualityGrade::orderBy('sort_order')->pluck('code')->all())->toBe(['Q00', 'Q01', 'Q02', 'Q03', 'Q04', 'Q05']);
    expect(QualityGrade::whereNotNull('criteria')->count())->toBe(0);
    $grade = QualityGrade::where('code', 'Q01')->firstOrFail();
    $grade->update(['name' => 'Grade Premium', 'criteria' => 'Kriteria client', 'is_active' => false]);
    $this->seed(QualityGradeSeeder::class);
    expect(QualityGrade::count())->toBe(6)->and($grade->refresh()->name)->toBe('Grade Premium')
        ->and($grade->criteria)->toBe('Kriteria client')->and($grade->is_active)->toBeFalse()
        ->and(Menu::where('route', 'kualitas-barang.index')->count())->toBe(1)
        ->and(Menu::where('route', 'kualitas-barang.index')->first()->parent->name)->toBe('Master');
});

it('renders list and forms and filters by quality and status', function () {
    $this->get(route('kualitas-barang.index'))->assertOk()->assertSee('Grade A')->assertSee('Belum ditetapkan')->assertSee('Tambah Kualitas');
    $this->get(route('kualitas-barang.create'))->assertOk()->assertSee('Kriteria Penilaian');
    $grade = QualityGrade::where('code', 'Q05')->firstOrFail();
    $this->get(route('kualitas-barang.edit', $grade))->assertOk()->assertSee('Reject');
    $this->get(route('kualitas-barang.index', ['q' => 'Q05']))->assertOk()->assertSee('Reject')->assertDontSee('Grade A');
    $grade->update(['is_active' => false]);
    $this->get(route('kualitas-barang.index', ['status' => 'inactive']))->assertOk()->assertSee('Reject')->assertDontSee('Grade A');
    $this->get(route('kualitas-barang.index', ['status' => 'active']))->assertOk()->assertSee('Grade A')->assertDontSee('Tidak layak menjadi stok siap jual');
});

it('normalizes codes, validates uniqueness and allows deactivation without deleting records', function () {
    $this->post(route('kualitas-barang.store'), array_replace($this->payload, ['code' => ' q06 ']))->assertRedirect(route('kualitas-barang.index'));
    $grade = QualityGrade::where('code', 'Q06')->firstOrFail();
    $this->postJson(route('kualitas-barang.store'), array_replace($this->payload, ['code' => 'q06']))->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->postJson(route('kualitas-barang.store'), array_replace($this->payload, ['code' => 'Q 07', 'name' => '', 'sort_order' => -1]))->assertUnprocessable()->assertJsonValidationErrors(['code', 'name', 'sort_order']);
    $this->put(route('kualitas-barang.update', $grade), array_replace($this->payload, ['criteria' => 'Kriteria revisi', 'is_active' => false]))->assertRedirect(route('kualitas-barang.index'));
    expect($grade->refresh()->is_active)->toBeFalse()->and($grade->criteria)->toBe('Kriteria revisi')->and(QualityGrade::count())->toBe(7);
});

it('enforces separate read and write permissions and login', function () {
    $this->user->removeRole('admin');
    $this->get(route('kualitas-barang.index'))->assertForbidden();
    $this->postJson(route('kualitas-barang.store'), $this->payload)->assertForbidden();
    $this->user->givePermissionTo('quality.read');
    $this->get(route('kualitas-barang.index'))->assertOk()->assertDontSee('Tambah Kualitas');
    $this->get(route('kualitas-barang.create'))->assertForbidden();
    $this->putJson(route('kualitas-barang.update', QualityGrade::first()), $this->payload)->assertForbidden();
    auth()->logout();
    $this->get(route('kualitas-barang.index'))->assertRedirect(route('login'));
});
