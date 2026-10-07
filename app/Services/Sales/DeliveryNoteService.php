<?php

namespace App\Services\Sales;

use App\Models\CustomerPurchaseOrder;
use App\Models\DeliveryNote;
use App\Models\Inventory;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Finance\OperationalAccountingService;
use App\Services\Transaction\ProductionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryNoteService
{
    public function getAll()
    {
        return DeliveryNote::with('sale')->orderByDesc('delivery_date')->orderByDesc('id')->get();
    }

    public function store(array $data): DeliveryNote
    {
        return DB::transaction(function () use ($data) {
            $note = new DeliveryNote($this->prepare($data));
            $note->status = 'draft';
            $note->save();

            return $note;
        });
    }

    public function update(DeliveryNote $note, array $data): bool
    {
        return DB::transaction(function () use ($note, $data) {
            $note = DeliveryNote::lockForUpdate()->findOrFail($note->id);
            $this->assertEditable($note);
            if ($note->status === 'legacy') {
                unset($data['sales_transaction_id'], $data['sack_count']);

                return $note->update($data);
            }

            return $note->update($this->prepare($data, $note));
        });
    }

    public function delete(DeliveryNote $note): bool
    {
        return DB::transaction(function () use ($note) {
            $note = DeliveryNote::lockForUpdate()->findOrFail($note->id);
            $this->assertEditable($note);

            return $note->delete();
        });
    }

    private function assertEditable(DeliveryNote $note): void
    {
        if ($note->status === 'shipped' || $note->shipped_at || StockMovement::where('delivery_note_id', $note->id)->exists()) {
            throw ValidationException::withMessages(['surat_jalan' => 'Surat jalan yang sudah dikirim tidak dapat diubah atau dihapus.']);
        }
    }

    private function prepare(array $data, ?DeliveryNote $note = null): array
    {
        $id = $data['sales_transaction_id'] ?? $note?->sales_transaction_id;
        $sale = SalesTransaction::with(['factory', 'inventory'])->lockForUpdate()->find($id);
        if (! $sale || $sale->stock_mode !== 'delivery_note') {
            throw ValidationException::withMessages(['sales_transaction_id' => 'Pilih penjualan baru dengan stok keluar melalui Surat Jalan. Transaksi lama/langsung tidak boleh dipotong lagi.']);
        }
        if ($note && (int) $sale->id !== (int) $note->sales_transaction_id) {
            throw ValidationException::withMessages(['sales_transaction_id' => 'Penjualan terkait tidak dapat diganti.']);
        }
        if ((float) $data['quantity_kg'] > (float) $sale->total_weight_kg || (int) ($data['sack_count'] ?? 0) > (int) $sale->sold_sacks) {
            throw ValidationException::withMessages(['quantity_kg' => 'Jumlah pengiriman tidak boleh melebihi penjualan.']);
        }
        if (! empty($data['customer_purchase_order_id'])) {
            $po = CustomerPurchaseOrder::findOrFail($data['customer_purchase_order_id']);
            if (($po->factory_id && (int) $po->factory_id !== (int) $sale->factory_id)
                || (! $po->factory_id && mb_strtolower(trim($po->customer_name)) !== mb_strtolower(trim($sale->factory->name)))) {
                throw ValidationException::withMessages(['customer_purchase_order_id' => 'Konsumen PO harus sesuai dengan penjualan.']);
            }
            $invoice = $sale->invoice;
            if ($invoice && (int) $invoice->customer_purchase_order_id !== (int) $po->id) {
                throw ValidationException::withMessages(['customer_purchase_order_id' => 'PO harus sesuai dengan invoice penjualan ini.']);
            }
        }
        $data['sales_transaction_id'] = $sale->id;
        if (empty($data['customer_purchase_order_id']) && $sale->invoice?->customer_purchase_order_id) {
            $data['customer_purchase_order_id'] = $sale->invoice->customer_purchase_order_id;
        }
        $data['recipient_name'] = $sale->factory->name;
        $data['item_name'] = $sale->inventory->item_name;
        $data['sack_count'] = $data['sack_count'] ?? 0;

        return $data;
    }

    public function dispatch(DeliveryNote $note, string $date, User $user): DeliveryNote
    {
        return DB::transaction(function () use ($note, $date, $user) {
            $note = DeliveryNote::lockForUpdate()->findOrFail($note->id);
            if ($note->status !== 'draft' || $note->shipped_at || StockMovement::where('delivery_note_id', $note->id)->exists()) {
                throw ValidationException::withMessages(['surat_jalan' => 'Hanya Draft baru yang dapat dikonfirmasi. Dokumen sudah dikirim atau merupakan data lama.']);
            }
            $sale = SalesTransaction::lockForUpdate()->findOrFail($note->sales_transaction_id);
            if ($sale->invoice?->customer_purchase_order_id) {
                if ($note->customer_purchase_order_id && (int) $note->customer_purchase_order_id !== (int) $sale->invoice->customer_purchase_order_id) {
                    throw ValidationException::withMessages(['surat_jalan' => 'PO surat jalan harus sama dengan invoice terkait.']);
                }
                $note->customer_purchase_order_id = $sale->invoice->customer_purchase_order_id;
            }
            if ($sale->stock_mode !== 'delivery_note' || $sale->stockMovements()->whereNull('delivery_note_id')->exists()) {
                throw ValidationException::withMessages(['surat_jalan' => 'Stok penjualan ini sudah ditangani di alur lama/langsung.']);
            }
            $shippingDate = Carbon::parse($date)->startOfDay();
            if ($shippingDate->lt($sale->sale_date->startOfDay()) || $shippingDate->gt(Carbon::now('Asia/Jakarta')->startOfDay())) {
                throw ValidationException::withMessages(['shipped_date' => 'Tanggal kirim harus sejak tanggal penjualan dan tidak boleh di masa depan.']);
            }
            // Locking the sale serializes partial shipments from different notes.
            // A locking read sees the latest committed movements even under MySQL
            // REPEATABLE READ, after waiting for another shipment of this sale.
            $movements = StockMovement::where('sales_transaction_id', $sale->id)->whereNotNull('delivery_note_id')->lockForUpdate()->get();
            $quantity = (int) round((float) $note->quantity_kg * 100);
            $alreadyShipped = (int) round((float) $movements->sum('weight_kg') * 100);
            if ($alreadyShipped + $quantity > (int) round((float) $sale->total_weight_kg * 100)
                || (int) $movements->sum('sack_count') + $note->sack_count > (int) $sale->sold_sacks) {
                throw ValidationException::withMessages(['quantity_kg' => 'Total pengiriman akan melebihi jumlah penjualan.']);
            }
            $inventory = Inventory::lockForUpdate()->findOrFail($sale->inventory_id);
            $allocation = [];
            if ($inventory->stock_stage === 'finished') {
                if (! $sale->finished_batch_id) {
                    throw ValidationException::withMessages(['surat_jalan' => 'Penjualan belum memilih batch jadi.']);
                }
                $allocation = app(ProductionService::class)->consumeFinished($sale->finished_batch_id, (int) $inventory->id, (float) $note->quantity_kg, (int) $note->sack_count, $date);
            }
            if ((int) round((float) $inventory->current_stock_kg * 100) < $quantity || $inventory->current_sack_count < $note->sack_count) {
                throw ValidationException::withMessages(['quantity_kg' => 'Stok berat atau karung gudang tidak cukup untuk pengiriman.']);
            }
            $inventory->decrement('current_stock_kg', $note->quantity_kg);
            $inventory->decrement('current_sack_count', $note->sack_count);
            $movement = StockMovement::create($allocation + [
                'inventory_id' => $inventory->id, 'sales_transaction_id' => $sale->id, 'delivery_note_id' => $note->id,
                'movement_date' => $date, 'direction' => 'out', 'type' => 'out',
                'weight_kg' => $note->quantity_kg, 'note_weight_kg' => $note->quantity_kg, 'sack_count' => $note->sack_count,
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => 'Pengiriman '.$note->number.' / Penjualan '.$sale->invoice_number,
            ]);
            $note->status = 'shipped';
            $note->shipped_date = $date;
            $note->shipped_at = now();
            $note->shipped_by = $user->id;
            $note->shipped_by_name = $user->name;
            $note->save();
            app(OperationalAccountingService::class)->shipment($movement);

            return $note;
        });
    }
}
