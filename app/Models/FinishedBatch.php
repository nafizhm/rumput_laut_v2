<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedBatch extends Model
{
    protected $guarded = ['id'];

    public function run()
    {
        return $this->belongsTo(ProductionRun::class, 'production_run_id');
    }

    public function area()
    {
        return $this->belongsTo(WarehouseArea::class, 'warehouse_area_id');
    }

    public function grade()
    {
        return $this->belongsTo(QualityGrade::class, 'quality_grade_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
