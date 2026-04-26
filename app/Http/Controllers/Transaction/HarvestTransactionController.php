<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreHarvestTransactionRequest;
use App\Http\Requests\Transaction\UpdateHarvestTransactionRequest;
use App\Models\HarvestTransaction;
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
            ['key' => 'net_weight_kg', 'label' => 'Berat Bersih (Kg)', 'sortable' => true],
            ['key' => 'total_harvest_value', 'label' => 'Total Nilai', 'render' => 'total_harvest_value', 'sortable' => true],
            ['key' => 'debt_deduction', 'label' => 'Potong Hutang', 'render' => 'debt_deduction', 'sortable' => true],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->harvestService->getAllHarvests();
        $farmers = $this->farmerService->getFarmerOptions();

        return view('pages.transaction.harvest.index', compact('data', 'columns', 'farmers'));
    }

    public function data()
    {
        $data = $this->harvestService->getAllHarvests();

        return $this->successResponse('Data panen berhasil diambil', $data);
    }

    public function store(StoreHarvestTransactionRequest $request)
    {
        $this->harvestService->createHarvest($request->validated());

        return $this->successResponse('Transaksi panen berhasil ditambahkan');
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
}
