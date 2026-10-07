<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionInput extends Model
{
    protected $guarded = ['id'];

    public function batch()
    {
        return $this->belongsTo(MaterialBatch::class, 'material_batch_id');
    }

    public function run()
    {
        return $this->belongsTo(ProductionRun::class, 'production_run_id');
    }
}
