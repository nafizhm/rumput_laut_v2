<?php

use App\Models\{AccountingSetting, ChartOfAccount, JournalEntry, Transaction, User};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-10-10 12:00:00', 'Asia/Jakarta'));
    foreach ([
        '0001_01_01_000000_create_users_table.php', '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php', '2026_10_07_000002_create_chart_of_accounts_table.php',
        '2026_10_07_000012_create_accounting_journals.php',
    ] as $migration) (require database_path('migrations/'.$migration))->up();
    Schema::create('expense_categories', function (Blueprint $table) {
        $table->id(); $table->string('name');
    });
    Schema::create('transactions', function (Blueprint $table) {
        $table->id(); $table->string('type'); $table->date('date'); $table->bigInteger('amount');
        $table->string('description')->nullable(); $table->text('notes')->nullable();
        $table->foreignId('expense_category_id')->nullable()->constrained(); $table->timestamps();
    });
    (require database_path('migrations/2026_10_10_000004_link_cash_transactions_to_coa.php'))->up();
    (require database_path('migrations/2026_10_13_000001_create_transaction_histories_table.php'))->up();
    Schema::table('journal_entries', function (Blueprint $table) {
        $table->nullableMorphs('source'); $table->boolean('is_automatic')->default(false);
    });
    Schema::create('operational_events', function (Blueprint $table) {
        $table->id(); $table->string('source_key')->unique(); $table->string('source_type');
        $table->unsignedBigInteger('source_id'); $table->string('event'); $table->json('snapshot');
        $table->foreignId('journal_entry_id')->nullable()->constrained(); $table->unsignedInteger('version')->default(0);
        $table->boolean('cancelled')->default(false); $table->timestamps();
    });
    \App\Models\Menu::create(['name' => 'Keuangan', 'is_active' => true]);
    $this->seed(Database\Seeders\ChartOfAccountSeeder::class);
    $this->actingAs(User::create(['name' => 'COA Tester', 'email' => 'cash-coa@example.test', 'password' => 'password']));
    $this->grantModuleAccess(auth()->user(), ['manajemen-pemasukan-pengeluaran']);
    $this->withoutVite();
    AccountingSetting::whereKey(1)->update(['start_date' => '2026-10-01']);
    $this->bank = ChartOfAccount::where('code', '11103')->firstOrFail();
    $this->account = ChartOfAccount::create(['code' => '53999', 'name' => 'Biaya Listrik Uji', 'category' => 'Biaya', 'account_group' => 'Biaya Operasional', 'normal_balance' => 'Debit', 'is_active' => true]);
    $this->payload = ['type' => 'expense', 'date' => '2026-10-10', 'amount' => 500000,
        'description' => 'Pembayaran listrik', 'chart_of_account_id' => $this->account->id, 'cash_account_id' => $this->bank->id];
});

afterEach(function () { Carbon::setTestNow(); });

it('posts a balanced expense journal to the selected COA and bank', function () {
    $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), $this->payload)->assertOk();
    $transaction = Transaction::firstOrFail();
    $journal = JournalEntry::firstOrFail();
    expect($transaction->expense_category_id)->toBeNull()->and($journal->status)->toBe('posted')
        ->and($journal->lines()->sum('debit_cents'))->toBe(50000000)
        ->and($journal->lines()->sum('credit_cents'))->toBe(50000000)
        ->and($journal->lines()->where('account_code', '53999')->first()->debit_cents)->toBe(50000000)
        ->and($journal->lines()->where('account_code', '11103')->first()->credit_cents)->toBe(50000000);
    $this->get(route('manajemen-pemasukan-pengeluaran.index'))->assertOk()->assertSee('Akun COA Transaksi')->assertSee('Akun Kas / Bank')->assertDontSee('Pilih kategori');
    $this->getJson(route('transaction-data'))->assertOk()->assertJsonPath('data.0.account.code', '53999')->assertJsonPath('data.0.cash_account.code', '11103');
});

it('balances incoming capital against the selected cash account without treating it as revenue', function () {
    $capital = ChartOfAccount::create(['code' => '39999', 'name' => 'Setoran Modal Uji', 'category' => 'Modal', 'account_group' => 'Modal', 'normal_balance' => 'Kredit', 'is_active' => true]);
    $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), array_merge($this->payload, ['type' => 'income', 'chart_of_account_id' => $capital->id]))->assertOk();
    $journal = JournalEntry::firstOrFail();
    expect($journal->lines()->where('account_code', '11103')->first()->debit_cents)->toBe(50000000)
        ->and($journal->lines()->where('account_code', '39999')->first()->credit_cents)->toBe(50000000)
        ->and($journal->lines()->count())->toBe(2);
});

it('rejects invalid accounts and rolls back transactions whose journal cannot be created', function () {
    foreach ([['cash_account_id' => $this->account->id], ['chart_of_account_id' => $this->bank->id], ['amount' => 0], ['amount' => 1.5], ['chart_of_account_id' => null]] as $invalid) {
        $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), array_merge($this->payload, $invalid))->assertUnprocessable();
    }
    $this->account->update(['is_active' => false]);
    $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), $this->payload)->assertUnprocessable();
    $this->account->update(['is_active' => true]);
    $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), array_merge($this->payload, ['date' => '2026-09-30']))->assertUnprocessable()->assertJsonValidationErrors('date');
    expect(Transaction::count())->toBe(0)->and(JournalEntry::count())->toBe(0)->and(DB::table('operational_events')->count())->toBe(0);
});

