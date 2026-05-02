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
        'sale_deduction_type',
        'sale_deduction_value',
        'sale_deduction_amount',
        'total_harvest_value',
        'cash_payout',
        'debt_deduction',
        'is_received',
    ];

    protected $casts = [
        'harvest_date' => 'date',
    ];

    protected $appends = [
        'remaining_debt_after_payment',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function ledgers(): MorphMany
    {
        return $this->morphMany(FarmerLedger::class, 'reference');
    }

    public function getRemainingDebtAfterPaymentAttribute(): int
    {
        $currentDebt = (int) ($this->farmer?->current_debt ?? 0);
        return max(0, $currentDebt);
    }
}
