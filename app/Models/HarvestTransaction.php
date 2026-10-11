<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HarvestTransaction extends Model
{
    use HasOperationalJournals;

    protected $fillable = [
        'farmer_id',
        'submission_key',
        'separate_payment',
        'purchase_date',
        'priced_at',
        'priced_by',
        'priced_by_name',
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
        'notes',
    ];

    protected $casts = [
        'harvest_date' => 'date:Y-m-d',
        'purchase_date' => 'date:Y-m-d',
        'priced_at' => 'datetime',
        'separate_payment' => 'boolean',
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

    public function stockMovement()
    {
        return $this->hasOne(StockMovement::class);
    }

    public function receipt()
    {
        return $this->hasOne(GoodsReceipt::class);
    }

    public function payments() { return $this->hasMany(HarvestPayment::class); }

    public function getOutstandingPayableAttribute(): int
    {
        return $this->separate_payment && $this->priced_at
            ? max(0, (int) $this->total_harvest_value - (int) $this->cash_payout - (int) $this->debt_deduction) : 0;
    }

    public function getPaymentStatusAttribute(): string
    {
        if (! $this->separate_payment) return 'Riwayat Lama — Selesai';
        if (! $this->priced_at) return 'Menunggu Penetapan Harga';
        if ($this->outstanding_payable === 0) return 'Sudah Terbayar';
        return (int) $this->cash_payout + (int) $this->debt_deduction > 0 ? 'Terbayar Sebagian' : 'Belum Terbayar';
    }

    public function getRemainingDebtAfterPaymentAttribute(): int
    {
        $currentDebt = (int) ($this->farmer?->current_debt ?? 0);

        return max(0, $currentDebt);
    }
}
