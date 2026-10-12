<?php

use App\Models\{Farmer, FarmerPayment, JournalEntry, Loan};
use App\Services\Finance\OperationalAccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

it('records a farmer debt transfer without cash and does not duplicate reordered JSON snapshots', function () {
    foreach (['0001_01_01_000000_create_users_table.php', '2026_04_26_062312_create_farmers_table.php', '2026_04_26_062518_create_loans_table.php', '2026_07_13_141853_create_farmer_payments_table.php', '2026_10_07_000002_create_chart_of_accounts_table.php', '2026_10_07_000012_create_accounting_journals.php', '2026_10_12_000008_mark_farmer_debt_transfers.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Schema::table('journal_entries', function (Blueprint $table) {
        $table->nullableMorphs('source');
        $table->boolean('is_automatic')->default(false);
    });
    Schema::create('operational_events', function (Blueprint $table) {
        $table->id();
        $table->string('source_key')->unique();
        $table->string('source_type');
        $table->unsignedBigInteger('source_id');
        $table->string('event');
        $table->json('snapshot');
        $table->unsignedBigInteger('journal_entry_id')->nullable();
        $table->unsignedInteger('version');
        $table->boolean('cancelled');
        $table->timestamps();
    });
    $from = Farmer::create(['name' => 'Suhari 001']);
    $to = Farmer::create(['name' => 'Suhari 004']);
    $loan = Loan::create(['farmer_id' => $to->id, 'loan_date' => '2026-09-06', 'amount' => 29178800]);
    $loan->forceFill(['transfer_from_farmer_id' => $from->id])->save();
    $payment = FarmerPayment::create(['farmer_id' => $from->id, 'payment_date' => '2026-09-06', 'amount' => 29178800]);
    $payment->forceFill(['transfer_to_loan_id' => $loan->id])->save();
    $service = app(OperationalAccountingService::class);
    $service->loan($loan);
    $service->farmerPayment($payment);
    $entry = JournalEntry::with('lines')->sole();
    expect($entry->lines->pluck('account_code')->all())->toBe(['11201', '11201']);
    expect((int) $entry->lines->sum('debit_cents'))->toBe(2917880000);
    expect((int) $entry->lines->sum('credit_cents'))->toBe(2917880000);
    foreach (DB::table('operational_events')->get() as $event) {
        $snapshot = json_decode($event->snapshot, true);
        DB::table('operational_events')->where('id', $event->id)->update(['snapshot' => json_encode(['lines' => $snapshot['lines'], 'description' => $snapshot['description'], 'date' => $snapshot['date']])]);
    }
    $service->loan($loan);
    $service->farmerPayment($payment);
    expect(JournalEntry::count())->toBe(1);
    expect(DB::table('operational_events')->count())->toBe(2);
});
