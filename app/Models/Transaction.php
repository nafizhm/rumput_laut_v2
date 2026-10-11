<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasOperationalJournals;

    protected $fillable = [
        'type',
        'date',
        'amount',
        'description',
        'notes',
        'expense_category_id',
        'chart_of_account_id',
        'cash_account_id',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_account_id');
    }
}
