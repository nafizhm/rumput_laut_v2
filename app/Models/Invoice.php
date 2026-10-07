<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = ['number', 'invoice_date', 'due_date', 'customer_purchase_order_id', 'po_number', 'delivery_note_id', 'delivery_note_number', 'delivery_note_references', 'customer_name', 'customer_address', 'item_name', 'quantity_kg', 'price_per_kg', 'payment_status', 'payment_terms', 'notes', 'sales_transaction_id', 'sale_total_amount', 'paid_amount'];

    protected $casts = ['delivery_note_references' => 'array', 'invoice_date' => 'date:Y-m-d', 'due_date' => 'date:Y-m-d', 'quantity_kg' => 'decimal:2', 'price_per_kg' => 'decimal:2'];

    protected $appends = ['total_amount', 'term_days', 'days_until_due', 'due_label', 'remaining_amount', 'payment_status_label', 'deduction_amount'];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getDeliveryNoteReferencesAttribute($value): array
    {
        if ($this->sales_transaction_id && $this->sale?->stock_mode === 'delivery_note') {
            return DeliveryNote::where('sales_transaction_id', $this->sales_transaction_id)->where('status', 'shipped')
                ->orderBy('shipped_date')->orderBy('id')->get()->map(fn ($note) => [
                    'id' => $note->id, 'number' => $note->number, 'date' => $note->shipped_date->format('d/m/Y'),
                ])->all();
        }
        return is_array($value) ? $value : (json_decode($value ?? '[]', true) ?? []);
    }

    public function getRemainingAmountAttribute(): int
    {
        return $this->payment_status === 'paid' ? 0 : max(0, (int) round($this->total_amount) - (int) $this->paid_amount);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Sudah lunas', 'partial' => 'Cicilan', default => 'Belum lunas',
        };
    }

    public function getDeductionAmountAttribute(): float
    {
        return max(0, round((float) $this->quantity_kg * (float) $this->price_per_kg, 2) - $this->total_amount);
    }

    public function getTotalAmountAttribute(): float
    {
        if ($this->sale_total_amount !== null) {
            return (float) $this->sale_total_amount;
        }
        return round((float) $this->quantity_kg * (float) $this->price_per_kg, 2);
    }

    public function getTermDaysAttribute(): int
    {
        return (int) $this->invoice_date->diffInDays($this->due_date);
    }

    public function getDaysUntilDueAttribute(): int
    {
        return (int) now('Asia/Jakarta')->startOfDay()->diffInDays($this->due_date->format('Y-m-d'), false);
    }

    public function getDueLabelAttribute(): string
    {
        if ($this->payment_status === 'paid') {
            return 'Sudah lunas';
        }

        $days = $this->days_until_due;
        return $days < 0 ? 'Terlambat '.abs($days).' hari' : ($days === 0 ? 'Jatuh tempo hari ini' : $days.' hari lagi');
    }
}
