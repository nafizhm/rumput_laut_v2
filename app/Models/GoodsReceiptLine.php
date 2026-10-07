<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptLine extends Model
{
    protected $fillable = ['quality_grade_id', 'warehouse_area_id', 'actual_weight_kg', 'sack_count'];
    public function receipt() { return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id'); }
    public function grade() { return $this->belongsTo(QualityGrade::class, 'quality_grade_id'); }
    public function area() { return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id'); }
    public function batch() { return $this->hasOne(MaterialBatch::class); }
}
