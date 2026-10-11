<?php

namespace App\Services\Transaction;

use App\Models\FinishedBatch;
use App\Models\CustomerPurchaseOrder;
use App\Models\Inventory;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Services\Finance\OperationalAccountingService;
use App\Traits\HandlesTransaction;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SalesService
{
    use HandlesTransaction;

    /**
     * Get all sales transactions.
     */
    public function getAllSales()
    {
        return SalesTransaction::with(['factory', 'inventory', 'grade', 'invoice', 'purchaseOrder', 'purchaseOrderItem'])->orderBy('sale_date', 'desc')->get();
    }

    /**
     * Create a sales transaction and reduce stock.
     */
    public function createSale(array $data): SalesTransaction
    {
        return $this->transaction(function () use ($data) {
            $poItem = null;
            if (! empty($data['customer_purchase_order_id'])) {
                $order = CustomerPurchaseOrder::lockForUpdate()->findOrFail($data['customer_purchase_order_id']);
                if (! $order->factory_id) {
                    throw new Exception('Lengkapi pabrik pada PO sebelum membuat penjualan.');
                }
                if (! $order->items()->exists()) {
                    $item = $order->items()->create($order->only(['item_name', 'quantity_kg', 'price_per_kg']));
                    $order->salesTransactions()->whereNull('customer_purchase_order_item_id')->update(['customer_purchase_order_item_id' => $item->id]);
                }
                $poItem = ! empty($data['customer_purchase_order_item_id'])
                    ? $order->items()->lockForUpdate()->find($data['customer_purchase_order_item_id'])
                    : ($order->items()->count() === 1 ? $order->items()->lockForUpdate()->first() : null);
                if (! $poItem) {
                    throw new Exception('Pilih baris grade yang akan dipenuhi dari PO ini.');
                }
                $remaining = (int) round((float) $poItem->quantity_kg * 100)
                    - (int) round((float) $poItem->salesTransactions()->sum('total_weight_kg') * 100);
                if ((int) round((float) $data['sold_weight_kg'] * 100) > $remaining) {
                    throw new Exception('Berat penjualan melebihi sisa jumlah PO konsumen.');
                }
                $data['factory_id'] = $order->factory_id;
                $data['price_per_kg'] = $poItem->price_per_kg;
                $data['customer_purchase_order_item_id'] = $poItem->id;
            } elseif (! empty($data['customer_purchase_order_item_id'])) {
                throw new Exception('Baris PO harus disertai referensi PO konsumen.');
            }
            $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);
            if ($inventory->stock_stage === 'material') {
                throw new Exception('Bahan masuk belum menjadi barang jadi dan tidak dapat dijual.');
            }
            if ($poItem?->quality_grade_id && $inventory->stock_stage !== 'finished') {
                throw new Exception('Pilih gudang dan batch barang jadi sesuai grade pesanan.');
            }
            $soldWeight = $data['sold_weight_kg'];
            $allocation = [];
            $allocations = [];
            $gradeSale = ! empty($data['quality_grade_id']);
            if ($gradeSale) {
                if ($inventory->stock_stage !== 'finished' || ($poItem?->quality_grade_id && (int) $poItem->quality_grade_id !== (int) $data['quality_grade_id'])) {
                    throw new Exception('Pilih grade barang jadi sesuai pesanan.');
                }
                unset($data['finished_batch_id']);
                app(ProductionService::class)->allocateGrade($inventory->id, (int) $data['quality_grade_id'], (float) $soldWeight, (int) ($data['sold_sacks'] ?? 0), $data['sale_date'], false);
            } elseif ($inventory->stock_stage === 'finished') {
                $batch = FinishedBatch::lockForUpdate()->find($data['finished_batch_id'] ?? null);
                if (! $batch || (int) $batch->inventory_id !== (int) $inventory->id) {
                    throw new Exception('Pilih batch barang jadi sesuai gudang, kualitas, dan area.');
                }
                if ($poItem?->quality_grade_id && (int) $batch->quality_grade_id !== (int) $poItem->quality_grade_id) {
                    throw new Exception('Grade batch barang jadi harus sesuai grade pada baris PO yang dipilih.');
                }
                if ($soldWeight > $batch->remaining_weight_kg || ($data['sold_sacks'] ?? 0) < 1 || $data['sold_sacks'] > $batch->remaining_packages) {
                    throw new Exception('Berat atau kemasan penjualan melebihi batch barang jadi.');
                }
            } else {
                unset($data['finished_batch_id']);
            }
            $data['stock_mode'] = $data['stock_mode'] ?? 'immediate';
            $immediate = $data['stock_mode'] === 'immediate';

            if ($immediate && $inventory->current_stock_kg < $soldWeight) {
                throw new Exception('Stok tidak mencukupi untuk penjualan ini.');
            }

            if ($immediate && ($data['sold_sacks'] ?? 0) > $inventory->current_sack_count) {
                throw new Exception('Jumlah karung melebihi stok yang tersedia.');
            }

            if ($immediate) {
                if ($gradeSale) {
                    $allocations = app(ProductionService::class)->allocateGrade($inventory->id, (int) $data['quality_grade_id'], (float) $soldWeight, (int) $data['sold_sacks'], $data['sale_date']);
                } else {
                    if ($inventory->stock_stage === 'finished') {
                        $allocation = app(ProductionService::class)->consumeFinished($data['finished_batch_id'], (int) $inventory->id, (float) $soldWeight, (int) $data['sold_sacks'], $data['sale_date']);
                    }
                    $allocations = [$allocation + ['weight_kg' => $soldWeight, 'sack_count' => $data['sold_sacks'] ?? 0]];
                }
            }
            $data['invoice_number'] = $this->generateSaleNumber();

            $soldWeight = $data['sold_weight_kg'];
            $price = $data['price_per_kg'];
            // The form expresses bonus weight per sack, not total bonus weight.
            $weightDeduction = (int) ($data['sold_sacks'] ?? 0) * (float) ($data['deduction_weight_kg'] ?? 0);
            if ($weightDeduction > $soldWeight) {
                throw new Exception('Total bonus berat tidak boleh melebihi berat terjual.');
            }
            $deductionType = $data['deduction_type'] ?? 'none';
            $deductionValue = (float) ($data['deduction_value'] ?? 0);

            $baseWeight = max(0, $soldWeight - $weightDeduction);
            $baseValue = $baseWeight * $price;
            $deductionAmount = 0;

            if ($deductionType === 'percentage') {
                $deductionAmount = (int) round($baseValue * ($deductionValue / 100));
            } elseif ($deductionType === 'nominal') {
                $deductionAmount = (int) round($deductionValue);
            } elseif ($deductionType === 'weight') {
                $deductionAmount = (int) round($deductionValue * $price);
            }

            $data['total_sale_value'] = max(0, (int) round($baseValue) - $deductionAmount);
            if ($deductionAmount > $baseValue || ($deductionType === 'percentage' && $deductionValue > 100)) {
                throw new Exception('Potongan tidak boleh melebihi nilai penjualan.');
            }
            $data['total_weight_kg'] = $soldWeight;
            $data['payment_status'] = 'unpaid';
            $data['paid_amount'] = 0;
            $data['payment_date'] = null;

            $sale = SalesTransaction::create($data);

            foreach ($allocations as $allocation) {
                $inventory->decrement('current_stock_kg', $allocation['weight_kg']);
                $inventory->decrement('current_sack_count', $allocation['sack_count']);
                $movement = StockMovement::create($allocation + [
                    'inventory_id' => $inventory->id, 'sales_transaction_id' => $sale->id,
                    'movement_date' => $data['sale_date'], 'direction' => 'out', 'type' => 'out',
                    'balance_after_kg' => $inventory->current_stock_kg,
                    'description' => 'Penjualan langsung ke '.$sale->factory->name.' (No. Penjualan: '.$sale->invoice_number.')',
                ]);
                app(OperationalAccountingService::class)->shipment($movement);
            }
            return $sale;
        });
    }

    public function deleteSale(SalesTransaction $sale): bool
    {
        return $this->transaction(function () use ($sale) {
            if ($sale->customer_purchase_order_id) {
                CustomerPurchaseOrder::lockForUpdate()->findOrFail($sale->customer_purchase_order_id);
            }
            $sale = SalesTransaction::lockForUpdate()->findOrFail($sale->id);
            if ((int) $sale->paid_amount > 0 || $sale->payment_status === 'paid') {
                throw new Exception('Penjualan dengan pembayaran tidak dapat dihapus.');
            }
            if ($sale->invoice()->exists()) {
                throw new Exception('Penjualan dengan invoice tidak dapat dihapus.');
            }
            if ($sale->deliveryNotes()->exists()) {
                throw new Exception('Penjualan dengan surat jalan tidak dapat dihapus.');
            }
            $inventory = Inventory::lockForUpdate()->find($sale->inventory_id);
            if ($inventory && $sale->stock_mode !== 'delivery_note') {
                $inventory->increment('current_stock_kg', $sale->total_weight_kg);
                if ($sale->sold_sacks) {
                    $inventory->increment('current_sack_count', $sale->sold_sacks);
                }
            }

            foreach ($sale->stockMovements()->lockForUpdate()->get() as $movement) {
                app(ProductionService::class)->restoreFinished($movement);
                app(OperationalAccountingService::class)->cancel($movement, 'dispatch');
            }
            $sale->stockMovements()->delete();

            if ($sale->payment_proof_path) {
                Storage::disk('public')->delete($sale->payment_proof_path);
            }

            return $sale->delete();
        });
    }

    public function paySale(SalesTransaction $sale, array $data): SalesTransaction
    {
        return $this->transaction(function () use ($sale, $data) {
            $sale = SalesTransaction::lockForUpdate()->findOrFail($sale->id);
            if ($sale->invoice()->exists()) {
                throw new Exception('Catat pembayaran melalui invoice terkait.');
            }
            if ($sale->payment_status === 'paid') {
                throw new Exception('Penjualan ini sudah lunas.');
            }
            $paidAmount = (int) round($data['paid_amount']);

            if ($paidAmount !== (int) $sale->total_sale_value) {
                throw new Exception('Nominal pelunasan harus sama dengan total penjualan.');
            }
            if (Carbon::parse($data['payment_date'])->startOfDay()->lt($sale->sale_date->startOfDay())) {
                throw new Exception('Tanggal bayar tidak boleh sebelum tanggal penjualan.');
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
            app(OperationalAccountingService::class)->salePayments($sale);

            return $sale->fresh(['factory', 'inventory']);
        });
    }

    /**
     * Generate sale reference: PJL-YYYYMMDD-XXXX. Historical references are preserved.
     */
    protected function generateSaleNumber(): string
    {
        $today = Carbon::today();
        $dateStr = $today->format('Ymd');

        $count = SalesTransaction::whereDate('created_at', $today)->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        $code = "PJL-{$dateStr}-{$sequence}";

        while (SalesTransaction::where('invoice_number', $code)->exists()) {
            $count++;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $code = "PJL-{$dateStr}-{$sequence}";
        }

        return $code;
    }
}
