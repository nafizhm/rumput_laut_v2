<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedBatch extends Model
{
    protected $guarded = ['id'];

    public function scopeReadyForSale(\Illuminate\Database\Eloquent\Builder $query, bool $areaBalance = false): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($stock) use ($areaBalance) {
            $stock->where('remaining_weight_kg', '>', 0);
            if ($areaBalance) $stock->orWhere('remaining_packages', '>', 0);
            else $stock->where('remaining_packages', '>', 0);
        })
            ->where(fn ($stock) => $stock->whereNull('production_run_id')
                ->orWhereHas('run', fn ($run) => $run->hasReadyOutputs()))
            ->whereHas('area', fn ($area) => $area->where('is_active', true)->where('usage', 'finished'));
    }

    public function run()
    {
        return $this->belongsTo(ProductionRun::class, 'production_run_id');
    }

    public function transfer() { return $this->belongsTo(WipTransfer::class, 'wip_transfer_id'); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }

    public function area()
    {
        return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id');
    }

    public function grade()
    {
        return $this->belongsTo(QualityGrade::class, 'quality_grade_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
