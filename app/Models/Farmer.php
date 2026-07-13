<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farmer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'current_debt',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function harvestTransactions(): HasMany
    {
        return $this->hasMany(HarvestTransaction::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(FarmerLedger::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FarmerPayment::class);
    }
}
