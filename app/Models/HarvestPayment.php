<?php

namespace App\Models;

use App\Traits\HasOperationalJournals;
use Illuminate\Database\Eloquent\Model;

class HarvestPayment extends Model
{
    use HasOperationalJournals;

    protected $fillable = ['harvest_transaction_id', 'submission_key', 'payment_date', 'amount', 'debt_deduction', 'cash_account_id', 'notes', 'created_by', 'created_by_name'];
    protected $casts = ['payment_date' => 'date', 'amount' => 'integer', 'debt_deduction' => 'integer'];

    public function harvest() { return $this->belongsTo(HarvestTransaction::class, 'harvest_transaction_id'); }
    public function cashAccount() { return $this->belongsTo(ChartOfAccount::class, 'cash_account_id'); }
    public function ledgers() { return $this->morphMany(FarmerLedger::class, 'reference'); }
}
