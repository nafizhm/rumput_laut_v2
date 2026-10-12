<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    public function items() { return $this->hasMany(DeliveryNoteItem::class); }
    public function finishedBatch() { return $this->belongsTo(FinishedBatch::class); }
    protected $fillable = ['finished_batch_id', 'number', 'delivery_date', 'customer_purchase_order_id', 'recipient_name', 'destination_address', 'origin', 'item_name', 'quantity_kg', 'packaging', 'remarks', 'notes', 'sender_name', 'receiver_name', 'sales_transaction_id', 'sack_count', 'vehicle_plate', 'vehicle_type', 'driver_name', 'condition', 'origin_address', 'sender_contact', 'recipient_contact', 'gross_weight_kg', 'tare_weight_kg', 'moisture_percent'];

    protected $casts = ['delivery_date' => 'date:Y-m-d', 'quantity_kg' => 'decimal:2', 'shipped_date' => 'date:Y-m-d', 'shipped_at' => 'datetime'];

    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }
}
