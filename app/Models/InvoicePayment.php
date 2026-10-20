<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    use HasOperationalJournals;

    protected $fillable = [
        'invoice_id',
        'payment_date',
        'amount',
        'cooperation_deduction',
        'cooperation_notes',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date:Y-m-d',
        'cooperation_deduction' => 'integer',
    ];

    protected $appends = ['settlement_amount'];

    public function getSettlementAmountAttribute(): int
    {
        return (int) $this->amount + (int) $this->cooperation_deduction;
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
