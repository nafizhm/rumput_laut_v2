<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'name',
        'type',
        'is_active',
    ];

    protected $appends = [
        'type_label',
    ];

    protected $casts = [
        'type' => 'string',
        'is_active' => 'boolean',
    ];

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
