<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DeliveryNoteItem extends Model {
    protected $guarded = ['id'];
    public function sale() { return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id'); }
}
