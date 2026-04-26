<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreFactoryRequest;
use App\Http\Requests\Master\UpdateFactoryRequest;
use App\Services\Master\FactoryService;
use App\Traits\JsonResponser;

class FactoryController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected FactoryService $service
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Nama Pabrik', 'sortable' => true],
            ['key' => 'phone', 'label' => 'Nomor Telepon', 'sortable' => true],
            ['key' => 'address', 'label' => 'Alamat', 'sortable' => true],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->service->getAllFactories();

        return view('pages.master.factory.index', compact('data', 'columns'));
    }

    public function data()
    {
        $data = $this->service->getAllFactories();
        return $this->successResponse('Data pabrik berhasil diambil', $data);
    }

    public function store(StoreFactoryRequest $request)
    {
        $this->service->store($request->validated());
        return $this->successResponse('Pabrik berhasil ditambahkan');
    }

    public function update(UpdateFactoryRequest $request, $id)
    {
        $this->service->update($id, $request->validated());
        return $this->successResponse('Pabrik berhasil diperbarui');
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return $this->successResponse('Pabrik berhasil dihapus');
    }
}
