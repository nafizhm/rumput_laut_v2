<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Loan extends Model
{
    use HasOperationalJournals;

    protected $fillable = [
        'farmer_id',
        'loan_date',
        'amount',
        'description',
    ];

    protected $casts = [
        'loan_date' => 'date:Y-m-d',
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
