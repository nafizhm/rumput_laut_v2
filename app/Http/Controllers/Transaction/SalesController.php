<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\PaySaleRequest;
use App\Http\Requests\Transaction\StoreSaleRequest;
use App\Models\Factory;
use App\Models\CustomerPurchaseOrder;
use App\Models\FinishedBatch;
use App\Models\Inventory;
use App\Models\SalesTransaction;
use App\Services\Transaction\SalesService;
use App\Traits\JsonResponser;
use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected SalesService $service
    ) {}

    public function index(Request $request)
    {
        $request->validate(['view' => 'nullable|in:products']);
        if ($request->input('view') === 'products') {
            $columns = [
                ['key' => 'sale_reference', 'label' => 'No. Nota'],
                ['key' => 'sale_date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
                ['key' => 'factory.name', 'label' => 'Konsumen'],
                ['key' => 'document_item_name', 'label' => 'Produk'],
                ['key' => 'total_weight_kg', 'label' => 'Berat (Kg)', 'render' => 'weight', 'align' => 'right'],
                ['key' => 'sold_sacks', 'label' => 'Kemasan', 'align' => 'right'],
                ['key' => 'price_per_kg', 'label' => 'Harga / Kg', 'render' => 'price', 'align' => 'right'],
                ['key' => 'total_sale_value', 'label' => 'Nilai Bersih', 'render' => 'total', 'align' => 'right'],
            ];
            return view('pages.transaction.sales.products', ['columns' => $columns, 'data' => $this->service->getAllSales()]);
        }
        $columns = [
            ['key' => 'sale_reference', 'label' => 'No. Penjualan', 'sortable' => true],
            ['key' => 'sale_date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
            ['key' => 'factory.name', 'label' => 'Konsumen'],
            ['key' => 'total_sale_value', 'label' => 'Total Nilai', 'render' => 'total_sale_value', 'sortable' => true, 'align' => 'right'],
            ['key' => 'payment_status', 'label' => 'Status', 'render' => 'payment_status', 'align' => 'center'],
            ['key' => 'action', 'label' => 'Aksi', 'render' => 'action', 'width' => '160px'],
        ];

        $data = $this->service->getSaleNotes();
        $factories = Factory::orderBy('name')->get()->map(fn ($f) => ['value' => $f->id, 'label' => $f->name]);
        $finishedBatches = $this->finishedBatchOptions();
        $inventories = Inventory::where('stock_stage', 'finished')
            ->whereIn('id', $finishedBatches->pluck('inventory_id'))->orderBy('item_name')->get()->map(fn ($i) => [
            'value' => $i->id,
            'label' => $i->item_name,
            'stock_stage' => $i->stock_stage,
            'current_stock' => (float) $i->current_stock_kg,
            'current_sacks' => (int) $i->current_sack_count,
        ]);

        $purchaseOrders = $this->purchaseOrderOptions();

        return view('pages.transaction.sales.index', compact('data', 'columns', 'factories', 'inventories', 'finishedBatches', 'purchaseOrders'));
    }

    private function purchaseOrderOptions()
    {
        return CustomerPurchaseOrder::whereNotNull('factory_id')->with(['items' => fn ($q) => $q->withSum('salesTransactions', 'total_weight_kg')])->withSum('salesTransactions', 'total_weight_kg')
            ->orderByDesc('po_date')->get()->map(fn ($po) => [
                'id' => $po->id, 'label' => $po->po_number.' - '.$po->customer_name,
                'factory_id' => $po->factory_id, 'item_name' => $po->item_name,
                'price_per_kg' => $po->price_per_kg,
                'items' => $po->items->map(fn ($item) => [
                    'id' => $item->id, 'description' => $item->description, 'quality_grade_id' => $item->quality_grade_id,
                    'price_per_kg' => $item->price_per_kg,
                    'remaining_kg' => max(0, round((float) $item->quantity_kg - (float) $item->sales_transactions_sum_total_weight_kg, 2)),
                ])->filter(fn ($item) => $item['remaining_kg'] > 0)->values(),
                'remaining_kg' => max(0, round((float) $po->quantity_kg - (float) $po->sales_transactions_sum_total_weight_kg, 2)),
            ])->filter(fn ($po) => $po['remaining_kg'] > 0)->values();
    }

    public function create()
    {
        abort_unless(auth()->user()->can('manajemen-penjualan.create'), 403);
        return view('pages.transaction.sales.create', [
            'factories' => Factory::orderBy('name')->get(['id', 'name']),
            'purchaseOrders' => $this->purchaseOrderOptions(),
            'finishedBatches' => $this->finishedBatchOptions(),
        ]);
    }

    public function purchaseOrders()
    {
        return $this->successResponse('PO tersedia untuk penjualan', $this->purchaseOrderOptions());
    }

    private function finishedBatchOptions()
    {
        return Schema::hasTable('finished_batches') ? FinishedBatch::with(['grade', 'area'])->where('remaining_weight_kg', '>', 0)
            ->where('remaining_packages', '>', 0)->whereHas('run', fn ($q) => $q->hasReadyOutputs())
            ->whereHas('area', fn ($q) => $q->where('is_active', true)->where('usage', 'finished'))
            ->get()->map(fn ($b) => [
            'id' => $b->id, 'inventory_id' => $b->inventory_id, 'quality_grade_id' => $b->quality_grade_id, 'label' => $b->area->name.' / '.$b->code.' / '.$b->remaining_weight_kg.' kg / '.$b->remaining_packages.' kemasan',
            'area_name' => $b->area->name, 'weight_kg' => (float) $b->remaining_weight_kg, 'packages' => (int) $b->remaining_packages,
        ])->values() : collect();
    }

    public function finishedBatches()
    {
        return $this->successResponse('Batch barang jadi tersedia', $this->finishedBatchOptions());
    }

    public function data(Request $request)
    {
        $request->validate(['view' => 'nullable|in:products']);
        $data = $request->input('view') === 'products' ? $this->service->getAllSales() : $this->service->getSaleNotes();

        return $this->successResponse('Data penjualan berhasil diambil', $data);
    }

    public function store(StoreSaleRequest $request)
    {
        try {
            if ($request->has('items')) {
                abort_unless($request->user()->can('manajemen-penjualan.create'), 403);
                $sales = $this->service->createCart($request->validated());
                return $this->successResponse('Penjualan berhasil dicatat', [
                    'number' => $sales[0]->sale_group_number, 'item_count' => count($sales),
                ]);
            }
            $this->service->createSale($request->validated());

            return $this->successResponse('Penjualan berhasil dicatat');
        } catch (ValidationException $e) {
            throw $e;
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
