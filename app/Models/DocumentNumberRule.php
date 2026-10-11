<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentNumberRule extends Model
{
    protected $fillable = ['document_type', 'format', 'padding', 'start_number', 'reset_period'];
}
