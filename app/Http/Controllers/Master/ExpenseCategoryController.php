<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreExpenseCategoryRequest;
use App\Http\Requests\Master\UpdateExpenseCategoryRequest;
use App\Services\Master\ExpenseCategoryService;
use App\Traits\JsonResponser;

class ExpenseCategoryController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected ExpenseCategoryService $service
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Nama Kategori', 'sortable' => true],
            ['key' => 'is_active', 'label' => 'Status', 'render' => 'statusBadge'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->service->getAllCategories();

        return view('pages.master.expense-category.index', compact('data', 'columns'));
    }

    public function data()
    {
        $data = $this->service->getAllCategories();

        return $this->successResponse('Data kategori berhasil diambil', $data);
    }

    public function store(StoreExpenseCategoryRequest $request)
    {
        $this->service->createCategory($request->validated());

        return $this->successResponse('Kategori berhasil ditambahkan');
    }

    public function update(UpdateExpenseCategoryRequest $request, $id)
    {
        $this->service->updateCategory($id, $request->validated());

        return $this->successResponse('Kategori berhasil diperbarui');
    }

    public function destroy($id)
    {
        $this->service->deleteCategory($id);

        return $this->successResponse('Kategori berhasil dihapus');
    }
}
