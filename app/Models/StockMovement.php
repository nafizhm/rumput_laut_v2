<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasOperationalJournals;

    protected static function booted(): void
    {
        static::created(fn (self $movement) => app(\App\Services\Transaction\MovementLocationService::class)->capture($movement));
    }

    public function locations()
    {
        return $this->hasMany(StockMovementLocation::class);
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    protected $fillable = [
        'production_run_id', 'finished_batch_id', 'cost_cents',
        'goods_receipt_id',
        'delivery_note_id',
        'inventory_id',
        'harvest_transaction_id',
        'sales_transaction_id',
        'movement_date',
        'direction',
        'type',
        'weight_kg',
        'note_weight_kg',
        'discrepancy_kg',
        'sack_count',
        'balance_after_kg',
        'description',
    ];

    protected $casts = [
        'movement_date' => 'date:Y-m-d',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function harvestTransaction(): BelongsTo
    {
        return $this->belongsTo(HarvestTransaction::class);
    }

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }
}
