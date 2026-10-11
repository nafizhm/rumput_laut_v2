<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPurchaseOrderItem extends Model
{
    protected $fillable = ['quality_grade_id', 'item_name', 'grade_name', 'quantity_kg', 'price_per_kg'];
    protected $casts = ['quantity_kg' => 'decimal:2', 'price_per_kg' => 'decimal:2'];
    protected $appends = ['description', 'total_amount'];

    public function order()
    {
        return $this->belongsTo(CustomerPurchaseOrder::class, 'customer_purchase_order_id');
    }

    public function grade()
    {
        return $this->belongsTo(QualityGrade::class, 'quality_grade_id');
    }

    public function salesTransactions()
    {
        return $this->hasMany(SalesTransaction::class);
    }

    public function getDescriptionAttribute(): string
    {
        return $this->item_name.($this->grade_name ? ' - '.$this->grade_name : '');
    }

    public function getTotalAmountAttribute(): float
    {
        return round((float) $this->quantity_kg * (float) $this->price_per_kg, 2);
    }
}
