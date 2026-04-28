<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ReceiveStockRequest;
use App\Http\Requests\Transaction\ShrinkageRequest;
use App\Http\Requests\Transaction\AdjustmentRequest;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Services\Transaction\WarehouseService;
use App\Traits\JsonResponser;
use Exception;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected WarehouseService $service
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'movement_date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
            ['key' => 'type', 'label' => 'Jenis', 'render' => 'type_badge'],
            ['key' => 'inventory.item_name', 'label' => 'Barang'],
            ['key' => 'direction', 'label' => 'Arah', 'render' => 'direction_badge'],
            ['key' => 'weight_kg', 'label' => 'Berat (Kg)', 'sortable' => true],
            ['key' => 'balance_after_kg', 'label' => 'Saldo Akhir (Kg)'],
            ['key' => 'description', 'label' => 'Keterangan'],
        ];

        $data = $this->service->getAllMovements();
        $inventories = Inventory::orderBy('item_name')->get()->map(fn($i) => ['value' => $i->id, 'label' => $i->item_name]);
        
        return view('pages.transaction.warehouse.index', compact('data', 'columns', 'inventories'));
    }

    public function data()
    {
        $data = $this->service->getAllMovements();
        return $this->successResponse('Data mutasi gudang berhasil diambil', $data);
    }

    public function receive(ReceiveStockRequest $request)
    {
        try {
            $this->service->receive($request->validated());
            return $this->successResponse('Penerimaan stok berhasil dicatat');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function shrinkage(ShrinkageRequest $request)
    {
        try {
            $this->service->shrinkage($request->validated());
            return $this->successResponse('Penyusutan stok berhasil dicatat');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function adjustment(AdjustmentRequest $request)
    {
        try {
            $this->service->adjustment($request->validated());
            return $this->successResponse('Penyesuaian stok berhasil dicatat');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
