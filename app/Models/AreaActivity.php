<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaActivity extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['activity_date' => 'date:Y-m-d'];

    public function area()
    {
        return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id');
    }
}
