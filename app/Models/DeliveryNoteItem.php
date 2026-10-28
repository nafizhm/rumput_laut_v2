<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DeliveryNoteItem extends Model {
    protected $fillable = ['delivery_note_id', 'sales_transaction_id', 'finished_batch_id', 'warehouse_area_id', 'item_name', 'quantity_kg', 'sack_count'];
    public function sale() { return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id'); }
    public function finishedBatch() { return $this->belongsTo(FinishedBatch::class); }
}
