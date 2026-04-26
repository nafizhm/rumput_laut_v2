<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FarmerLedger extends Model
{
    protected $fillable = [
        'farmer_id',
        'reference_type',
        'reference_id',
        'type',
        'amount',
        'balance_after',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
