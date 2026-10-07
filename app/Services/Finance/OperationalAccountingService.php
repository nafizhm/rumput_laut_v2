<?php

namespace App\Services\Finance;

use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use App\Models\FarmerPayment;
use App\Models\GoodsReceipt;
use App\Models\HarvestTransaction;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\ProductionRun;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Transactional, idempotent journals. Posted entries are corrected by reversal. */
class OperationalAccountingService
{
    private const ACCOUNTS = [
        '11101' => ['Kas Operasional', 'Harta', 'Kas dan Bank', 'Debit'],
        '11201' => ['Piutang Petani', 'Harta', 'Piutang', 'Debit'],
        '11202' => ['Piutang Usaha Pabrik / Konsumen', 'Harta', 'Piutang', 'Debit'],
        '11301' => ['Persediaan Rumput Laut Basah', 'Harta', 'Persediaan', 'Debit'],
        '11304' => ['Persediaan Dalam Proses (WIP)', 'Harta', 'Persediaan', 'Debit'],
        '11305' => ['Persediaan Barang Jadi Rumput Laut', 'Harta', 'Persediaan', 'Debit'],
        '11401' => ['Uang Muka Pembelian', 'Harta', 'Uang Muka', 'Debit'],
        '21201' => ['Uang Muka Konsumen', 'Utang', 'Uang Muka Penjualan', 'Kredit'],
        '41102' => ['Penjualan Rumput Laut Kering', 'Pendapatan', 'Pendapatan Usaha', 'Kredit'],
        '42102' => ['Pendapatan Lain-lain', 'Pendapatan', 'Pendapatan Lainnya', 'Kredit'],
        '51103' => ['Harga Pokok Penjualan Barang Jadi Rumput Laut', 'Biaya', 'Harga Pokok Penjualan', 'Debit'],
        '54102' => ['Biaya Lain-lain', 'Biaya', 'Biaya Lainnya', 'Debit'],
    ];

    public function enabled(): bool
    {
        return Schema::hasTable('operational_events');
    }

    public function loan(Loan $loan): void
    {
        $this->record($loan, 'loan', $loan->loan_date->format('Y-m-d'), 'Pinjaman petani '.$loan->farmer->name,
            [['11201', $loan->amount * 100, 0], ['11101', 0, $loan->amount * 100]]);
    }

    public function harvest(HarvestTransaction $harvest): void
    {
        $this->record($harvest, 'purchase', $harvest->harvest_date->format('Y-m-d'), 'Pembelian '.$harvest->batch_code,
            [['11401', $harvest->total_harvest_value * 100, 0], ['11101', 0, $harvest->cash_payout * 100], ['11201', 0, $harvest->debt_deduction * 100]]);
    }

    public function farmerPayment(FarmerPayment $payment): void
    {
        $this->record($payment, 'farmer-payment', $payment->payment_date->format('Y-m-d'), 'Pembayaran utang petani '.$payment->farmer->name,
            [['11101', $payment->amount * 100, 0], ['11201', 0, $payment->amount * 100]]);
    }

    public function receipt(GoodsReceipt $receipt): void
    {
        if (! $receipt->harvest_transaction_id) {
            return;
        }
        $cost = (int) $receipt->harvest->total_harvest_value * 100;
        $this->record($receipt, 'receipt', $receipt->received_date->format('Y-m-d'), 'Penerimaan '.$receipt->number,
            [['11301', $cost, 0], ['11401', 0, $cost]]);
    }

    public function productionStart(ProductionRun $run): void
    {
        $this->record($run, 'production-start', $run->started_date->format('Y-m-d'), 'Bahan masuk proses '.$run->number,
            [['11304', $run->material_cost_cents + $run->processing_cost_cents, 0], ['11301', 0, $run->material_cost_cents], ['11101', 0, $run->processing_cost_cents]]);
    }

    public function productionPack(ProductionRun $run): void
    {
        $wip = $run->material_cost_cents + $run->processing_cost_cents;
        $this->record($run, 'production-pack', $run->packed_date->format('Y-m-d'), 'Barang jadi '.$run->number,
            [['11305', $wip + $run->packing_cost_cents, 0], ['11304', 0, $wip], ['11101', 0, $run->packing_cost_cents]]);
    }

    public function shipment(StockMovement $movement): void
    {
        if (! $movement->sales_transaction_id) {
            return;
        }
        $sale = SalesTransaction::findOrFail($movement->sales_transaction_id);
        $prior = StockMovement::where('sales_transaction_id', $sale->id)->where('id', '<', $movement->id)->sum('weight_kg');
        $grossCents = (int) $sale->total_sale_value * 100;
        $value = (int) round($grossCents * (($prior + $movement->weight_kg) / $sale->total_weight_kg))
            - (int) round($grossCents * ($prior / $sale->total_weight_kg));
        $this->record($movement, 'dispatch', $movement->movement_date->format('Y-m-d'), 'Penyerahan '.$sale->invoice_number,
            [['11202', $value, 0], ['41102', 0, $value], ['51103', (int) $movement->cost_cents, 0], ['11305', 0, (int) $movement->cost_cents]]);
        $this->salePayments($sale);
    }

