<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialBatch extends Model
{
    protected $fillable = ['code', 'goods_receipt_line_id', 'incoming_weight_kg', 'incoming_sacks', 'remaining_sacks', 'status',
        'remaining_weight_kg', 'remaining_cost_cents', 'is_costed'];

    public function line()
    {
        return $this->belongsTo(GoodsReceiptLine::class, 'goods_receipt_line_id');
    }
}
