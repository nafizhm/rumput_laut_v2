<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaveInvoiceRequest;
use App\Models\CustomerPurchaseOrder;
use App\Models\Invoice;
use App\Models\SalesTransaction;
use App\Http\Requests\Sales\StoreInvoicePaymentRequest;
use App\Services\Sales\InvoicePaymentService;
use Exception;
use App\Services\Sales\InvoiceService;
use App\Traits\JsonResponser;

class InvoiceController extends Controller
{
    use JsonResponser;

    public function __construct(protected InvoiceService $service) {}

    public function index()
    {
        $columns = [
            ['key' => 'number', 'label' => 'Invoice', 'sortable' => true, 'render' => 'document', 'width' => '220px'],
            ['key' => 'customer_name', 'label' => 'Konsumen / Penjualan', 'sortable' => true, 'render' => 'customer'],
            ['key' => 'item_name', 'label' => 'Barang', 'render' => 'items', 'width' => '240px'],
            ['key' => 'total_amount', 'label' => 'Total Tagihan', 'render' => 'total', 'align' => 'right'],
            ['key' => 'due_date', 'label' => 'Jatuh Tempo', 'sortable' => true, 'render' => 'due'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '240px', 'render' => 'action'],
        ];
        $data = $this->service->getAll();
        $orders = CustomerPurchaseOrder::with('deliveryNotes')->orderByDesc('po_date')->get()->map(fn ($po) => [
            'value' => $po->id, 'label' => $po->po_number.' - '.$po->customer_name,
            'customer_name' => $po->customer_name, 'customer_address' => $po->customer_address,
            'item_name' => $po->item_name, 'quantity_kg' => $po->quantity_kg, 'price_per_kg' => $po->price_per_kg,
            'delivery_notes' => $po->deliveryNotes->map(fn ($note) => [
                'id' => $note->id, 'number' => $note->number, 'date' => ($note->shipped_date ?? $note->delivery_date)->format('d/m/Y'),
                'status' => $note->status, 'sales_transaction_id' => $note->sales_transaction_id,
            ])->values(),
        ]);
        return view('pages.sales.invoice.index', compact('data', 'columns', 'orders'));
    }

    public function create()
    {
        return view('pages.sales.invoice.form', $this->formData());
    }

    public function edit(Invoice $invoice)
    {
        return view('pages.sales.invoice.form', $this->formData($invoice));
    }

    private function formData(?Invoice $invoice = null): array
    {
        $orders = CustomerPurchaseOrder::with(\Illuminate\Support\Facades\Schema::hasTable('delivery_note_items') ? 'deliveryNotes.items' : 'deliveryNotes')->orderByDesc('po_date')->get()->map(fn ($po) => [
            'value' => $po->id, 'label' => $po->po_number.' - '.$po->customer_name,
            'customer_name' => $po->customer_name, 'customer_address' => $po->customer_address,
            'item_name' => $po->item_name, 'quantity_kg' => $po->quantity_kg, 'price_per_kg' => $po->price_per_kg,
            'delivery_notes' => $po->deliveryNotes->map(fn ($note) => [
                'id' => $note->id, 'number' => $note->number, 'date' => ($note->shipped_date ?? $note->delivery_date)->format('d/m/Y'),
                'status' => $note->status, 'sales_transaction_ids' => collect($note->relationLoaded('items') ? $note->items->pluck('sales_transaction_id') : [])->push($note->sales_transaction_id)->filter()->values(),
            ])->values(),
        ]);
        $sales = SalesTransaction::with(['factory', 'inventory', 'invoice'])->orderByDesc('sale_date')->get()
            ->groupBy(fn ($sale) => $sale->sale_group_number ? 'group:'.$sale->sale_group_number : 'sale:'.$sale->id)
            ->map(function ($items) use ($invoice) {
                $sale = $items->sortBy('id')->first();
                $linked = Invoice::whereIn('sales_transaction_id', $items->pluck('id'))->first();
                if (($linked && $linked->id !== $invoice?->id) || (!$linked && $items->contains(fn ($item) => $item->payment_status === 'paid' || (int) $item->paid_amount > 0))) {
                    return null;
                }
                $quantity = (float) $items->sum('total_weight_kg');
                $subtotal = $items->sum(fn ($item) => (float) $item->total_weight_kg * (float) $item->price_per_kg);
                return [
                    'value' => $linked?->sales_transaction_id ?? $sale->id, 'label' => $sale->sale_reference.' - '.$sale->factory->name.' ('.$items->count().' barang)',
                    'invoice_id' => $linked?->id, 'customer_name' => $sale->factory->name,
                    'customer_address' => $sale->factory->address ?? '', 'item_name' => $items->count() > 1 ? 'Penjualan '.$sale->sale_reference : $sale->document_item_name,
                    'quantity_kg' => $quantity, 'price_per_kg' => $quantity > 0 ? round($subtotal / $quantity, 2) : 0,
                    'total_amount' => $items->sum('total_sale_value'), 'subtotal' => $subtotal,
                    'stock_mode' => $sale->stock_mode, 'customer_purchase_order_id' => $sale->customer_purchase_order_id,
                    'sales_transaction_ids' => $items->pluck('id')->values(),
                    'items' => $items->map(fn ($item) => ['id' => $item->id, 'item_name' => $item->document_item_name,
                        'quantity_kg' => $item->total_weight_kg, 'price_per_kg' => $item->price_per_kg,
                        'deduction_type' => $item->deduction_type, 'deduction_value' => $item->deduction_value,
                        'bonus_weight' => (int) $item->sold_sacks * (float) $item->deduction_weight_kg,
                        'subtotal' => (float) $item->total_weight_kg * (float) $item->price_per_kg,
                        'total_amount' => $item->total_sale_value])->values(),
                ];
            })->filter()->values();
        return compact('orders', 'sales', 'invoice');
    }

    public function data()
    {
        return $this->successResponse('Data invoice berhasil diambil', $this->service->getAll());
    }

    public function store(SaveInvoiceRequest $request)
    {
        $this->service->store($request->validated());
        return $this->successResponse('Invoice berhasil ditambahkan');
    }

    public function update(SaveInvoiceRequest $request, Invoice $invoice)
    {
        $this->service->update($invoice, $request->validated());
        return $this->successResponse('Invoice berhasil diperbarui');
    }

    public function destroy(Invoice $invoice)
    {
        $this->service->delete($invoice);
        return $this->successResponse('Invoice berhasil dihapus');
    }

    public function print(Invoice $invoice)
    {
        return view('pages.sales.invoice.print', ['invoice' => $invoice, 'profile' => \App\Models\CompanyProfile::current()]);
    }

    public function pay(StoreInvoicePaymentRequest $request, Invoice $invoice, InvoicePaymentService $payments)
    {
        try {
            $data = $request->validated();
            // The URL is authoritative, not an invoice id submitted by the client.
            $data['invoice_id'] = $invoice->id;
            $payments->recordPayment($data);
            return $this->successResponse('Pembayaran invoice berhasil dicatat');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