    public function salePayments(SalesTransaction $sale): void
    {
        if (! $this->enabled()) {
            return;
        }
        $sale = SalesTransaction::lockForUpdate()->findOrFail($sale->id);
        $shipped = min((float) $sale->total_weight_kg, (float) $sale->stockMovements()->sum('weight_kg'));
        $receivable = (int) round($sale->total_sale_value * 100 * $shipped / $sale->total_weight_kg);
        $invoice = $sale->invoice;
        if ($invoice) {
            foreach ($invoice->payments()->orderBy('payment_date')->orderBy('id')->lockForUpdate()->get() as $payment) {
                $amount = (int) $payment->amount * 100;
                $againstReceivable = min($amount, $receivable);
                $receivable -= $againstReceivable;
                $this->record($payment, 'invoice-payment', $payment->payment_date->format('Y-m-d'), 'Pembayaran '.$invoice->number,
                    [['11101', $amount, 0], ['11202', 0, $againstReceivable], ['21201', 0, $amount - $againstReceivable]]);
            }
        } elseif ((int) $sale->paid_amount > 0) {
            $amount = (int) $sale->paid_amount * 100;
            $againstReceivable = min($amount, $receivable);
            $this->record($sale, 'sale-payment', $sale->payment_date->format('Y-m-d'), 'Pelunasan '.$sale->invoice_number,
                [['11101', $amount, 0], ['11202', 0, $againstReceivable], ['21201', 0, $amount - $againstReceivable]]);
        }
    }

    public function transaction(Transaction $transaction): void
    {
        $amount = (int) $transaction->amount * 100;
        $income = $transaction->type === 'income';
        $this->record($transaction, 'cash-transaction', $transaction->date->format('Y-m-d'), $transaction->description ?: 'Transaksi kas',
            $income ? [['11101', $amount, 0], ['42102', 0, $amount]] : [['54102', $amount, 0], ['11101', 0, $amount]]);
    }

