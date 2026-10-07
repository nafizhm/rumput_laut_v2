<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    use HasOperationalJournals;

    protected $fillable = ['received_date', 'farmer_id', 'harvest_transaction_id', 'source_reference', 'inventory_id', 'note_weight_kg', 'notes'];

    protected $casts = ['received_date' => 'date:Y-m-d', 'confirmed_at' => 'datetime'];

    public function lines()
    {
        return $this->hasMany(GoodsReceiptLine::class);
    }

    public function farmer()
    {
        return $this->belongsTo(Farmer::class);
    }

    public function harvest()
    {
        return $this->belongsTo(HarvestTransaction::class, 'harvest_transaction_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
