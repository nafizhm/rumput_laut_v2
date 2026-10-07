<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;

class ProductionRun extends Model
{
    use HasOperationalJournals;

    protected $guarded = ['id'];

    protected $casts = ['started_date' => 'date:Y-m-d', 'completed_date' => 'date:Y-m-d', 'packed_date' => 'date:Y-m-d'];

    public function inputs()
    {
        return $this->hasMany(ProductionInput::class);
    }

    public function outputs()
    {
        return $this->hasMany(FinishedBatch::class);
    }

    public function area()
    {
        return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id');
    }
}