    public function record(Model $source, string $event, string $date, string $description, array $lines): void
    {
        if (! $this->enabled()) {
            return;
        }
        DB::transaction(function () use ($source, $event, $date, $description, $lines) {
            $setting = AccountingSetting::lockForUpdate()->findOrFail(1);
            if ($setting->start_date && Carbon::parse($date)->startOfDay()->lt($setting->start_date)) {
                throw ValidationException::withMessages(['date' => 'Tanggal transaksi tidak boleh sebelum mulai pembukuan.']);
            }
            $lines = array_values(array_filter($lines, fn ($l) => (int) $l[1] > 0 || (int) $l[2] > 0));
            if (array_sum(array_column($lines, 1)) !== array_sum(array_column($lines, 2))) {
                throw new \LogicException('Operational journal is not balanced.');
            }
            $key = $source->getMorphClass().':'.$source->getKey().':'.$event;
            $snapshot = ['date' => $date, 'description' => $description, 'lines' => $lines];
            $row = DB::table('operational_events')->where('source_key', $key)->lockForUpdate()->first();
            if ($row && ! $row->cancelled && json_decode($row->snapshot, true) === $snapshot) {
                return;
            }
            $effectiveDate = $date;
            if ($row?->journal_entry_id) {
                $old = JournalEntry::lockForUpdate()->findOrFail($row->journal_entry_id);
                if ($old->status === 'posted') {
                    $effectiveDate = max($date, now('Asia/Jakarta')->format('Y-m-d'));
                    $this->reverseEntry($old, $effectiveDate);
                } else {
                    DB::table('operational_events')->where('id', $row->id)->update(['journal_entry_id' => null]);
                    $old->lines()->delete();
                    $old->delete();
                }
            }
            $version = ($row?->version ?? 0) + 1;
            $entry = null;
            if ($lines) {
                $posted = $setting->start_date && $effectiveDate <= now('Asia/Jakarta')->format('Y-m-d');
                $entry = JournalEntry::create(['entry_date' => $effectiveDate, 'kind' => 'operational', 'status' => $posted ? 'posted' : 'draft',
                    'description' => $description, 'source_reference' => 'op:'.hash('sha256', $key).':'.$version,
                    'source_type' => $source->getMorphClass(), 'source_id' => $source->getKey(), 'is_automatic' => true,
                    'created_by' => auth()->id(), 'created_by_name' => auth()->user()?->name ?? 'Sistem',
                    'posted_by' => $posted ? auth()->id() : null, 'posted_by_name' => $posted ? (auth()->user()?->name ?? 'Sistem') : null, 'posted_at' => $posted ? now() : null]);
                $entry->number = 'OP-'.str_replace('-', '', $effectiveDate).'-'.str_pad($entry->id, 6, '0', STR_PAD_LEFT);
                $entry->save();
                foreach ($lines as [$code,$debit,$credit]) {
                    $account = $this->account($code);
                    $entry->lines()->create(['chart_of_account_id' => $account->id, 'debit_cents' => (int) $debit, 'credit_cents' => (int) $credit,
                        'account_code' => $account->code, 'account_name' => $account->name, 'category' => $account->category, 'account_group' => $account->account_group, 'normal_balance' => $account->normal_balance]);
                }
            }
            $attributes = ['source_type' => $source->getMorphClass(), 'source_id' => $source->getKey(), 'event' => $event,
                'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'journal_entry_id' => $entry?->id, 'version' => $version, 'cancelled' => false, 'updated_at' => now()];
            if ($row) {
                DB::table('operational_events')->where('id', $row->id)->update($attributes);
            } else {
                DB::table('operational_events')->insert($attributes + ['source_key' => $key, 'created_at' => now()]);
            }
        });
    }

    public function cancel(Model $source, string $event): void
    {
        if (! $this->enabled()) {
            return;
        }
        DB::transaction(function () use ($source, $event) {
            AccountingSetting::lockForUpdate()->findOrFail(1);
            $row = DB::table('operational_events')->where('source_key', $source->getMorphClass().':'.$source->getKey().':'.$event)->lockForUpdate()->first();
            if (! $row || $row->cancelled) {
                return;
            }
            if ($row->journal_entry_id) {
                $entry = JournalEntry::lockForUpdate()->findOrFail($row->journal_entry_id);
                if ($entry->status === 'posted') {
                    $this->reverseEntry($entry, now('Asia/Jakarta')->format('Y-m-d'));
                } else {
                    DB::table('operational_events')->where('id', $row->id)->update(['journal_entry_id' => null]);
                    $entry->lines()->delete();
                    $entry->delete();
                }
            }
            DB::table('operational_events')->where('id', $row->id)->update(['cancelled' => true, 'updated_at' => now()]);
        });
    }

    private function reverseEntry(JournalEntry $entry, string $date): void
    {
        if ($entry->reversal()->exists()) {
            return;
        }
        $reverse = JournalEntry::create(['entry_date' => $date, 'kind' => 'operational-reversal', 'status' => 'posted', 'reversal_of_id' => $entry->id,
            'description' => 'Pembalik otomatis '.$entry->number, 'is_automatic' => true, 'source_type' => $entry->source_type, 'source_id' => $entry->source_id,
            'created_by' => auth()->id(), 'created_by_name' => auth()->user()?->name ?? 'Sistem', 'posted_by' => auth()->id(), 'posted_by_name' => auth()->user()?->name ?? 'Sistem', 'posted_at' => now()]);
        $reverse->number = 'OPR-'.str_replace('-', '', $date).'-'.str_pad($reverse->id, 6, '0', STR_PAD_LEFT);
        $reverse->save();
        foreach ($entry->lines as $line) {
            $reverse->lines()->create($line->only(['chart_of_account_id', 'account_code', 'account_name', 'category', 'account_group', 'normal_balance']) + ['debit_cents' => $line->credit_cents, 'credit_cents' => $line->debit_cents]);
        }
    }

    private function account(string $code): ChartOfAccount
    {
        [$name,$category,$group,$normal] = self::ACCOUNTS[$code];
        $account = ChartOfAccount::firstOrCreate(['code' => $code], ['name' => $name, 'category' => $category, 'account_group' => $group, 'normal_balance' => $normal, 'is_active' => true]);
        $account = ChartOfAccount::lockForUpdate()->findOrFail($account->id);
        if (! $account->is_active || $account->category !== $category || $account->normal_balance !== $normal) {
            throw ValidationException::withMessages(['account' => 'Akun '.$code.' harus aktif dengan kategori dan saldo normal yang sesuai.']);
        }

        return $account;
    }

    public function postPending(): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        return DB::transaction(function () {
            $setting = AccountingSetting::lockForUpdate()->findOrFail(1);
            if (! $setting->start_date) {
                return 0;
            }
            $entries = JournalEntry::where('is_automatic', true)->where('status', 'draft')->whereBetween('entry_date', [$setting->start_date->format('Y-m-d'), now('Asia/Jakarta')->format('Y-m-d')])->lockForUpdate()->get();
            foreach ($entries as $entry) {
                foreach ($entry->lines as $line) {
                    $this->account($line->account_code);
                }
                $entry->update(['status' => 'posted', 'posted_at' => now(), 'posted_by' => auth()->id(), 'posted_by_name' => auth()->user()?->name ?? 'Sistem']);
            }

            return $entries->count();
        });
    }
}
