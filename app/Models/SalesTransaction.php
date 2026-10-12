<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class SalesTransaction extends Model
{
    public function finishedBatch()
    {
        return $this->belongsTo(FinishedBatch::class);
    }

    use HasOperationalJournals;

    protected $fillable = [
        'sale_group_number',
        'item_name',
        'customer_purchase_order_id',
        'customer_purchase_order_item_id',
        'finished_batch_id',
        'quality_grade_id',
        'stock_mode',
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
        'sold_sacks',
        'deduction_type',
        'deduction_value',
        'deduction_weight_kg',
    ];

    protected $casts = [
        'sale_date' => 'date:Y-m-d',
        'payment_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'document_item_name',
        'sale_reference',
        'payment_proof_url',
        'payment_status_label',
        'remaining_amount',
    ];

    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerPurchaseOrder::class, 'customer_purchase_order_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(CustomerPurchaseOrderItem::class, 'customer_purchase_order_item_id');
    }

    public function getDocumentItemNameAttribute(): string
    {
        return $this->item_name ?: ($this->purchaseOrderItem?->description ?? $this->grade?->name ?? $this->inventory?->item_name ?? 'Rumput Laut');
    }

    public function getSaleReferenceAttribute(): string
    {
        return $this->sale_group_number ?: $this->invoice_number;
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(QualityGrade::class, 'quality_grade_id');
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
        if (! $this->payment_proof_path) {
            return null;
        }

        return Storage::disk('public')->url($this->payment_proof_path);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->payment_status === 'paid' ? 'Sudah Terbayar' : ((int) $this->paid_amount > 0 ? 'Cicilan' : 'Belum Terbayar');
    }

    public function getRemainingAmountAttribute(): int
    {
        // Historical paid records may not have a paid_amount value.
        if ($this->payment_status === 'paid') {
            return 0;
        }

        return max(0, (int) $this->total_sale_value - (int) $this->paid_amount);
    }
}
