<?php

namespace App\Services\Transaction;

use App\Models\Inventory;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Traits\HandlesTransaction;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SalesService
{
    use HandlesTransaction;

    /**
     * Get all sales transactions.
     */
    public function getAllSales()
    {
        return SalesTransaction::with(['factory', 'inventory'])->orderBy('sale_date', 'desc')->get();
    }

    /**
     * Create a sales transaction and reduce stock.
     */
    public function createSale(array $data): SalesTransaction
    {
        return $this->transaction(function () use ($data) {
            $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);
            $soldWeight = $data['sold_weight_kg'];

            if ($inventory->current_stock_kg < $soldWeight) {
                throw new Exception('Stok tidak mencukupi untuk penjualan ini.');
            }

            $inventory->current_stock_kg -= $soldWeight;

            if (isset($data['sold_sacks'])) {
                $inventory->current_sack_count -= $data['sold_sacks'];
            }
            $inventory->save();

            $data['invoice_number'] = $this->generateInvoiceNumber();
            $data['total_sale_value'] = $soldWeight * $data['price_per_kg'];
            $data['total_weight_kg'] = $soldWeight;
            $data['payment_status'] = $data['payment_status'] ?? 'unpaid';

            $sale = SalesTransaction::create($data);

            StockMovement::create([
                'inventory_id' => $inventory->id,
                'sales_transaction_id' => $sale->id,
                'movement_date' => $data['sale_date'] ?? now(),
                'direction' => 'out',
                'type' => 'out',
                'weight_kg' => $soldWeight,
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => 'Penjualan ke ' . $sale->factory->name . ' (Invoice: ' . $sale->invoice_number . ')'
            ]);

            return $sale;
        });
    }

    public function paySale(SalesTransaction $sale, array $data): SalesTransaction
    {
        return $this->transaction(function () use ($sale, $data) {
            $paidAmount = (int) round($data['paid_amount']);

            if ($paidAmount < (int) $sale->total_sale_value) {
                throw new Exception('Nominal bayar tidak boleh lebih kecil dari total penjualan.');
            }

            $proofPath = null;
            if (($data['payment_proof'] ?? null) instanceof UploadedFile) {
                $proofPath = $data['payment_proof']->store('sales-payments', 'public');
            }

            if ($sale->payment_proof_path && $proofPath) {
                Storage::disk('public')->delete($sale->payment_proof_path);
            }

            $sale->update([
                'payment_status' => 'paid',
                'payment_date' => $data['payment_date'],
                'paid_amount' => $paidAmount,
                'payment_proof_path' => $proofPath ?? $sale->payment_proof_path,
                'payment_notes' => $data['payment_notes'] ?? null,
            ]);

            return $sale->fresh(['factory', 'inventory']);
        });
    }

    /**
     * Generate invoice number: INV-YYYYMMDD-XXXX
     */
    protected function generateInvoiceNumber(): string
    {
        $today = Carbon::today();
        $dateStr = $today->format('Ymd');
        
        $count = SalesTransaction::whereDate('created_at', $today)->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        
        $code = "INV-{$dateStr}-{$sequence}";
        
        while (SalesTransaction::where('invoice_number', $code)->exists()) {
            $count++;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $code = "INV-{$dateStr}-{$sequence}";
        }
        
        return $code;
    }
}
