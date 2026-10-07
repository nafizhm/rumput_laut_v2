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
            ['key' => 'number', 'label' => 'No. Invoice', 'sortable' => true],
            ['key' => 'sale', 'label' => 'Penjualan Terkait', 'render' => 'sale'],
            ['key' => 'invoice_date', 'label' => 'Tanggal', 'sortable' => true, 'render' => 'date'],
            ['key' => 'customer_name', 'label' => 'Konsumen', 'sortable' => true],
            ['key' => 'total_amount', 'label' => 'Total Tagihan', 'render' => 'total', 'align' => 'right'],
            ['key' => 'payment_status', 'label' => 'Pembayaran', 'render' => 'payment'],
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
        $sales = SalesTransaction::with(['factory', 'inventory', 'invoice'])->orderByDesc('sale_date')->get()
            ->filter(fn ($sale) => $sale->invoice || ($sale->payment_status !== 'paid' && (int) $sale->paid_amount === 0))
            ->map(fn ($sale) => [
                'value' => $sale->id, 'label' => $sale->invoice_number.' - '.$sale->factory->name,
                'invoice_id' => $sale->invoice?->id, 'customer_name' => $sale->factory->name,
                'customer_address' => $sale->factory->address ?? '', 'item_name' => $sale->inventory->item_name,
                'quantity_kg' => $sale->total_weight_kg, 'price_per_kg' => $sale->price_per_kg,
                'total_amount' => $sale->total_sale_value,
                'stock_mode' => $sale->stock_mode,
            ])->values();
        return view('pages.sales.invoice.index', compact('data', 'columns', 'orders', 'sales'));
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
        return view('pages.sales.invoice.print', compact('invoice'));
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
