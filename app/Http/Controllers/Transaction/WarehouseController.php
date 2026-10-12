<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\AdjustmentRequest;
use App\Http\Requests\Transaction\ReceiveStockRequest;
use App\Http\Requests\Transaction\ShrinkageRequest;
use App\Http\Requests\Transaction\TransferStockRequest;
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

    public function index(Request $request)
    {
        return app(WarehouseDashboardController::class)->index();
    }

    public function movements(Request $request) {
        return $this->renderWarehousePage('Riwayat Mutasi Gudang', $request->integer('inventory_id'), true);
    }

    public function wet()
    {
        $inventory = Inventory::where('item_name', 'Gudang Basah')->firstOrFail();
        return $this->renderWarehousePage('Gudang Basah', $inventory->id, false);
    }

    public function dry()
    {
        $inventory = Inventory::where('item_name', 'Gudang Kering')->firstOrFail();
        return $this->renderWarehousePage('Gudang Kering', $inventory->id, false);
    }

    public function inventory(Inventory $inventory)
    {
        return $this->renderWarehousePage('Histori ' . $inventory->item_name, $inventory->id, false);
    }

    public function data(Request $request)
    {
        $data = $this->service->getAllMovements($request->integer('inventory_id'));
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

    public function transfer(TransferStockRequest $request)
    {
        try {
            app(\App\Services\Transaction\AreaStockTransferService::class)->transfer($request->validated());
            return $this->successResponse('Transfer stok berhasil dicatat');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function adjustment(AdjustmentRequest $request)
    {
        try {
            app(\App\Services\Transaction\AreaStockAdjustmentService::class)->save($request->validated());
            return $this->successResponse('Penyesuaian stok berhasil dicatat');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function transferData()
    {
        return $this->successResponse('Data area berhasil diambil', app(\App\Services\Transaction\AreaStockTransferService::class)->options());
    }

    public function adjustmentData()
    {
        return $this->successResponse('Data area berhasil diambil', app(\App\Services\Transaction\AreaStockAdjustmentService::class)->areas());
    }

    public function stockData()
    {
        $inventories = Inventory::orderBy('item_name')->get()->map(fn($i) => [
            'value' => $i->id,
            'label' => $i->item_name,
            'current_stock' => (float) $i->current_stock_kg,
            'current_sacks' => (int) $i->current_sack_count,
            'stock_stage' => $i->stock_stage,
        ]);
        return $this->successResponse('Data stok berhasil diambil', $inventories);
    }

    protected function renderWarehousePage(string $pageTitle, ?int $inventoryId, bool $showActions)
    {
        $columns = [
            ['key' => 'movement_date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
            ['key' => 'type', 'label' => 'Jenis', 'render' => 'type_badge', 'align' => 'center'],
            ['key' => 'inventory.item_name', 'label' => 'Barang'],
            ['key' => 'direction', 'label' => 'Arah', 'render' => 'direction_badge', 'align' => 'center'],
            ['key' => 'weight_kg', 'label' => 'Berat (Kg)', 'render' => 'weight', 'sortable' => true, 'align' => 'right'],
            ['key' => 'balance_after_kg', 'label' => 'Saldo Akhir (Kg)', 'render' => 'balance_after_kg', 'align' => 'right'],
            ['key' => 'description', 'label' => 'Keterangan'],
        ];

        $data = $this->service->getAllMovements($inventoryId);
        $inventories = Inventory::orderBy('item_name')->get()->map(fn($i) => [
            'value' => $i->id,
            'label' => $i->item_name,
            'current_stock' => (float) $i->current_stock_kg,
            'current_sacks' => (int) $i->current_sack_count
        ]);
        $selectedInventory = $inventoryId ? Inventory::find($inventoryId) : null;

        return view('pages.transaction.warehouse.index', compact(
            'data',
            'columns',
            'inventories',
            'pageTitle',
            'inventoryId',
            'selectedInventory',
            'showActions'
        ));
    }
}
