<?php

use App\Models\CompanyProfile;
use App\Models\DocumentNumberRule;
use App\Models\Menu;
use App\Models\User;
use App\Services\Setting\DocumentNumberService;
use App\Support\IndonesianNumber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php',
        '2026_10_10_000002_create_company_profile_and_document_numbering.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    foreach (['invoices', 'delivery_notes'] as $name) {
        Schema::create($name, function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
        });
    }
    $this->withoutVite();
    $this->user = User::create(['name' => 'Profile Tester', 'email' => 'profile@example.test', 'password' => 'password']);
    $this->actingAs($this->user);
    $this->payload = [
        'company_name' => 'PT Perusahaan Uji', 'address' => "Jl. Gudang 12\nBalikpapan", 'phone' => '081234567890',
        'bank_name' => 'Bank Uji', 'bank_account' => '123456789', 'bank_account_name' => 'PT Perusahaan Uji',
        'rules' => [
            'delivery_note' => ['format' => 'SJ/{SEQ}/{MM}/{YYYY}', 'padding' => 4, 'start_number' => 1, 'reset_period' => 'year'],
            'invoice' => ['format' => 'INV/{SEQ}/{MM}/{YYYY}', 'padding' => 4, 'start_number' => 1, 'reset_period' => 'year'],
        ],
    ];
});

it('protects profile access and saves reusable company data and numbering rules', function () {
    $this->get(route('company-profile.index'))->assertForbidden();
    $this->putJson(route('company-profile.update'), $this->payload)->assertForbidden();
    $this->user->givePermissionTo(['company-profile.read', 'company-profile.update']);
    $this->get(route('company-profile.index'))->assertOk()->assertSee('Aturan Nomor Surat Jalan');
    $this->put(route('company-profile.update'), $this->payload)->assertRedirect(route('company-profile.index'));
    expect(CompanyProfile::current()->company_name)->toBe('PT Perusahaan Uji')
        ->and(CompanyProfile::current()->address)->toBe("Jl. Gudang 12\nBalikpapan")
        ->and(CompanyProfile::current()->bank_account)->toBe('123456789');
    $this->get(route('company-profile.index'))->assertOk()->assertSee('PT Perusahaan Uji')->assertSee('081234567890');
});

it('rejects invalid numbering formats without changing the company or counters', function () {
    $this->user->givePermissionTo('company-profile.update');
    foreach (['INV/{BAD}/{YYYY}', 'INV/{SEQ}/{SEQ}', 'INV/{SEQ}'] as $format) {
        $payload = $this->payload;
        $payload['rules']['invoice']['format'] = $format;
        $this->putJson(route('company-profile.update'), $payload)->assertUnprocessable()->assertJsonValidationErrors('rules.invoice.format');
    }
    $payload = $this->payload;
    $payload['rules']['invoice']['reset_period'] = 'month';
    $payload['rules']['invoice']['format'] = 'INV/{SEQ}/{YYYY}';
    $this->putJson(route('company-profile.update'), $payload)->assertUnprocessable()->assertJsonValidationErrors('rules.invoice.format');
    expect(CompanyProfile::current()->company_name)->toBe('PT Albagro Indo Sejahtera')
        ->and(DB::table('document_number_counters')->count())->toBe(0);
});

it('allocates unique numbers by document date and keeps independent document sequences', function () {
    $service = app(DocumentNumberService::class);
    DB::table('invoices')->insert(['number' => 'INV/0001/10/2026']);
    expect($service->next('invoice', '2026-10-10'))->toBe('INV/0002/10/2026')
        ->and($service->next('invoice', '2026-11-01'))->toBe('INV/0003/11/2026')
        ->and($service->next('invoice', '2027-01-01'))->toBe('INV/0001/01/2027')
        ->and($service->next('delivery_note', '2026-10-10'))->toBe('SJ/0001/10/2026');
    DB::table('invoices')->delete();
    expect($service->next('invoice', '2026-10-10'))->toBe('INV/0004/10/2026');
});

it('supports monthly reset and configured formats without consuming numbers on rollback', function () {
    $rule = DocumentNumberRule::where('document_type', 'invoice')->firstOrFail();
    $rule->update(['format' => 'ALB-{YYYY}{MM}-{SEQ}', 'padding' => 3, 'start_number' => 10, 'reset_period' => 'month']);
    $service = app(DocumentNumberService::class);
    DB::beginTransaction();
    expect($service->next('invoice', '2026-10-10'))->toBe('ALB-202610-010');
    DB::rollBack();
    expect($service->next('invoice', '2026-10-10'))->toBe('ALB-202610-010')
        ->and($service->next('invoice', '2026-10-11'))->toBe('ALB-202610-011')
        ->and($service->next('invoice', '2026-11-01'))->toBe('ALB-202611-010');
    $rule->update(['format' => 'NEW-{SEQ}', 'reset_period' => 'never', 'start_number' => 20]);
    expect($service->next('invoice', '2026-10-10'))->toBe('NEW-020')
        ->and($service->next('invoice', '2027-01-01'))->toBe('NEW-021');
});

it('adds the profile menu and permissions without duplicating them', function () {
    $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $this->seed(Database\Seeders\CompanyProfileMenuSeeder::class);
    $this->seed(Database\Seeders\CompanyProfileMenuSeeder::class);
    expect(Menu::where('route', 'company-profile.index')->count())->toBe(1)
        ->and($role->refresh()->hasPermissionTo('company-profile.update'))->toBeTrue();
});

it('spells invoice amounts including zeros and fractions', function () {
    expect(IndonesianNumber::rupiah(0))->toBe('Nol rupiah')
        ->and(IndonesianNumber::rupiah(26000000))->toBe('Dua puluh enam juta rupiah')
        ->and(IndonesianNumber::rupiah(1500.25))->toBe('Seribu lima ratus rupiah dan dua puluh lima sen');
});
