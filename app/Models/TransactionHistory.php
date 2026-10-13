<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionHistory extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
}
