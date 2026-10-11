<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreHarvestTransactionRequest;
use App\Http\Requests\Transaction\UpdateHarvestTransactionRequest;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\WarehouseArea;
use App\Services\Master\FarmerService;
use App\Services\Transaction\HarvestTransactionService;
use App\Traits\JsonResponser;

class HarvestTransactionController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected HarvestTransactionService $harvestService,
        protected FarmerService $farmerService
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'batch_code', 'label' => 'Kode Batch', 'sortable' => true],
            ['key' => 'harvest_date', 'label' => 'Tanggal Panen', 'render' => 'longDate', 'sortable' => true],
            ['key' => 'farmer.name', 'label' => 'Petani', 'sortable' => true],
            ['key' => 'total_weight', 'label' => 'Berat Referensi (Kg)', 'render' => 'total_weight', 'sortable' => true, 'align' => 'right'],
            ['key' => 'warehouse_weight', 'label' => 'Berat Gudang (Kg)', 'render' => 'warehouse_weight', 'sortable' => true, 'align' => 'right'],
            ['key' => 'receipt_mode', 'label' => 'Penerimaan Fisik', 'render' => 'receipt'],
            ['key' => 'sack_count', 'label' => 'Karung', 'sortable' => true, 'align' => 'right'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->physicalData();
        $farmers = $this->farmerService->getFarmerOptions();
        $inventories = Inventory::where('stock_stage', 'material')->get(['id', 'item_name']);
        $areas = WarehouseArea::query()->where('is_active', true)->where('usage', 'receiving')->get()->map(fn ($area) => [
            'id' => $area->id, 'name' => $area->code.' - '.$area->name, 'inventory_id' => $area->inventory_id,
        ]);

        return view('pages.transaction.harvest.index', compact('data', 'columns', 'farmers', 'inventories', 'areas'));
    }

    public function data()
    {
        $data = $this->physicalData();

        return $this->successResponse('Data panen berhasil diambil', $data);
    }

    public function store(StoreHarvestTransactionRequest $request)
    {
        $this->harvestService->createAndReceive($request->validated(), $request->user());

        return $this->successResponse('Barang panen diterima. Stok dan batch dicatat; penetapan harga dan pembayaran diproses oleh keuangan.');
    }

    public function update(UpdateHarvestTransactionRequest $request, HarvestTransaction $manajemen_panen)
    {
        $this->harvestService->updateHarvest($manajemen_panen, $request->validated());

        return $this->successResponse('Transaksi panen berhasil diperbarui');
    }

    public function destroy(HarvestTransaction $manajemen_panen)
    {
        $this->harvestService->deleteHarvest($manajemen_panen);

        return $this->successResponse('Transaksi panen berhasil dihapus');
    }

    private function physicalData()
    {
        return HarvestTransaction::with(['farmer:id,name', 'receipt:id,harvest_transaction_id,status'])
            ->orderByDesc('harvest_date')->orderByDesc('id')
            ->get(['id', 'farmer_id', 'batch_code', 'harvest_date', 'location', 'total_weight', 'sack_count', 'extra_per_sack', 'warehouse_weight', 'receipt_mode', 'is_received', 'notes'])
            ->map(fn ($harvest) => $harvest->setAppends([])->toArray());
    }
}
