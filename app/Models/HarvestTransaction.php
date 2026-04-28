<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HarvestTransaction extends Model
{
    protected $fillable = [
        'farmer_id',
        'batch_code',
        'harvest_date',
        'location',
        'total_weight',
        'sack_count',
        'extra_per_sack',
        'warehouse_weight',
        'price_per_kg',
        'total_harvest_value',
        'cash_payout',
        'debt_deduction',
        'is_received',
    ];

    protected $casts = [
        'harvest_date' => 'date',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function ledgers(): MorphMany
    {
        return $this->morphMany(FarmerLedger::class, 'reference');
    }
}
