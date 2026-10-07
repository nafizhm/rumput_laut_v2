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
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date:Y-m-d',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
