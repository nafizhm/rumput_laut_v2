<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    protected $fillable = ['number', 'delivery_date', 'customer_purchase_order_id', 'recipient_name', 'destination_address', 'origin', 'item_name', 'quantity_kg', 'packaging', 'remarks', 'notes', 'sender_name', 'receiver_name', 'sales_transaction_id', 'sack_count'];

    protected $casts = ['delivery_date' => 'date:Y-m-d', 'quantity_kg' => 'decimal:2', 'shipped_date' => 'date:Y-m-d', 'shipped_at' => 'datetime'];

    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }
}
