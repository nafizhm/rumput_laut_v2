<?php

namespace App\Services\Finance;

use App\Models\{AccountingSetting, FarmerPayment, GoodsReceipt, HarvestTransaction, Loan, ProductionRun, SalesTransaction, StockMovement, Transaction};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationalJournalBackfillService
{
    /** Recover missing events only; never replay or correct an existing event. */
    public function run(): array
    {
        $accounting = app(OperationalAccountingService::class);
        if (! $accounting->enabled()) {
            throw ValidationException::withMessages(['accounting' => 'Jalankan migrasi integrasi jurnal terlebih dahulu.']);
        }

        return DB::transaction(function () use ($accounting) {
            $setting = AccountingSetting::lockForUpdate()->findOrFail(1);
            $counts = ['created' => 0, 'existing' => 0, 'before_start' => 0];
            $recover = function (Model $source, string $event, string $date, callable $record) use ($setting, &$counts) {
                $key = $source->getMorphClass().':'.$source->getKey().':'.$event;
                if (DB::table('operational_events')->where('source_key', $key)->exists()) {
                    $counts['existing']++;
                    return;
                }
                if ($setting->start_date && $date < $setting->start_date->format('Y-m-d')) {
                    $counts['before_start']++;
                    return;
                }
                $record();
                $counts['created']++;
            };

            foreach (Loan::orderBy('id')->cursor() as $row) {
                $recover($row, 'loan', $row->loan_date->format('Y-m-d'), fn () => $accounting->loan($row));
            }
            foreach (HarvestTransaction::orderBy('id')->cursor() as $row) {
                $recover($row, 'purchase', $row->harvest_date->format('Y-m-d'), fn () => $accounting->harvest($row));
            }
            foreach (FarmerPayment::orderBy('id')->cursor() as $row) {
                $recover($row, 'farmer-payment', $row->payment_date->format('Y-m-d'), fn () => $accounting->farmerPayment($row));
            }
            foreach (Transaction::orderBy('id')->cursor() as $row) {
                $recover($row, 'cash-transaction', $row->date->format('Y-m-d'), fn () => $accounting->transaction($row));
            }
            foreach (GoodsReceipt::where('status', 'confirmed')->whereNotNull('harvest_transaction_id')->orderBy('id')->cursor() as $row) {
                $recover($row, 'receipt', $row->received_date->format('Y-m-d'), fn () => $accounting->receipt($row));
            }
            foreach (ProductionRun::orderBy('id')->cursor() as $row) {
                $recover($row, 'production-start', $row->started_date->format('Y-m-d'), fn () => $accounting->productionStart($row));
                if ($row->packed_date) {
                    $recover($row, 'production-pack', $row->packed_date->format('Y-m-d'), fn () => $accounting->productionPack($row));
                }
            }
            // Payments use the existing receivable allocation, after shipments are recovered.
            foreach (StockMovement::whereNotNull('sales_transaction_id')->orderBy('id')->cursor() as $row) {
                $recover($row, 'dispatch', $row->movement_date->format('Y-m-d'), fn () => $accounting->shipment($row, false));
            }
            foreach (SalesTransaction::orderBy('id')->cursor() as $sale) {
                $accounting->salePayments($sale, true);
            }

            return $counts;
        }, 3);
    }
}
