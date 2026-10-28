<?php

namespace App\Services\Sales;

use App\Models\CustomerPurchaseOrder;
use App\Models\DeliveryNote;
use App\Models\Inventory;
use App\Models\FinishedBatch;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Finance\OperationalAccountingService;
use App\Services\Transaction\ProductionService;
use App\Services\Setting\DocumentNumberService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryNoteService
{
    public function getAll()
    {
        $query = DeliveryNote::with('sale');
        if (\Illuminate\Support\Facades\Schema::hasTable('delivery_note_items')) $query->with('items');
        return $query->orderByDesc('delivery_date')->orderByDesc('id')->get();
    }

    public function saveItems(array $data, ?DeliveryNote $note = null): DeliveryNote
    {
        return DB::transaction(function () use ($data, $note) {
            if ($note) { $note = DeliveryNote::lockForUpdate()->findOrFail($note->id); $this->assertEditable($note); }
            $anchor = SalesTransaction::lockForUpdate()->findOrFail($data['sales_transaction_id']);
            if ($note && $note->sales_transaction_id !== $anchor->id) throw ValidationException::withMessages(['sales_transaction_id'=>'Nota penjualan terkait tidak dapat diganti.']);
            $rows = $data['items']; unset($data['items']);
            $prepared = [];
            foreach ($rows as $index => $row) {
                $sale = SalesTransaction::lockForUpdate()->findOrFail($row['sales_transaction_id']);
                if ($sale->id !== $anchor->id && (!$anchor->sale_group_number || $sale->sale_group_number !== $anchor->sale_group_number)) {
                    throw ValidationException::withMessages(['items.'.$index=>'Barang harus berasal dari nota penjualan yang dipilih.']);
                }
                $used = StockMovement::where('sales_transaction_id',$sale->id)->whereNotNull('delivery_note_id')->sum('weight_kg');
                $usedSacks = StockMovement::where('sales_transaction_id',$sale->id)->whereNotNull('delivery_note_id')->sum('sack_count');
                $remainingWeight = (int) round((float) $sale->total_weight_kg * 100) - (int) round((float) $used * 100);
                if ((int) round((float) $row['quantity_kg'] * 100) > $remainingWeight || (int)$row['sack_count'] > (int)$sale->sold_sacks - $usedSacks) throw ValidationException::withMessages(['items.'.$index=>'Jumlah melebihi sisa penjualan yang belum dikirim.']);
                try { $line = $this->prepare($row + array_diff_key($data, ['sales_transaction_id'=>true])); }
                catch (ValidationException $e) { throw ValidationException::withMessages(['items.'.$index=>collect($e->errors())->flatten()->implode(' ')]); }
                $prepared[] = array_intersect_key($line, array_flip(['sales_transaction_id','finished_batch_id','warehouse_area_id','quantity_kg','sack_count'])) + ['item_name'=>$row['item_name']];
            }
            foreach (collect($prepared)->filter(fn($row)=>!empty($row['finished_batch_id']))->groupBy('finished_batch_id') as $id=>$lines) {
                $batch = FinishedBatch::lockForUpdate()->findOrFail($id);
                if ($lines->sum('quantity_kg') > $batch->remaining_weight_kg || $lines->sum('sack_count') > $batch->remaining_packages) throw ValidationException::withMessages(['items'=>'Total pengiriman dari batch yang sama melebihi stok.']);
            }
            foreach (collect($prepared)->filter(fn ($row) => !empty($row['warehouse_area_id']))
                ->groupBy(fn ($row) => $row['warehouse_area_id'].':'.(int) SalesTransaction::findOrFail($row['sales_transaction_id'])->quality_grade_id) as $lines) {
                $area = \App\Models\WarehouseArea::findOrFail($lines->first()['warehouse_area_id']);
                $grade = (int) SalesTransaction::findOrFail($lines->first()['sales_transaction_id'])->quality_grade_id;
                app(ProductionService::class)->allocateGrade($area->inventory_id, $grade, (float) $lines->sum('quantity_kg'), (int) $lines->sum('sack_count'), $data['delivery_date'], false, $area->id);
            }
            $data['customer_purchase_order_id'] = $anchor->customer_purchase_order_id;
            $data['item_name'] = mb_substr(implode(', ',array_column($prepared,'item_name')),0,255);
            $data['quantity_kg'] = array_sum(array_column($prepared,'quantity_kg')); $data['sack_count'] = array_sum(array_column($prepared,'sack_count'));
            if (!$note) { $note = new DeliveryNote; if(empty($data['number'])) $data['number'] = app(DocumentNumberService::class)->next('delivery_note',$data['delivery_date']); $note->status='draft'; }
            $note->fill($data)->save(); $note->items()->delete(); $note->items()->createMany($prepared);
            return $note;
        });
    }

    public function store(array $data): DeliveryNote
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['number'])) {
                $data['number'] = app(DocumentNumberService::class)->next('delivery_note', $data['delivery_date']);
            }
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
        if ($sale->sale_group_number && !$sale->finished_batch_id && !empty($data['warehouse_area_id'])) {
            $area = \App\Models\WarehouseArea::where('is_active', true)->where('usage', 'finished')->find($data['warehouse_area_id']);
            if (!$area) throw ValidationException::withMessages(['warehouse_area_id' => 'Pilih area stok siap jual yang aktif.']);
            app(ProductionService::class)->allocateGrade($area->inventory_id, (int) $sale->quality_grade_id, (float) $data['quantity_kg'], (int) $data['sack_count'], $data['delivery_date'], false, $area->id);
            $data['finished_batch_id'] = null;
            $data['origin'] = $area->name;
        } elseif ($sale->sale_group_number && !$sale->finished_batch_id) {
            $batch = FinishedBatch::with(['area', 'run'])->readyForSale()->lockForUpdate()->find($data['finished_batch_id'] ?? $note?->finished_batch_id);
            if (!$batch
                || ($sale->quality_grade_id && (int) $sale->quality_grade_id !== (int) $batch->quality_grade_id)) {
                throw ValidationException::withMessages(['finished_batch_id' => 'Pilih batch siap jual yang aktif dan sesuai barang pesanan.']);
            }
            if ((float) $data['quantity_kg'] > (float) $batch->remaining_weight_kg || (int) ($data['sack_count'] ?? 0) < 1
                || (int) $data['sack_count'] > (int) $batch->remaining_packages) {
                throw ValidationException::withMessages(['quantity_kg' => 'Stok batch siap jual belum cukup untuk jumlah pengiriman ini.']);
            }
            $data['finished_batch_id'] = $batch->id;
            $data['origin'] = $batch->area->name;
        } else {
            unset($data['finished_batch_id'], $data['warehouse_area_id']);
        }
        if ($note && (int) $sale->id !== (int) $note->sales_transaction_id) {
            throw ValidationException::withMessages(['sales_transaction_id' => 'Penjualan terkait tidak dapat diganti.']);
        }
        if ($sale->customer_purchase_order_id) {
            if (! empty($data['customer_purchase_order_id']) && (int) $data['customer_purchase_order_id'] !== (int) $sale->customer_purchase_order_id) {
                throw ValidationException::withMessages(['customer_purchase_order_id' => 'PO surat jalan harus sama dengan PO penjualan.']);
            }
            $data['customer_purchase_order_id'] = $sale->customer_purchase_order_id;
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
        $data['item_name'] = $sale->document_item_name;
        $data['sack_count'] = $data['sack_count'] ?? 0;

        return $data;
    }

    public function dispatch(DeliveryNote $note, string $date, User $user, bool $lineOnly = false): DeliveryNote
    {
        return DB::transaction(function () use ($note, $date, $user, $lineOnly) {
            if (!$lineOnly) $note = DeliveryNote::lockForUpdate()->findOrFail($note->id);
            if ($note->status !== 'draft' || $note->shipped_at || (!$lineOnly && StockMovement::where('delivery_note_id', $note->id)->exists())) {
                throw ValidationException::withMessages(['surat_jalan' => 'Hanya Draft baru yang dapat dikonfirmasi. Dokumen sudah dikirim atau merupakan data lama.']);
            }
            if (!$lineOnly && \Illuminate\Support\Facades\Schema::hasTable('delivery_note_items') && $note->items()->exists()) {
                foreach ($note->items()->orderBy('sales_transaction_id')->get() as $item) {
                    $line = clone $note;
                    $line->warehouse_area_id=$item->warehouse_area_id; $line->sales_transaction_id=$item->sales_transaction_id; $line->finished_batch_id=$item->finished_batch_id;
                    $line->quantity_kg=$item->quantity_kg; $line->sack_count=$item->sack_count;
                    $this->dispatch($line,$date,$user,true);
                }
                $note->status='shipped'; $note->shipped_date=$date; $note->shipped_at=now(); $note->shipped_by=$user->id; $note->shipped_by_name=$user->name; $note->save();
                return $note;
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
            $selectedBatch = $note->finished_batch_id ? FinishedBatch::lockForUpdate()->findOrFail($note->finished_batch_id) : null;
            $area = $note->warehouse_area_id ? \App\Models\WarehouseArea::findOrFail($note->warehouse_area_id) : null;
            $inventory = Inventory::lockForUpdate()->findOrFail($area?->inventory_id ?? $selectedBatch?->inventory_id ?? $sale->inventory_id);
            $allocation = [];
            $allocations = [];
            if ($area && $sale->sale_group_number && !$sale->finished_batch_id) {
                $allocations = app(ProductionService::class)->allocateGrade($inventory->id, (int) $sale->quality_grade_id, (float) $note->quantity_kg, (int) $note->sack_count, $date, true, $area->id);
            } elseif ($selectedBatch) {
                if ($sale->quality_grade_id && (int) $selectedBatch->quality_grade_id !== (int) $sale->quality_grade_id) {
                    throw ValidationException::withMessages(['finished_batch_id' => 'Batch harus sesuai barang pada penjualan.']);
                }
                $allocation = app(ProductionService::class)->consumeFinished($selectedBatch->id, (int) $inventory->id, (float) $note->quantity_kg, (int) $note->sack_count, $date);
            } elseif ($sale->sale_group_number && !$sale->finished_batch_id) {
                throw ValidationException::withMessages(['finished_batch_id' => 'Pilih stok siap jual pada Surat Jalan sebelum konfirmasi kirim.']);
            } elseif ($sale->quality_grade_id) {
                $allocations = app(ProductionService::class)->allocateGrade($inventory->id, $sale->quality_grade_id, (float) $note->quantity_kg, (int) $note->sack_count, $date);
            } elseif ($inventory->stock_stage === 'finished') {
                if (! $sale->finished_batch_id) {
                    throw ValidationException::withMessages(['surat_jalan' => 'Penjualan belum memilih batch jadi.']);
                }
                $allocation = app(ProductionService::class)->consumeFinished($sale->finished_batch_id, (int) $inventory->id, (float) $note->quantity_kg, (int) $note->sack_count, $date);
            }
            if ((int) round((float) $inventory->current_stock_kg * 100) < $quantity || $inventory->current_sack_count < $note->sack_count) {
                throw ValidationException::withMessages(['quantity_kg' => 'Stok berat atau karung gudang tidak cukup untuk pengiriman.']);
            }
            if (! $allocations) {
                $allocations = [$allocation + ['weight_kg' => $note->quantity_kg, 'sack_count' => $note->sack_count]];
            }
            foreach ($allocations as $allocation) {
                $inventory->decrement('current_stock_kg', $allocation['weight_kg']);
                $inventory->decrement('current_sack_count', $allocation['sack_count']);
                $movement = StockMovement::create($allocation + [
                    'inventory_id' => $inventory->id, 'sales_transaction_id' => $sale->id, 'delivery_note_id' => $note->id,
                    'movement_date' => $date, 'direction' => 'out', 'type' => 'out',
                    'note_weight_kg' => $allocation['weight_kg'],
                    'balance_after_kg' => $inventory->current_stock_kg,
                    'description' => 'Pengiriman '.$note->number.' / Penjualan '.$sale->invoice_number,
                ]);
                app(OperationalAccountingService::class)->shipment($movement);
            }
            $note->status = 'shipped';
            $note->shipped_date = $date;
            $note->shipped_at = now();
            $note->shipped_by = $user->id;
            $note->shipped_by_name = $user->name;
            if (!$lineOnly) $note->save();

            return $note;
        });
    }
}
