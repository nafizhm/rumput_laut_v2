<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    public const CATEGORIES = ['Harta' => '1', 'Utang' => '2', 'Modal' => '3', 'Pendapatan' => '4', 'Biaya' => '5'];

    protected $fillable = ['code', 'name', 'category', 'account_group', 'normal_balance', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
