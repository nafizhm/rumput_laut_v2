<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovementLocation extends Model
{
    protected $guarded = ['id'];

    public function area()
    {
        return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id');
    }
}
