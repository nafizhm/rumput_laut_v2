<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseArea extends Model
{
    // Master data defines the function through usage (receiving/processing/finished).
    // Names and codes are editable labels, never rules for classifying stock.
    protected $fillable = ['code', 'name', 'inventory_id', 'usage', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function inventory() { return $this->belongsTo(Inventory::class); }
    public function grades() { return $this->belongsToMany(QualityGrade::class); }
    public function batches() {
        return $this->hasManyThrough(MaterialBatch::class, GoodsReceiptLine::class, 'warehouse_area_id', 'goods_receipt_line_id');
    }
}
