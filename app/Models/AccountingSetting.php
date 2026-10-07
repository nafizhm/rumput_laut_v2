<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingSetting extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['start_date' => 'date:Y-m-d'];
}
