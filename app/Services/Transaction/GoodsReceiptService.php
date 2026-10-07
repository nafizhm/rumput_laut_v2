<?php

namespace App\Services\Transaction;

use App\Models\GoodsReceipt;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\MaterialBatch;
use App\Models\QualityGrade;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseArea;
use App\Services\Finance\OperationalAccountingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function save(array $data, ?GoodsReceipt $receipt = null): GoodsReceipt
    {
        return DB::transaction(function () use ($data, $receipt) {
            if ($receipt) {
                $receipt = GoodsReceipt::lockForUpdate()->findOrFail($receipt->id);
                $this->assertDraft($receipt);
            }
            $this->checkSource($data, $receipt);
            $this->checkLines($data);
            $lines = $data['lines'];
            unset($data['lines']);
            if (! $receipt) {
                $receipt = GoodsReceipt::create($data);
                $receipt->number = 'RCV-'.str_replace('-', '', $data['received_date']).'-'.str_pad($receipt->id, 6, '0', STR_PAD_LEFT);
                $receipt->save();
            } else {
                $receipt->update($data);
                $receipt->lines()->delete();
            }
            $receipt->lines()->createMany($lines);

            return $receipt;
        });
    }

    public function delete(GoodsReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            $receipt = GoodsReceipt::lockForUpdate()->findOrFail($receipt->id);
            $this->assertDraft($receipt);
            $receipt->delete();
        });
    }

    public function confirm(GoodsReceipt $receipt, User $user): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $user) {
            $receipt = GoodsReceipt::lockForUpdate()->findOrFail($receipt->id);
            $this->assertDraft($receipt);
            $lines = $receipt->lines()->lockForUpdate()->get();
            $data = $receipt->only(['farmer_id', 'harvest_transaction_id', 'received_date', 'inventory_id']);
            $data['lines'] = $lines->toArray();
            $this->checkSource($data, $receipt);
            $this->checkLines($data);
            if ($receipt->received_date->gt(Carbon::now('Asia/Jakarta')->startOfDay())) {
                throw ValidationException::withMessages(['received_date' => 'Tanggal penerimaan aktual tidak boleh di masa depan.']);
            }
            $inventory = Inventory::lockForUpdate()->findOrFail($receipt->inventory_id);
            $weight = round($lines->sum('actual_weight_kg'), 2);
            $sacks = $lines->sum('sack_count');
            if ((float) $inventory->current_stock_kg + $weight > 9999999999.99) {
                throw ValidationException::withMessages(['lines' => 'Total stok melewati kapasitas pencatatan.']);
            }
            $inventory->increment('current_stock_kg', $weight);
            $inventory->increment('current_sack_count', $sacks);
            foreach ($lines as $line) {
                MaterialBatch::create([
                    'code' => 'BAT-'.$receipt->id.'-'.$line->id, 'goods_receipt_line_id' => $line->id,
                    'incoming_weight_kg' => $line->actual_weight_kg, 'incoming_sacks' => $line->sack_count,
                    'remaining_sacks' => $line->sack_count, 'status' => 'available',
                ]);
            }
            StockMovement::create([
                'inventory_id' => $inventory->id, 'goods_receipt_id' => $receipt->id,
                'movement_date' => $receipt->received_date->format('Y-m-d'), 'direction' => 'in', 'type' => 'in',
                'weight_kg' => $weight, 'note_weight_kg' => $receipt->note_weight_kg,
                'discrepancy_kg' => $receipt->note_weight_kg === null ? 0 : $receipt->note_weight_kg - $weight,
                'sack_count' => $sacks, 'balance_after_kg' => $inventory->current_stock_kg,
                'description' => 'Penerimaan bahan '.$receipt->number,
            ]);
            $receipt->status = 'confirmed';
            $receipt->confirmed_at = now();
            $receipt->confirmed_by = $user->id;
            $receipt->confirmed_by_name = $user->name;
            $receipt->save();
            if ($receipt->harvest_transaction_id) {
                HarvestTransaction::whereKey($receipt->harvest_transaction_id)->update(['is_received' => true]);
            }
            $this->valueBatches($receipt);
            app(OperationalAccountingService::class)->receipt($receipt);

            return $receipt;
        });
    }

    private function assertDraft(GoodsReceipt $receipt): void
    {
        if ($receipt->status !== 'draft' || $receipt->confirmed_at || StockMovement::where('goods_receipt_id', $receipt->id)->exists()) {
            throw ValidationException::withMessages(['receipt' => 'Penerimaan sudah dikonfirmasi dan dikunci. Koreksi memerlukan transaksi tersendiri.']);
        }
    }

    private function checkSource(array $data, ?GoodsReceipt $receipt): void
    {
        if (! empty($data['harvest_transaction_id'])) {
            $harvest = HarvestTransaction::lockForUpdate()->findOrFail($data['harvest_transaction_id']);
            if ($harvest->receipt_mode !== 'separate' || $harvest->is_received
                || (int) $harvest->farmer_id !== (int) $data['farmer_id']
                || strtotime($data['received_date']) < strtotime($harvest->harvest_date->format('Y-m-d'))) {
                throw ValidationException::withMessages(['harvest_transaction_id' => 'Pilih pembelian baru yang belum diterima, dengan petani dan tanggal yang sesuai. Pembelian lama tidak boleh menambah stok ulang.']);
            }
            if (GoodsReceipt::where('harvest_transaction_id', $harvest->id)->when($receipt, fn ($q) => $q->where('id', '!=', $receipt->id))->exists()) {
                throw ValidationException::withMessages(['harvest_transaction_id' => 'Pembelian ini sudah memiliki dokumen penerimaan.']);
            }
        }
    }

    private function checkLines(array $data): void
    {
        if (empty($data['lines'])) {
            throw ValidationException::withMessages(['lines' => 'Isi rincian kualitas/area penerimaan.']);
        }
        $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);
        if ($inventory->stock_stage !== 'material') {
            throw ValidationException::withMessages(['inventory_id' => 'Penerimaan baru hanya masuk gudang bahan, bukan stok barang jadi atau saldo lama.']);
        }
        foreach ($data['lines'] as $index => $line) {
            $grade = QualityGrade::lockForUpdate()->findOrFail($line['quality_grade_id']);
            $area = WarehouseArea::lockForUpdate()->findOrFail($line['warehouse_area_id']);
            if (! $grade->is_active || ! $area->is_active || $area->usage !== 'receiving'
                || (int) $area->inventory_id !== (int) $inventory->id || ! $area->grades()->whereKey($grade->id)->exists()) {
                throw ValidationException::withMessages(['lines.'.$index.'.warehouse_area_id' => 'Area harus aktif, sesuai gudang bahan, dan mengizinkan kualitas yang dipilih.']);
            }
        }
    }

    public function linkPurchase(GoodsReceipt $receipt, int $harvestId): void
    {
        DB::transaction(function () use ($receipt, $harvestId) {
            $receipt = GoodsReceipt::lockForUpdate()->findOrFail($receipt->id);
            if ($receipt->harvest_transaction_id) {
                throw ValidationException::withMessages(['harvest_transaction_id' => 'Penerimaan sudah terhubung ke pembelian.']);
            }
            $data = $receipt->only(['farmer_id', 'inventory_id']);
            $data['received_date'] = $receipt->received_date->format('Y-m-d');
            $data['harvest_transaction_id'] = $harvestId;
            $this->checkSource($data, $receipt);
            $receipt->harvest_transaction_id = $harvestId;
            $receipt->save();
            if ($receipt->status === 'confirmed') {
                $this->valueBatches($receipt);
                HarvestTransaction::whereKey($harvestId)->update(['is_received' => true]);
                app(OperationalAccountingService::class)->receipt($receipt);
            }
        });
    }

    private function valueBatches(GoodsReceipt $receipt): void
    {
        if (! Schema::hasColumn('material_batches', 'remaining_cost_cents')) {
            return;
        }
        $lines = $receipt->lines()->with('batch')->orderBy('id')->get();
        $totalWeight = (int) round($lines->sum('actual_weight_kg') * 100);
        $totalCost = $receipt->harvest_transaction_id ? (int) $receipt->harvest->total_harvest_value * 100 : 0;
        $assigned = 0;
        $assignedWeight = 0;
        foreach ($lines as $index => $line) {
            if (! $line->batch) {
                continue;
            }
            $assignedWeight += (int) round($line->actual_weight_kg * 100);
            $cost = (int) round($totalCost * $assignedWeight / $totalWeight) - $assigned;
            $assigned += $cost;
            $line->batch->update(['remaining_weight_kg' => $line->actual_weight_kg, 'remaining_cost_cents' => $cost, 'is_costed' => (bool) $receipt->harvest_transaction_id]);
        }
    }
}
