<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'type',
        'date',
        'amount',
        'description',
        'notes',
        'expense_category_id',
    ];

    protected $appends = [
        'type_label',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
    }
}
