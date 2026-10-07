<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaveCustomerPurchaseOrderRequest;
use App\Models\CustomerPurchaseOrder;
use App\Models\Factory;
use Illuminate\Support\Facades\Storage;
use App\Services\Sales\CustomerPurchaseOrderService;
use App\Traits\JsonResponser;

class CustomerPurchaseOrderController extends Controller
{
    use JsonResponser;

    public function __construct(protected CustomerPurchaseOrderService $service) {}

    public function index()
    {
        $columns = [
            ['key' => 'po_number', 'label' => 'Nomor PO', 'sortable' => true],
            ['key' => 'po_date', 'label' => 'Tanggal PO', 'sortable' => true, 'render' => 'date'],
            ['key' => 'customer_name', 'label' => 'Konsumen', 'sortable' => true],
            ['key' => 'item_name', 'label' => 'Barang', 'sortable' => true],
            ['key' => 'quantity_kg', 'label' => 'Jumlah (Kg)', 'sortable' => true, 'render' => 'weight', 'align' => 'right'],
            ['key' => 'price_per_kg', 'label' => 'Harga / Kg', 'render' => 'price', 'align' => 'right'],
            ['key' => 'total_amount', 'label' => 'Total', 'render' => 'total', 'align' => 'right'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '240px', 'render' => 'action'],
        ];
        $data = $this->service->getAll();
        $factories = Factory::orderBy('name')->get()->map(fn ($factory) => [
            'value' => $factory->id, 'label' => $factory->name,
            'phone' => $factory->phone, 'address' => $factory->address,
        ]);

        return view('pages.sales.customer-po.index', compact('data', 'columns', 'factories'));
    }

    public function data()
    {
        return $this->successResponse('Data PO konsumen berhasil diambil', $this->service->getAll());
    }

    public function store(SaveCustomerPurchaseOrderRequest $request)
    {
        $this->service->store($request->validated());
        return $this->successResponse('PO konsumen berhasil ditambahkan');
    }

    public function update(SaveCustomerPurchaseOrderRequest $request, CustomerPurchaseOrder $po_konsumen)
    {
        $this->service->update($po_konsumen, $request->validated());
        return $this->successResponse('PO konsumen berhasil diperbarui');
    }

    public function destroy(CustomerPurchaseOrder $po_konsumen)
    {
        $this->service->delete($po_konsumen);
        return $this->successResponse('PO konsumen berhasil dihapus');
    }

    public function file(CustomerPurchaseOrder $po_konsumen)
    {
        abort_unless($po_konsumen->po_file_path && Storage::disk('local')->exists($po_konsumen->po_file_path), 404);

        return Storage::disk('local')->response($po_konsumen->po_file_path, $po_konsumen->po_file_name, [
            'Content-Type' => $po_konsumen->po_file_mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
