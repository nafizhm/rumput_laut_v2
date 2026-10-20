<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;

class ProductionRun extends Model
{
    use HasOperationalJournals;

    protected $guarded = ['id'];

    protected $casts = ['started_date' => 'date:Y-m-d', 'completed_date' => 'date:Y-m-d', 'packed_date' => 'date:Y-m-d', 'warehouse_only' => 'boolean', 'wip_mode' => 'boolean'];

    public function inputs()
    {
        return $this->hasMany(ProductionInput::class);
    }

    public function sourceArea() { return $this->belongsTo(WarehouseArea::class, 'source_area_id'); }
    public function transfers() { return $this->hasMany(WipTransfer::class); }

    public function getRemainingWipSacksAttribute(): int
    {
        if ($this->status === 'packed') return 0;
        return max(0, (int) $this->input_sacks - ($this->wip_mode ? (int) $this->transfers->sum('source_sacks') : 0));
    }

    // A partial WIP transfer already produces sellable stock while the parent WIP remains open.
    public function scopeHasReadyOutputs($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'packed');
            if (\Illuminate\Support\Facades\Schema::hasColumn('production_runs', 'wip_mode')) {
                $q->orWhere(fn ($wip) => $wip->where('wip_mode', true)->where('status', 'wip'));
            }
        });
    }

    public function outputs()
    {
        return $this->hasMany(FinishedBatch::class);
    }

    public function area()
    {
        return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id');
    }
}
