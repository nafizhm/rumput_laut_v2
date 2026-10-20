<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WipTransfer extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['transfer_date' => 'date:Y-m-d'];

    public function outputs()
    {
        // Area-to-area transfers replicate finished batches. Only the original WIP
        // receipt belongs in the production history and its allocated output cost.
        return $this->hasMany(FinishedBatch::class)->whereHas('stockMovements', fn ($query) => $query
            ->where('direction', 'in')->whereNotNull('production_run_id'));
    }

    public function run()
    {
        return $this->belongsTo(ProductionRun::class, 'production_run_id');
    }

    public function getShrinkageKgAttribute(): float
    {
        return round($this->reference_weight_kg - $this->output_weight_kg, 2);
    }
}
