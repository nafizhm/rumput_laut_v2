<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['debit_cents' => 'integer', 'credit_cents' => 'integer'];
    public function entry() { return $this->belongsTo(JournalEntry::class, 'journal_entry_id'); }
    public function account() { return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id'); }
}
