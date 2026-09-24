<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\PaySaleRequest;
use App\Http\Requests\Transaction\StoreSaleRequest;
use App\Models\Factory;
use App\Models\Inventory;
use App\Models\SalesTransaction;
use App\Services\Transaction\SalesService;
use App\Traits\JsonResponser;
use Exception;

class SalesController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected SalesService $service
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'invoice_number', 'label' => 'No. Invoice', 'sortable' => true],
            ['key' => 'sale_date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
            ['key' => 'factory.name', 'label' => 'Pabrik'],
            ['key' => 'total_weight_kg', 'label' => 'Berat (Kg)', 'render' => 'weight', 'sortable' => true, 'align' => 'right'],
            ['key' => 'price_per_kg', 'label' => 'Harga / Kg', 'render' => 'currency', 'align' => 'right'],
            ['key' => 'total_sale_value', 'label' => 'Total Nilai', 'render' => 'total_sale_value', 'sortable' => true, 'align' => 'right'],
            ['key' => 'payment_status', 'label' => 'Status', 'render' => 'payment_status', 'align' => 'center'],
            ['key' => 'action', 'label' => 'Aksi', 'render' => 'action', 'width' => '220px'],
        ];

        $data = $this->service->getAllSales();
        $factories = Factory::orderBy('name')->get()->map(fn($f) => ['value' => $f->id, 'label' => $f->name]);
        $inventories = Inventory::where('item_name', 'Gudang Kering')->orderBy('item_name')->get()->map(fn($i) => [
            'value' => $i->id,
            'label' => $i->item_name,
            'current_stock' => (float) $i->current_stock_kg,
            'current_sacks' => (int) $i->current_sack_count
        ]);

        return view('pages.transaction.sales.index', compact('data', 'columns', 'factories', 'inventories'));
    }

    public function data()
    {
        $data = $this->service->getAllSales();
        return $this->successResponse('Data penjualan berhasil diambil', $data);
    }

    public function store(StoreSaleRequest $request)
    {
        try {
            $this->service->createSale($request->validated());
            return $this->successResponse('Penjualan berhasil dicatat');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function pay(PaySaleRequest $request, SalesTransaction $manajemen_penjualan)
    {
        try {
            $this->service->paySale($manajemen_penjualan, $request->validated());
            return $this->successResponse('Pembayaran penjualan berhasil dicatat');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy(SalesTransaction $manajemen_penjualan)
    {
        try {
            $this->service->deleteSale($manajemen_penjualan);
            return $this->successResponse('Penjualan berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
