<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreFarmerRequest;
use App\Http\Requests\Master\UpdateFarmerRequest;
use App\Models\Farmer;
use App\Services\Master\FarmerService;
use App\Traits\JsonResponser;

class FarmerController extends Controller
{
    use JsonResponser;

    protected FarmerService $farmerService;

    public function __construct(FarmerService $farmerService)
    {
        $this->farmerService = $farmerService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Nama Petani', 'sortable' => true],
            ['key' => 'phone', 'label' => 'No. Telepon', 'sortable' => true],
            ['key' => 'address', 'label' => 'Alamat'],
            ['key' => 'current_debt', 'label' => 'Hutang Saat Ini', 'render' => 'currency', 'sortable' => true],
            ['key' => 'action', 'label' => 'Aksi', 'render' => 'action'],
        ];

        $data = $this->farmerService->getAllFarmers();

        $ledgerColumns = [
            ['key' => 'created_at', 'label' => 'Tanggal', 'width' => '120px', 'render' => 'ledgerDate'],
            ['key' => 'reference_type', 'label' => 'Referensi', 'render' => 'reference'],
            ['key' => 'type', 'label' => 'Tipe', 'render' => 'typeBadge'],  
            ['key' => 'amount', 'label' => 'Jumlah', 'render' => 'ledgerAmount'],
            ['key' => 'balance_after', 'label' => 'Saldo Akhir', 'render' => 'ledgerBalance'],
        ];

        return view('pages.master.farmer.index', compact('data', 'columns', 'ledgerColumns'));
    }

    /**
     * Get data for the data table.
     */
    public function data()
    {
        $data = $this->farmerService->getAllFarmers();

        return $this->successResponse('Data petani berhasil diambil', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFarmerRequest $request)
    {
        $this->farmerService->createFarmer($request->validated());

        return $this->successResponse('Petani berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $farmer = Farmer::findOrFail($id);

        return $this->successResponse('Data petani berhasil diambil', $farmer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFarmerRequest $request, Farmer $manajemen_petani)
    {
        $this->farmerService->updateFarmer($manajemen_petani, $request->validated());

        return $this->successResponse('Petani berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Farmer $manajemen_petani)
    {
        $this->farmerService->deleteFarmer($manajemen_petani);

        return $this->successResponse('Petani berhasil dihapus');
    }

    public function ledgers($id)
    {
        $data = $this->farmerService->getFarmerLedgers($id);

        return $this->successResponse('Data mutasi berhasil diambil', $data);
    }
}
