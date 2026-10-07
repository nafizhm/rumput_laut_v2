<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityGrade extends Model
{
    protected $fillable = ['code', 'name', 'description', 'criteria', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
