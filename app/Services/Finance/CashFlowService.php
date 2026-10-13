<?php

namespace App\Services\Finance;

use App\Models\FarmerPayment;
use App\Models\HarvestTransaction;
use App\Models\HarvestPayment;
use App\Models\InvoicePayment;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\ProductionRun;
use App\Models\SalesTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class CashFlowService
{
    public function summary(Carbon $start, Carbon $end, string $type): array
    {
        if (Schema::hasTable('operational_events')) {
            return $this->journalSummary($start, $end, $type);
        }
        $groups = [];
        $add = function (int $id, string $category, string $direction, $rows) use (&$groups, $type) {
            if ($type !== 'all' && (($type === 'income') !== ($direction === 'Cash In'))) {
                return;
            }
            $amount = (int) collect($rows)->sum('amount');
            $groups[] = ['id' => $id, 'category' => $category, 'type' => $direction, 'amount' => $amount, 'amount_formatted' => $this->money($amount),
                'details' => collect($rows)->map(fn ($r) => ['id' => $r['id'], 'date' => Carbon::parse($r['date'])->format('d/m/Y'), 'description' => $r['description'], 'amount' => $this->money($r['amount'])])->values()];
        };
        $sales = InvoicePayment::with('invoice')->whereBetween('payment_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->get()->map(fn ($p) => [
            'id' => 'invoice-'.$p->id, 'date' => $p->payment_date, 'amount' => $p->amount, 'description' => 'Pembayaran invoice '.$p->invoice->number])->all();
        foreach (SalesTransaction::with('factory')->whereDoesntHave('invoice')->whereBetween('payment_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->where('paid_amount', '>', 0)->get() as $s) {
            $sales[] = ['id' => 'sale-'.$s->id, 'date' => $s->payment_date, 'amount' => $s->paid_amount, 'description' => 'Pelunasan '.$s->invoice_number.' / '.$s->factory->name];
        }
        $add(1, 'Penjualan Rumput Laut', 'Cash In', $sales);
        $add(6, 'Pembayaran Utang Petani', 'Cash In', FarmerPayment::with('farmer')->whereBetween('payment_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->get()->map(fn ($p) => [
            'id' => $p->id, 'date' => $p->payment_date, 'amount' => $p->amount, 'description' => 'Pembayaran utang '.$p->farmer->name]));
        $add(5, 'Pemasukan Lainnya', 'Cash In', Transaction::where('type', 'income')->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->get()->map(fn ($t) => [
            'id' => $t->id, 'date' => $t->date, 'amount' => $t->amount, 'description' => $t->description ?? 'Pemasukan']));
        $add(2, 'Pembayaran Tunai Panen', 'Cash Out', HarvestTransaction::with('farmer')->whereBetween('harvest_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->where('cash_payout', '>', 0)->get()->map(fn ($h) => [
            'id' => $h->id, 'date' => $h->harvest_date, 'amount' => $h->cash_payout, 'description' => 'Pembelian '.$h->batch_code.' / '.$h->farmer->name]));
        $add(3, 'Pinjaman Petani (Modal)', 'Cash Out', Loan::with('farmer')->whereBetween('loan_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->get()->map(fn ($l) => [
            'id' => $l->id, 'date' => $l->loan_date, 'amount' => $l->amount, 'description' => 'Pinjaman '.$l->farmer->name]));
        $add(4, 'Biaya Operasional', 'Cash Out', Transaction::with('category')->where('type', 'expense')->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->get()->map(fn ($t) => [
            'id' => $t->id, 'date' => $t->date, 'amount' => $t->amount, 'description' => ($t->category?->name ?? 'Pengeluaran').': '.$t->description]));
        if (Schema::hasTable('production_runs')) {
            $rows = [];
            foreach (ProductionRun::whereBetween('started_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->where('processing_cost_cents', '>', 0)->get() as $run) {
                $rows[] = ['id' => 'process-'.$run->id, 'date' => $run->started_date, 'amount' => $run->processing_cost_cents / 100, 'description' => 'Biaya proses '.$run->number];
            }
            foreach (ProductionRun::whereBetween('packed_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->where('packing_cost_cents', '>', 0)->get() as $run) {
                $rows[] = ['id' => 'pack-'.$run->id, 'date' => $run->packed_date, 'amount' => $run->packing_cost_cents / 100, 'description' => 'Biaya packing '.$run->number];
            }
            $add(7, 'Biaya Produksi dan Packing', 'Cash Out', $rows);
        }

        return $groups;
    }

    private function journalSummary(Carbon $start, Carbon $end, string $type): array
    {
        $definitions = [
            1 => ['Penjualan Rumput Laut', 'Cash In'], 2 => ['Pembayaran Tunai Panen', 'Cash Out'], 3 => ['Pinjaman Petani (Modal)', 'Cash Out'],
            4 => ['Biaya Operasional', 'Cash Out'], 5 => ['Pemasukan Lainnya', 'Cash In'], 6 => ['Pembayaran Utang Petani', 'Cash In'],
            7 => ['Biaya Produksi dan Packing', 'Cash Out'], 8 => ['Penerimaan Jurnal Manual', 'Cash In'], 9 => ['Pembayaran Jurnal Manual', 'Cash Out'],
        ];
        $rows = array_fill_keys(array_keys($definitions), []);
        $entries = JournalEntry::with(['lines', 'original.lines'])->whereBetween('entry_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where(function ($q) {
                $q->where('status', 'posted')->orWhere(fn ($draft) => $draft->where('is_automatic', true)->where('status', 'draft')
                    ->where('entry_date', '<=', now('Asia/Jakarta')->format('Y-m-d')));
            })
            ->where('kind', '!=', 'opening')->whereHas('lines', fn ($q) => $q->where('category', 'Harta')->where('account_group', 'Kas dan Bank'))->orderBy('entry_date')->orderBy('id')->get();
        foreach ($entries as $entry) {
            $cash = $entry->lines->where('category', 'Harta')->where('account_group', 'Kas dan Bank');
            $net = (int) $cash->sum('debit_cents') - (int) $cash->sum('credit_cents');
            if (! $net) {
                continue;
            }
            $originalCash = $entry->original ? $entry->original->lines->where('category', 'Harta')->where('account_group', 'Kas dan Bank') : $cash;
            $wasIncome = $originalCash->sum('debit_cents') > $originalCash->sum('credit_cents');
            $group = match ($entry->source_type) {
                InvoicePayment::class, SalesTransaction::class => 1,
                HarvestTransaction::class, HarvestPayment::class => 2, Loan::class => 3, FarmerPayment::class => 6, ProductionRun::class => 7,
                Transaction::class => $wasIncome ? 5 : 4,
                default => $wasIncome ? 8 : 9,
            };
            $amount = ($definitions[$group][1] === 'Cash In' ? $net : -$net) / 100;
            $isReversal = $entry->reversal_of_id !== null;
            $original = $entry->original;
            $explanation = $isReversal
                ? 'Koreksi/pembatalan atas '.$original?->number.' tanggal '.$original?->entry_date->format('d/m/Y').'. '.$original?->description
                : 'Pencatatan transaksi';
            $rows[$group][] = [
                'id' => 'journal-'.$entry->id, 'date' => $entry->entry_date->format('d/m/Y'),
                'description' => $entry->description.' ('.$entry->number.')',
                'amount' => $this->money($amount), 'value' => $amount,
                'activity' => $isReversal ? 'Koreksi / Pembatalan' : 'Transaksi',
                'explanation' => $explanation,
                'actor' => $entry->created_by_name ?? 'Tidak tersedia',
                'recorded_at' => $entry->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s').' WIB',
                'history_url' => $entry->source_type === (new Transaction)->getMorphClass()
                    ? route('manajemen-pemasukan-pengeluaran.history', ['transaction_id' => $entry->source_id]) : null,
            ];
        }
        $result = [];
        foreach ($definitions as $id => [$category,$direction]) {
            if ($type !== 'all' && (($type === 'income') !== ($direction === 'Cash In'))) {
                continue;
            }
            $amount = round(collect($rows[$id])->sum('value'), 2);
            if (floor($amount) === $amount) {
                $amount = (int) $amount;
            }
            $result[] = ['id' => $id, 'category' => $category, 'type' => $direction, 'amount' => $amount, 'amount_formatted' => $this->money($amount),
                'transactions_formatted' => $this->money(collect($rows[$id])->where('activity', 'Transaksi')->sum('value')),
                'corrections_formatted' => $this->money(collect($rows[$id])->where('activity', 'Koreksi / Pembatalan')->sum('value')),
                'details' => collect($rows[$id])->map(function ($r) {
                    unset($r['value']);

                    return $r;
                })->values()];
        }

        return $result;
    }

    private function money($amount): string
    {
        return 'Rp '.number_format($amount,fmod((float) $amount,1.0) == 0.0 ? 0 : 2,',','.');
    }
}
