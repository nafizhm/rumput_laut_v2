<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = ['number', 'entry_date', 'kind', 'status', 'description', 'source_reference', 'reversal_of_id',
        'source_type', 'source_id', 'is_automatic', 'created_by', 'created_by_name', 'posted_by', 'posted_by_name', 'posted_at'];

    protected $casts = ['entry_date' => 'date:Y-m-d', 'posted_at' => 'datetime'];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function getSourceUrlAttribute(): ?string
    {
        $source = $this->source;

        return match (true) {
            $source instanceof GoodsReceipt => route('penerimaan-barang.show', $source),
            $source instanceof ProductionRun => route('produksi.show', $source),
            $source instanceof Loan => route('manajemen-pinjaman.index'),
            $source instanceof HarvestTransaction => route('manajemen-panen.index'),
            $source instanceof FarmerPayment => route('manajemen-petani.index'),
            $source instanceof InvoicePayment => route('invoice.index'),
            $source instanceof StockMovement => route('manajemen-gudang.movements', ['inventory_id' => $source->inventory_id]),
            $source instanceof SalesTransaction => route('manajemen-penjualan.index'),
            $source instanceof Transaction => route('manajemen-pemasukan-pengeluaran.index'),
            default => null,
        };
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function original()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }
}