it('reverses posted journals on edits and deletion without duplicating unchanged entries', function () {
    $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), $this->payload)->assertOk();
    $transaction = Transaction::firstOrFail();
    $changed = array_merge($this->payload, ['amount' => 750000, 'change_reason' => 'Koreksi nominal listrik']);
    $this->putJson(route('manajemen-pemasukan-pengeluaran.update', $transaction), $changed)->assertOk();
    expect(JournalEntry::count())->toBe(3);
    $this->putJson(route('manajemen-pemasukan-pengeluaran.update', $transaction), $changed)->assertOk();
    expect(JournalEntry::count())->toBe(3);
    $this->deleteJson(route('manajemen-pemasukan-pengeluaran.destroy', $transaction), ['change_reason' => 'Salah input'])->assertOk();
    expect(JournalEntry::count())->toBe(4)->and(Transaction::count())->toBe(0);
    foreach (JournalEntry::all() as $entry) expect($entry->lines()->sum('debit_cents'))->toBe($entry->lines()->sum('credit_cents'));
    $lines = DB::table('journal_lines')->where('account_code', '11103');
    expect($lines->sum('debit_cents'))->toBe($lines->sum('credit_cents'));
});

it('leaves legacy data and its category untouched and prevents implicit conversion', function () {
    $category = DB::table('expense_categories')->insertGetId(['name' => 'Kategori Lama']);
    $old = Transaction::create(['type' => 'expense', 'date' => '2026-10-01', 'amount' => 100000, 'expense_category_id' => $category]);
    $this->get(route('manajemen-pemasukan-pengeluaran.index'))->assertOk()->assertSee('Kategori Lama');
    $this->putJson(route('manajemen-pemasukan-pengeluaran.update', $old), $this->payload)->assertUnprocessable()->assertJsonValidationErrors('chart_of_account_id');
    expect($old->refresh()->chart_of_account_id)->toBeNull()->and($old->expense_category_id)->toBe($category)
        ->and($old->amount)->toBe(100000)->and(JournalEntry::count())->toBe(0);
});

it('creates balanced draft journals for future transactions', function () {
    $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), array_merge($this->payload, ['date' => '2026-10-11']))->assertOk();
    $journal = JournalEntry::firstOrFail();
    expect($journal->status)->toBe('draft')->and($journal->lines()->sum('debit_cents'))->toBe($journal->lines()->sum('credit_cents'));
});


it('keeps snapshots and reasons after deletion and rejects changes without reasons', function () {
    $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), $this->payload)->assertOk();
    $transaction = Transaction::firstOrFail();
    $this->putJson(route('manajemen-pemasukan-pengeluaran.update', $transaction), array_merge($this->payload, ['amount'=>750000]))
        ->assertUnprocessable()->assertJsonValidationErrors('change_reason');
    $this->deleteJson(route('manajemen-pemasukan-pengeluaran.destroy', $transaction))->assertUnprocessable();
    expect($transaction->refresh()->amount)->toBe(500000)->and(JournalEntry::count())->toBe(1);
    $changed = array_merge($this->payload, ['amount'=>750000, 'change_reason'=>'Koreksi nominal']);
    $this->putJson(route('manajemen-pemasukan-pengeluaran.update', $transaction), $changed)->assertOk();
    $this->putJson(route('manajemen-pemasukan-pengeluaran.update', $transaction), $changed)->assertOk();
    $this->deleteJson(route('manajemen-pemasukan-pengeluaran.destroy', $transaction), ['change_reason'=>'Transaksi duplikat'])->assertOk();
    $history = \App\Models\TransactionHistory::orderBy('id')->get();
    expect($history)->toHaveCount(3)->and($history[1]->before['amount'])->toBe(500000)
        ->and($history[1]->after['amount'])->toBe(750000)->and($history[2]->reason)->toBe('Transaksi duplikat')
        ->and($history[2]->actor_name)->toBe('COA Tester')->and(Transaction::count())->toBe(0);
    $this->get(route('manajemen-pemasukan-pengeluaran.history', ['transaction_id'=>$transaction->id]))
        ->assertOk()->assertSee('Transaksi duplikat')->assertSee('500.000')->assertSee('750.000');
    $this->actingAs(User::create(['name'=>'Tanpa akses', 'email'=>'no-history@example.test', 'password'=>'password']));
    $this->get(route('manajemen-pemasukan-pengeluaran.history', ['transaction_id'=>$transaction->id]))->assertForbidden();
});

it('explains a negative income correction whose original is outside the selected period', function () {
    $this->postJson(route('manajemen-pemasukan-pengeluaran.store'), array_merge($this->payload, ['type'=>'income', 'date'=>'2026-10-01']))->assertOk();
    $transaction = Transaction::firstOrFail();
    $this->deleteJson(route('manajemen-pemasukan-pengeluaran.destroy', $transaction), ['change_reason'=>'Salah input'])->assertOk();
    $summary = app(\App\Services\Finance\CashFlowService::class)->summary(Carbon::parse('2026-10-10'), Carbon::parse('2026-10-10'), 'income');
    $income = collect($summary)->firstWhere('id', 5);
    expect($income['amount'])->toBe(-500000)->and($income['transactions_formatted'])->toBe('Rp 0')
        ->and($income['corrections_formatted'])->toBe('Rp -500.000')
        ->and($income['details'][0]['activity'])->toBe('Koreksi / Pembatalan')
        ->and($income['details'][0]['explanation'])->toContain('01/10/2026')
        ->and($income['details'][0]['history_url'])->toContain('transaction_id='.$transaction->id);
});
