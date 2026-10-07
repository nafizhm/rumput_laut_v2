<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\PaySaleRequest;
use App\Http\Requests\Transaction\StoreSaleRequest;
use App\Models\Factory;
use App\Models\FinishedBatch;
use App\Models\Inventory;
use App\Models\SalesTransaction;
use App\Services\Transaction\SalesService;
use App\Traits\JsonResponser;
use Exception;
use Illuminate\Support\Facades\Schema;

class SalesController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected SalesService $service
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'invoice_number', 'label' => 'No. Penjualan', 'sortable' => true],
            ['key' => 'sale_date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
            ['key' => 'factory.name', 'label' => 'Pabrik'],
            ['key' => 'inventory.item_name', 'label' => 'Gudang'],
            ['key' => 'stock_mode', 'label' => 'Pengeluaran Stok', 'render' => 'stock_mode'],
            ['key' => 'total_weight_kg', 'label' => 'Berat (Kg)', 'render' => 'weight', 'sortable' => true, 'align' => 'right'],
            ['key' => 'price_per_kg', 'label' => 'Harga / Kg', 'render' => 'currency', 'align' => 'right'],
            ['key' => 'total_sale_value', 'label' => 'Total Nilai', 'render' => 'total_sale_value', 'sortable' => true, 'align' => 'right'],
            ['key' => 'payment_status', 'label' => 'Status', 'render' => 'payment_status', 'align' => 'center'],
            ['key' => 'paid_amount', 'label' => 'Sudah Dibayar', 'render' => 'paid_amount', 'align' => 'right'],
            ['key' => 'remaining_amount', 'label' => 'Sisa Tagihan', 'render' => 'remaining_amount', 'align' => 'right'],
            ['key' => 'payment_date', 'label' => 'Tanggal Bayar', 'render' => 'payment_date'],
            ['key' => 'action', 'label' => 'Aksi', 'render' => 'action', 'width' => '220px'],
        ];

        $data = $this->service->getAllSales();
        $factories = Factory::orderBy('name')->get()->map(fn ($f) => ['value' => $f->id, 'label' => $f->name]);
        $inventories = Inventory::where('stock_stage', '!=', 'material')->orderBy('item_name')->get()->map(fn ($i) => [
            'value' => $i->id,
            'label' => $i->item_name,
            'stock_stage' => $i->stock_stage,
            'current_stock' => (float) $i->current_stock_kg,
            'current_sacks' => (int) $i->current_sack_count,
        ]);

        $finishedBatches = $this->finishedBatchOptions();

        return view('pages.transaction.sales.index', compact('data', 'columns', 'factories', 'inventories', 'finishedBatches'));
    }

    private function finishedBatchOptions()
    {
        return Schema::hasTable('finished_batches') ? FinishedBatch::with(['grade', 'area'])->where('remaining_weight_kg', '>', 0)->get()->map(fn ($b) => [
            'id' => $b->id, 'inventory_id' => $b->inventory_id, 'label' => $b->code.' / '.$b->grade->name.' / '.$b->area->code.' / '.$b->remaining_weight_kg.' kg / '.$b->remaining_packages.' kemasan',
        ])->values() : collect();
    }

    public function finishedBatches()
    {
        return $this->successResponse('Batch barang jadi tersedia', $this->finishedBatchOptions());
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
