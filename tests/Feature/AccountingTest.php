<?php

use App\Models\{AccountingSetting, ChartOfAccount, JournalEntry, JournalLine, Menu, User};
use App\Services\Finance\{AccountingMoney, FinancialReportService};
use Database\Seeders\{ChartOfAccountSeeder, AccountingMenuSeeder};
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

function accountingTestLine(string $code, $debit = '0', $credit = '0'): array {
    return ['chart_of_account_id' => ChartOfAccount::where('code', $code)->firstOrFail()->id, 'debit' => (string) $debit, 'credit' => (string) $credit];
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'Asia/Jakarta'));
    foreach ([
        '0001_01_01_000000_create_users_table.php', '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php', '2026_10_07_000002_create_chart_of_accounts_table.php',
        '2026_10_07_000012_create_accounting_journals.php',
    ] as $migration) (require database_path('migrations/'.$migration))->up();
    foreach (['owner', 'superadmin', 'admin'] as $name) Role::create(['name' => $name, 'guard_name' => 'web']);
    Menu::create(['name' => 'Keuangan', 'is_active' => true]);
    $this->seed(ChartOfAccountSeeder::class); $this->seed(AccountingMenuSeeder::class);
    $this->user = User::create(['name' => 'Owner', 'email' => 'journal@example.test', 'password' => 'password']);
    $this->user->assignRole('owner'); $this->actingAs($this->user); $this->withoutVite();
    $this->draft = function (array $lines, string $date = '2026-10-01', string $kind = 'general'): JournalEntry {
        $this->post(route($kind === 'opening' ? 'saldo-awal.store' : 'jurnal.store'), [
            'entry_date' => $date, 'description' => 'Transaksi akuntansi untuk pengujian', 'lines' => $lines,
        ])->assertRedirect();
        return JournalEntry::orderByDesc('id')->firstOrFail();
    };
    $this->postEntry = function (array $lines, string $date = '2026-10-01', string $kind = 'general'): JournalEntry {
        $entry = ($this->draft)($lines, $date, $kind);
        $this->post(route('jurnal.post', $entry), ['confirm' => 1])->assertRedirect(route('jurnal.show', $entry));
        return $entry->refresh();
    };
    $this->opening = fn (string $date = '2026-09-01') => ($this->postEntry)([
        accountingTestLine('11101', '1000'), accountingTestLine('31101', '0', '1000'),
    ], $date, 'opening');
});
afterEach(function () { Carbon::setTestNow(); });

it('adds finance menus and displays unavailable reports until verified opening balances are posted', function () {
    $this->seed(AccountingMenuSeeder::class);
    expect(Menu::whereIn('route', ['jurnal.index', 'saldo-awal.index', 'neraca.index', 'laba-rugi.index'])->count())->toBe(4);
    $this->get(route('neraca.index', ['month' => '2026-10']))->assertOk()->assertSee('Laporan belum tersedia')->assertDontSee('Rp 0,00');
    $this->get(route('laba-rugi.index', ['month' => '2026-10']))->assertOk()->assertSee('Saldo awal belum diposting');
    $this->get(route('jurnal.index'))->assertOk()->assertSee('Jurnal Umum');
    $this->get(route('saldo-awal.index'))->assertOk()->assertSee('Isi Saldo Awal');
    $this->get(route('jurnal.create'))->assertOk()->assertSee('Total Debit');
    $this->get(route('saldo-awal.create'))->assertOk()->assertSee('Tanggal Mulai Pembukuan');
    $this->getJson(route('neraca.index', ['month' => '2026-13']))->assertUnprocessable()->assertJsonValidationErrors('month');
});

it('keeps drafts out of reports and rejects unbalanced, future or pre-opening posting', function () {
    $entry = ($this->draft)([accountingTestLine('11101', '100'), accountingTestLine('41101', '0', '80')]);
    $this->postJson(route('jurnal.post', $entry), ['confirm' => 1])->assertUnprocessable();
    ($this->opening)();
    $this->postJson(route('jurnal.post', $entry), ['confirm' => 1])->assertUnprocessable()->assertJsonValidationErrors('journal');
    expect($entry->refresh()->status)->toBe('draft');
    $report = app(FinancialReportService::class)->month('2026-10');
    expect($report['net_profit'])->toBe(0)->and($report['asset_total'])->toBe(100000);
    $future = ($this->draft)([accountingTestLine('11101', '10'), accountingTestLine('41101', '0', '10')], '2026-12-01');
    $this->postJson(route('jurnal.post', $future), ['confirm' => 1])->assertUnprocessable();
    $this->postJson(route('jurnal.store'), ['entry_date' => '2026-08-31', 'description' => 'Sebelum pembukuan', 'lines' => [accountingTestLine('11101', '10'), accountingTestLine('41101', '0', '10')]])->assertUnprocessable();
});

