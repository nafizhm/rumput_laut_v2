<?php

namespace App\Services\Sales;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\SalesTransaction;
use App\Services\Finance\OperationalAccountingService;
use App\Traits\HandlesTransaction;
use Exception;
use Illuminate\Support\Carbon;

class InvoicePaymentService
{
    use HandlesTransaction;

    public function recordPayment(array $data): InvoicePayment
    {
        return $this->transaction(function () use ($data) {
            $invoice = Invoice::lockForUpdate()->findOrFail($data['invoice_id']);
            if (\Illuminate\Support\Facades\Schema::hasTable('accounting_settings') && ! $invoice->sales_transaction_id) {
                throw \Illuminate\Validation\ValidationException::withMessages(['invoice_id' => 'Hubungkan invoice dengan transaksi penjualan terlebih dahulu agar pembayaran tercatat pada jurnal yang benar.']);
            }
            $amount = $this->normalizeAmount($data['amount']);
            if ($amount <= 0) {
                throw new Exception('Jumlah pembayaran harus minimal Rp 1.');
            }
            if (Carbon::parse($data['payment_date'])->startOfDay()->lt($invoice->invoice_date->startOfDay())) {
                throw new Exception('Tanggal pembayaran tidak boleh sebelum tanggal invoice.');
            }
            $totalAmount = $this->totalAmount($invoice);
            $remaining = $this->remainingAmount($invoice);

            if ($invoice->payment_status === 'paid' || $remaining <= 0) {
                throw new Exception('Invoice ini sudah lunas.');
            }

            if ($amount > $remaining) {
                throw new Exception('Jumlah pembayaran tidak boleh melebihi sisa tagihan sebesar Rp '.number_format($remaining, 0, ',', '.').'.');
            }

            $payment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
            ]);

            $paidAmount = $this->paidAmount($invoice) + $amount;
            $invoice->update([
                'paid_amount' => $paidAmount,
                'payment_status' => $paidAmount >= $totalAmount ? 'paid' : 'partial',
            ]);

            $this->syncSale($invoice);
            if ($invoice->sales_transaction_id) {
                app(OperationalAccountingService::class)->salePayments($invoice->sale);
            }

            return $payment->load('invoice');
        });
    }

    public function deletePayment(InvoicePayment $payment): bool
    {
        return $this->transaction(function () use ($payment) {
            $payment = InvoicePayment::lockForUpdate()->findOrFail($payment->id);
            $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);

            $paidAmount = max(0, $this->paidAmount($invoice) - $payment->amount);

            app(OperationalAccountingService::class)->cancel($payment, 'invoice-payment');
            $payment->delete();

            $invoice->update([
                'paid_amount' => $paidAmount,
                'payment_status' => $paidAmount >= $this->totalAmount($invoice) ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
            ]);

            $this->syncSale($invoice);
            if ($invoice->sales_transaction_id) {
                app(OperationalAccountingService::class)->salePayments($invoice->sale);
            }

            return true;
        });
    }

    public function totalAmount(Invoice $invoice): int
    {
        return (int) round($invoice->total_amount);
    }

    public function paidAmount(Invoice $invoice): int
    {
        return (int) ($invoice->paid_amount ?? 0);
    }

    public function remainingAmount(Invoice $invoice): int
    {
        return max(0, $this->totalAmount($invoice) - $this->paidAmount($invoice));
    }

    private function normalizeAmount($amount): int
    {
        return (int) round((float) $amount);
    }

    private function syncSale(Invoice $invoice): void
    {
        if (! $invoice->sales_transaction_id) {
            return;
        }
        $remaining = (int) $invoice->paid_amount;
        foreach ($invoice->saleItems() as $item) {
            $sale = SalesTransaction::lockForUpdate()->findOrFail($item->id);
            $amount = min($remaining, (int) $sale->total_sale_value);
            $remaining -= $amount;
            $sale->update([
                'paid_amount' => $amount,
                'payment_status' => $amount >= (int) $sale->total_sale_value ? 'paid' : 'unpaid',
                'payment_date' => $amount > 0 ? $invoice->payments()->max('payment_date') : null,
            ]);
        }
    }
}
