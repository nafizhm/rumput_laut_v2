<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CompanyProfile extends Model
{
    protected $fillable = ['company_name', 'address', 'phone', 'tagline', 'tax_number', 'bank_name', 'bank_account', 'bank_account_name', 'signatory_name'];

    public static function current(): self
    {
        return (Schema::hasTable('company_profiles') ? static::find(1) : null)
            ?? new static(['company_name' => 'PT Albagro Indo Sejahtera', 'tagline' => 'Pemasok Rumput Laut Kering']);
    }
}