it('posts only one opening journal and prevents income or calculated profit accounts as opening balances', function () {
    $this->postJson(route('saldo-awal.store'), [
        'entry_date' => '2026-09-01', 'description' => 'Saldo awal tidak valid',
        'lines' => [accountingTestLine('11101', '10'), accountingTestLine('41101', '0', '10')],
    ])->assertUnprocessable();
    $opening = ($this->opening)();
    expect(AccountingSetting::find(1)->start_date->format('Y-m-d'))->toBe('2026-09-01');
    $this->postJson(route('jurnal.post', $opening), ['confirm' => 1])->assertUnprocessable();
    $this->postJson(route('saldo-awal.store'), [
        'entry_date' => '2026-09-01', 'description' => 'Saldo awal kedua',
        'lines' => [accountingTestLine('11101', '10'), accountingTestLine('31101', '0', '10')],
    ])->assertUnprocessable();
    $this->postJson(route('jurnal.store'), [
        'entry_date' => '2026-10-01', 'description' => 'Laba dicatat dua kali',
        'lines' => [accountingTestLine('11101', '10'), accountingTestLine('32102', '0', '10')],
    ])->assertUnprocessable();
    $this->get(route('neraca.index', ['month' => '2026-08']))->assertOk()->assertSee('sebelum tanggal mulai pembukuan');
});

it('calculates cumulative balance sheets and monthly accrual profits including contra accounts and HPP', function () {
    ($this->postEntry)([accountingTestLine('11101', '1000'), accountingTestLine('11301', '2000'), accountingTestLine('31101', '0', '3000')], '2026-09-01', 'opening');
    ($this->postEntry)([accountingTestLine('11101', '500'), accountingTestLine('41101', '0', '500')], '2026-09-15');
    ($this->postEntry)([accountingTestLine('11202', '1000'), accountingTestLine('41101', '0', '1000')], '2026-10-02');
    ($this->postEntry)([accountingTestLine('52104', '200'), accountingTestLine('11101', '0', '200')], '2026-10-03');
    ($this->postEntry)([accountingTestLine('51101', '400'), accountingTestLine('11301', '0', '400')], '2026-10-04');
    ($this->postEntry)([accountingTestLine('41201', '50'), accountingTestLine('11101', '0', '50')], '2026-10-05');
    ($this->postEntry)([accountingTestLine('52201', '50'), accountingTestLine('12202', '0', '50')], '2026-10-06');
    ($this->postEntry)([accountingTestLine('11202', '700'), accountingTestLine('41101', '0', '700')], '2026-11-01');
    ($this->draft)([accountingTestLine('11101', '999'), accountingTestLine('41101', '0', '999')]);
    $report = app(FinancialReportService::class)->month('2026-10');
    expect($report['totals']['sales'])->toBe(95000)->and($report['totals']['cogs'])->toBe(40000)
        ->and($report['gross_profit'])->toBe(55000)->and($report['net_profit'])->toBe(30000)
        ->and($report['asset_total'])->toBe(380000)->and($report['equity_total'])->toBe(380000)
        ->and($report['year_profit'])->toBe(80000)->and($report['difference'])->toBe(0)->and($report['missing_cogs'])->toBeFalse();
    $this->get(route('neraca.index', ['month' => '2026-10']))->assertOk()->assertSee('31 Oktober 2026')->assertSee('Rp 3.800,00')->assertSee('(Rp 50,00)');
    $this->get(route('laba-rugi.index', ['month' => '2026-10']))->assertOk()->assertSee('Rp 300,00')->assertSee('Harga Pokok Penjualan');
    expect(app(FinancialReportService::class)->month('2026-09')['net_profit'])->toBe(50000);
});

it('treats farmer loans and owner withdrawals as balance sheet movements rather than expenses', function () {
    ($this->opening)();
    ($this->postEntry)([accountingTestLine('11201', '500'), accountingTestLine('11101', '0', '500')]);
    ($this->postEntry)([accountingTestLine('31102', '100'), accountingTestLine('11101', '0', '100')]);
    $report = app(FinancialReportService::class)->month('2026-10');
    expect($report['net_profit'])->toBe(0)->and($report['asset_total'])->toBe(90000)
        ->and($report['equity_total'])->toBe(90000)->and($report['difference'])->toBe(0);
});

