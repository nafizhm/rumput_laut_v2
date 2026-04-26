<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreInventoryRequest;
use App\Http\Requests\Master\UpdateInventoryRequest;
use App\Services\Master\InventoryService;
use App\Traits\JsonResponser;

class InventoryController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected InventoryService $service
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'item_name', 'label' => 'Nama Barang', 'sortable' => true],
            ['key' => 'current_stock_kg', 'label' => 'Stok Saat Ini (Kg)', 'sortable' => true],
            ['key' => 'current_sack_count', 'label' => 'Jumlah Karung', 'sortable' => true],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->service->getAllInventories();

        return view('pages.master.inventory.index', compact('data', 'columns'));
    }

    public function data()
    {
        $data = $this->service->getAllInventories();
        return $this->successResponse('Data inventori berhasil diambil', $data);
    }

    public function store(StoreInventoryRequest $request)
    {
        $this->service->store($request->validated());
        return $this->successResponse('Barang berhasil ditambahkan ke daftar inventori');
    }

    public function update(UpdateInventoryRequest $request, $id)
    {
        $this->service->update($id, $request->validated());
        return $this->successResponse('Nama barang berhasil diperbarui');
    }

    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return $this->successResponse('Barang berhasil dihapus dari daftar inventori');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
