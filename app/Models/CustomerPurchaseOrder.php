<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPurchaseOrder extends Model
{
    protected $fillable = [
        'po_number', 'po_date', 'customer_name', 'customer_phone', 'customer_address',
        'item_name', 'quantity_kg', 'price_per_kg', 'delivery_date', 'notes',
        'factory_id', 'po_file_path', 'po_file_name', 'po_file_mime',
    ];

    protected $casts = [
        'po_date' => 'date:Y-m-d',
        'delivery_date' => 'date:Y-m-d',
        'quantity_kg' => 'decimal:2',
        'price_per_kg' => 'decimal:2',
    ];

    protected $hidden = ['po_file_path'];

    protected $appends = ['total_amount', 'po_file_url'];

    public function deliveryNotes()
    {
        return $this->hasMany(DeliveryNote::class)->orderBy('delivery_date')->orderBy('id');
    }

    public function getPoFileUrlAttribute(): ?string
    {
        return $this->po_file_path ? route('po-konsumen.file', $this) : null;
    }

    public function getTotalAmountAttribute(): float
    {
        return round((float) $this->quantity_kg * (float) $this->price_per_kg, 2);
    }
}
