<?php

namespace App\Services\Sales;

use App\Models\CustomerPurchaseOrder;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\SalesTransaction;
use App\Services\Setting\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function getAll()
    {
        return Invoice::with(['payments', 'sale'])->orderByDesc('invoice_date')->orderByDesc('id')->get();
    }

    public function store(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            if (empty($data['number'])) {
                $data['number'] = app(DocumentNumberService::class)->next('invoice', $data['invoice_date']);
            }
            return Invoice::create($this->withReferences($this->prepareSale($data)));
        });
    }

    public function update(Invoice $invoice, array $data): bool
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            return $invoice->update($this->withReferences($this->prepareSale($data, $invoice)));
        });
    }

    public function delete(Invoice $invoice): bool
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->payments()->exists() || (int) $invoice->paid_amount > 0 || $invoice->payment_status === 'paid') {
                throw ValidationException::withMessages(['invoice' => 'Invoice dengan pembayaran tidak dapat dihapus.']);
            }

            return $invoice->delete();
        });
    }

    private function prepareSale(array $data, ?Invoice $invoice = null): array
    {
        $firstPaymentDate = $invoice?->payments()->min('payment_date');
        if ($firstPaymentDate && strtotime($data['invoice_date']) > strtotime($firstPaymentDate)) {
            throw ValidationException::withMessages(['invoice_date' => 'Tanggal invoice tidak boleh setelah pembayaran yang sudah tercatat.']);
        }
        $saleId = $data['sales_transaction_id'] ?? $invoice?->sales_transaction_id;
        if ($invoice?->sales_transaction_id && (int) $saleId !== (int) $invoice->sales_transaction_id) {
            throw ValidationException::withMessages(['sales_transaction_id' => 'Penjualan terkait tidak dapat diganti atau dilepas.']);
        }
        if (! $saleId) {
            if (! $invoice) {
                throw ValidationException::withMessages(['sales_transaction_id' => 'Pilih penjualan yang akan ditagihkan.']);
            }
            unset($data['payment_status']);
            if ($invoice->payments()->exists() || (int) $invoice->paid_amount > 0) {
                foreach (['quantity_kg', 'price_per_kg', 'customer_name', 'customer_purchase_order_id'] as $field) {
                    $different = in_array($field, ['quantity_kg', 'price_per_kg'])
                        ? (float) ($data[$field] ?? 0) !== (float) $invoice->$field
                        : (string) ($data[$field] ?? '') !== (string) $invoice->$field;
                    if ($different) {
                        throw ValidationException::withMessages([$field => 'Data tagihan dengan pembayaran tidak dapat diubah.']);
                    }
                }
            }

            return $data;
        }
        $sale = SalesTransaction::with(['factory', 'inventory'])->lockForUpdate()->findOrFail($saleId);
        $items = SalesTransaction::with(['factory', 'inventory'])
            ->when($sale->sale_group_number, fn ($query) => $query->where('sale_group_number', $sale->sale_group_number), fn ($query) => $query->whereKey($sale->id))
            ->orderBy('id')->lockForUpdate()->get();
        if ($items->contains(fn ($item) => $item->factory_id !== $sale->factory_id || $item->customer_purchase_order_id !== $sale->customer_purchase_order_id)) {
            throw ValidationException::withMessages(['sales_transaction_id' => 'Seluruh barang penjualan harus memiliki konsumen dan PO yang sama.']);
        }
        if (Invoice::whereIn('sales_transaction_id', $items->pluck('id'))->when($invoice, fn ($q) => $q->where('id', '!=', $invoice->id))->exists()) {
            throw ValidationException::withMessages(['sales_transaction_id' => 'Penjualan ini sudah memiliki invoice.']);
        }
        if (! $invoice?->sales_transaction_id && ($items->contains(fn ($item) => $item->payment_status === 'paid' || (int) $item->paid_amount > 0) || (int) $invoice?->paid_amount > 0 || $invoice?->payment_status === 'paid' || $invoice?->payments()->exists())) {
            throw ValidationException::withMessages(['sales_transaction_id' => 'Transaksi yang sudah dibayar memerlukan rekonsiliasi sebelum dihubungkan.']);
        }
        if ($invoice && ! $invoice->sales_transaction_id && (
            (int) round($invoice->total_amount) !== (int) $items->sum('total_sale_value')
            || mb_strtolower(trim($invoice->customer_name)) !== mb_strtolower(trim($sale->factory->name))
        )) {
            throw ValidationException::withMessages(['sales_transaction_id' => 'Konsumen dan total invoice lama harus sesuai dengan penjualan sebelum dihubungkan.']);
        }
        if ($items->contains(fn ($item) => strtotime($data['invoice_date']) < strtotime($item->sale_date->format('Y-m-d')))) {
            throw ValidationException::withMessages(['invoice_date' => 'Tanggal invoice tidak boleh sebelum tanggal penjualan.']);
        }
        $po = CustomerPurchaseOrder::findOrFail($data['customer_purchase_order_id']);
        if ($sale->customer_purchase_order_id && (int) $sale->customer_purchase_order_id !== (int) $po->id) {
            throw ValidationException::withMessages(['customer_purchase_order_id' => 'PO invoice harus sama dengan PO penjualan.']);
        }
        if ($sale->deliveryNotes()->whereNotNull('customer_purchase_order_id')->where('customer_purchase_order_id', '!=', $po->id)->exists()) {
            throw ValidationException::withMessages(['customer_purchase_order_id' => 'PO invoice harus sama dengan surat jalan penjualan ini.']);
        }
        if ($invoice && (int) $invoice->customer_purchase_order_id !== (int) $po->id && $invoice->payments()->exists()) {
            throw ValidationException::withMessages(['customer_purchase_order_id' => 'PO invoice yang sudah memiliki pembayaran tidak dapat diganti.']);
        }
        if (($po->factory_id && (int) $po->factory_id !== (int) $sale->factory_id)
            || (! $po->factory_id && mb_strtolower(trim($po->customer_name)) !== mb_strtolower(trim($sale->factory->name)))) {
            throw ValidationException::withMessages(['customer_purchase_order_id' => 'Konsumen PO harus sesuai dengan penjualan.']);
        }
        $data['sales_transaction_id'] = $sale->id;
        $data['customer_name'] = $sale->factory->name;
        $data['item_name'] = $items->count() > 1 ? 'Penjualan '.$sale->sale_reference : $sale->document_item_name;
        $data['quantity_kg'] = $items->sum('total_weight_kg');
        $data['price_per_kg'] = round($items->sum(fn ($item) => (float) $item->total_weight_kg * (float) $item->price_per_kg) / (float) $data['quantity_kg'], 2);
        $data['sale_total_amount'] = $items->sum('total_sale_value');
        $data['payment_status'] = $invoice?->payment_status ?? 'unpaid';
        $data['paid_amount'] = (int) $invoice?->paid_amount;

        return $data;
    }

    private function withReferences(array $data): array
    {
        $po = CustomerPurchaseOrder::findOrFail($data['customer_purchase_order_id']);
        $query = DeliveryNote::where('customer_purchase_order_id', $po->id);
        $sale = !empty($data['sales_transaction_id']) ? SalesTransaction::findOrFail($data['sales_transaction_id']) : null;
        $saleIds = $sale ? SalesTransaction::when($sale->sale_group_number,
            fn ($query) => $query->where('sale_group_number', $sale->sale_group_number),
            fn ($query) => $query->whereKey($sale->id))->pluck('id') : collect();
        if (! empty($data['sales_transaction_id']) && SalesTransaction::find($data['sales_transaction_id'])?->stock_mode === 'delivery_note') {
            $query->where(function($q) use($saleIds) {
                $q->whereIn('sales_transaction_id',$saleIds);
                if (\Illuminate\Support\Facades\Schema::hasTable('delivery_note_items')) $q->orWhereHas('items',fn($item)=>$item->whereIn('sales_transaction_id',$saleIds));
            })->where('status', 'shipped');
        } elseif (! empty($data['sales_transaction_id'])) {
            // Preserve unlinked historical documents; never take another sale's shipment.
            $query->where(fn ($q) => $q->where('sales_transaction_id', $data['sales_transaction_id'])
                ->orWhere(fn ($legacy) => $legacy->whereNull('sales_transaction_id')->where('status', 'legacy')));
        }
        $notes = $query->orderBy('delivery_date')->orderBy('id')->get();
        $data['po_number'] = $po->po_number;
        $data['delivery_note_id'] = $notes->first()?->id;
        $data['delivery_note_number'] = $notes->first()?->number;
        $data['delivery_note_references'] = $notes->map(fn ($note) => [
            'id' => $note->id, 'number' => $note->number, 'date' => $note->delivery_date->format('d/m/Y'),
        ])->values()->all();

        return $data;
    }
}
