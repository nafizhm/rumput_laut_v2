<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Loan extends Model
{
    protected $fillable = [
        'farmer_id',
        'loan_date',
        'amount',
        'description',
    ];

    protected $casts = [
        'loan_date' => 'date',
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
