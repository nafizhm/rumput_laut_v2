<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class SalesTransaction extends Model
{
    protected $fillable = [
        'factory_id',
        'inventory_id',
        'invoice_number',
        'sale_date',
        'total_weight_kg',
        'price_per_kg',
        'total_sale_value',
        'payment_status',
        'payment_date',
        'paid_amount',
        'payment_proof_path',
        'payment_notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'payment_date' => 'date',
    ];

    protected $appends = [
        'payment_proof_url',
        'payment_status_label',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        if (!$this->payment_proof_path) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_proof_path);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->payment_status === 'paid' ? 'Sudah Terbayar' : 'Belum Terbayar';
    }
}
