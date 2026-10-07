<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaveDeliveryNoteRequest;
use App\Models\CustomerPurchaseOrder;
use App\Models\DeliveryNote;
use App\Models\SalesTransaction;
use Illuminate\Http\Request;
use App\Services\Sales\DeliveryNoteService;
use App\Traits\JsonResponser;

class DeliveryNoteController extends Controller
{
    use JsonResponser;

    public function __construct(protected DeliveryNoteService $service) {}

    public function index()
    {
        $columns = [
            ['key' => 'number', 'label' => 'No. Surat Jalan', 'sortable' => true],
            ['key' => 'sale.invoice_number', 'label' => 'Penjualan'],
            ['key' => 'status', 'label' => 'Status', 'render' => 'status'],
            ['key' => 'shipped_date', 'label' => 'Tanggal Kirim Aktual', 'render' => 'shipped_date'],
            ['key' => 'delivery_date', 'label' => 'Tanggal', 'sortable' => true, 'render' => 'date'],
            ['key' => 'recipient_name', 'label' => 'Penerima', 'sortable' => true],
            ['key' => 'item_name', 'label' => 'Barang', 'sortable' => true],
            ['key' => 'quantity_kg', 'label' => 'Jumlah (Kg)', 'render' => 'weight', 'align' => 'right'],
            ['key' => 'packaging', 'label' => 'Kemasan'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '240px', 'render' => 'action'],
        ];
        $data = $this->service->getAll();
        $orders = CustomerPurchaseOrder::orderByDesc('po_date')->get([
            'id', 'po_number', 'customer_name', 'customer_address', 'item_name', 'quantity_kg',
        ])->map(fn ($order) => [
            'value' => $order->id, 'label' => $order->po_number.' ? '.$order->customer_name,
            'recipient_name' => $order->customer_name, 'destination_address' => $order->customer_address,
            'item_name' => $order->item_name, 'quantity_kg' => $order->quantity_kg,
        ]);
        $sales = SalesTransaction::with(['factory', 'inventory'])->where('stock_mode', 'delivery_note')->orderByDesc('sale_date')->get()->map(fn ($sale) => [
            'value' => $sale->id, 'label' => $sale->invoice_number.' - '.$sale->factory->name,
            'recipient_name' => $sale->factory->name, 'destination_address' => $sale->factory->address ?? '',
            'item_name' => $sale->inventory->item_name, 'origin' => $sale->inventory->item_name,
            'quantity_kg' => $sale->total_weight_kg, 'sack_count' => $sale->sold_sacks ?? 0,
        ]);
        return view('pages.sales.delivery-note.index', compact('data', 'columns', 'orders', 'sales'));
    }

    public function data()
    {
        return $this->successResponse('Data surat jalan berhasil diambil', $this->service->getAll());
    }

    public function store(SaveDeliveryNoteRequest $request)
    {
        $this->service->store($request->validated());
        return $this->successResponse('Surat jalan berhasil ditambahkan');
    }

    public function update(SaveDeliveryNoteRequest $request, DeliveryNote $surat_jalan)
    {
        $this->service->update($surat_jalan, $request->validated());
        return $this->successResponse('Surat jalan berhasil diperbarui');
    }

    public function destroy(DeliveryNote $surat_jalan)
    {
        $this->service->delete($surat_jalan);
        return $this->successResponse('Surat jalan berhasil dihapus');
    }

    public function print(DeliveryNote $surat_jalan)
    {
        return view('pages.sales.delivery-note.print', ['note' => $surat_jalan]);
    }

    public function dispatch(Request $request, DeliveryNote $surat_jalan)
    {
        $data = $request->validate(['shipped_date' => 'required|date_format:Y-m-d', 'confirm' => 'required|accepted']);
        $this->service->dispatch($surat_jalan, $data['shipped_date'], $request->user());
        return $this->successResponse('Pengiriman dikonfirmasi dan stok dikurangi satu kali');
    }
}