it('reverses posted journals once in the selected period without rewriting historical reports', function () {
    ($this->opening)();
    $entry = ($this->postEntry)([accountingTestLine('11101', '100'), accountingTestLine('41101', '0', '100')]);
    $this->putJson(route('jurnal.update', $entry), ['entry_date' => '2026-10-01', 'description' => 'Mengubah jurnal Posted', 'lines' => [accountingTestLine('11101', '1'), accountingTestLine('41101', '0', '1')]])->assertUnprocessable();
    $this->deleteJson(route('jurnal.destroy', $entry))->assertUnprocessable();
    $this->post(route('jurnal.reverse', $entry), ['entry_date' => '2026-11-10', 'reason' => 'Pembatalan transaksi setelah verifikasi', 'confirm' => 1])->assertRedirect();
    expect($entry->refresh()->status)->toBe('posted')->and($entry->reversal()->count())->toBe(1)
        ->and(app(FinancialReportService::class)->month('2026-10')['net_profit'])->toBe(10000)
        ->and(app(FinancialReportService::class)->month('2026-11')['net_profit'])->toBe(-10000)
        ->and(app(FinancialReportService::class)->month('2026-11')['asset_total'])->toBe(100000);
    $this->postJson(route('jurnal.reverse', $entry), ['entry_date' => '2026-11-10', 'reason' => 'Pembalik kedua', 'confirm' => 1])->assertUnprocessable();
    $this->get(route('jurnal.show', $entry))->assertOk()->assertSee('Jurnal pembalik');
});

it('preserves exact cents and warns when sales are posted without cost of goods sold', function () {
    ($this->opening)();
    $entry = ($this->postEntry)([accountingTestLine('11101', '0.10'), accountingTestLine('11101', '0.20'), accountingTestLine('41101', '0', '0.30')]);
    expect($entry->lines()->sum('debit_cents'))->toBe(30)->and(AccountingMoney::cents('0.29'))->toBe(29)
        ->and(app(FinancialReportService::class)->month('2026-10')['net_profit'])->toBe(30);
    $this->get(route('laba-rugi.index', ['month' => '2026-10']))->assertOk()->assertSee('belum ada jurnal HPP')->assertSee('Rp 0,30');
    $this->get(route('jurnal.show', $entry))->assertOk()->assertSee('Rp 0,30');
});

it('rechecks active accounts at posting and protects posted account classifications', function () {
    ($this->opening)();
    $entry = ($this->draft)([accountingTestLine('11101', '100'), accountingTestLine('41101', '0', '100')]);
    $salesAccount = ChartOfAccount::where('code', '41101')->first(); $salesAccount->update(['is_active' => false]);
    $this->postJson(route('jurnal.post', $entry), ['confirm' => 1])->assertUnprocessable();
    $cash = ChartOfAccount::where('code', '11101')->first();
    $data = $cash->only(['code', 'name', 'category', 'account_group', 'normal_balance', 'description', 'is_active']);
    $this->putJson(route('coa.update', $cash), array_replace($data, ['code' => '11109']))->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->put(route('coa.update', $cash), array_replace($data, ['name' => 'Kas Nama Baru']))->assertRedirect();
    expect(JournalLine::where('chart_of_account_id', $cash->id)->first()->account_name)->toBe('Kas Operasional');
});

it('enforces reading and posting permissions separately', function () {
    $entry = ($this->draft)([accountingTestLine('11101', '100'), accountingTestLine('41101', '0', '100')]);
    $this->user->removeRole('owner'); $this->user->givePermissionTo('journal.read');
    $this->get(route('jurnal.show', $entry))->assertOk()->assertDontSee('Posting Jurnal');
    $this->postJson(route('jurnal.post', $entry), ['confirm' => 1])->assertForbidden();
    $this->postJson(route('saldo-awal.store'), [])->assertForbidden();
    $this->get(route('neraca.index'))->assertForbidden();
    $this->user->givePermissionTo('financial-reports.read'); $this->get(route('neraca.index'))->assertOk();
    auth()->logout(); $this->get(route('jurnal.index'))->assertRedirect(route('login'));
});
