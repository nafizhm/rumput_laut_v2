<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreExpenseRequest;
use App\Http\Requests\Transaction\UpdateExpenseRequest;
use App\Services\Master\ExpenseCategoryService;
use App\Services\Transaction\ExpenseService;
use App\Traits\JsonResponser;

class ExpenseController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected ExpenseService $service,
        protected ExpenseCategoryService $categoryService
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'expense_date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
            ['key' => 'category.name', 'label' => 'Kategori', 'sortable' => true],
            ['key' => 'amount', 'label' => 'Jumlah', 'render' => 'amount', 'sortable' => true],
            ['key' => 'description', 'label' => 'Keterangan'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->service->getAllExpenses();
        $categories = $this->categoryService->getCategoryOptions();

        return view('pages.transaction.expense.index', compact('data', 'columns', 'categories'));
    }

    public function data()
    {
        $data = $this->service->getAllExpenses();

        return $this->successResponse('Data pengeluaran berhasil diambil', $data);
    }

    public function store(StoreExpenseRequest $request)
    {
        $this->service->createExpense($request->validated());

        return $this->successResponse('Pengeluaran berhasil ditambahkan');
    }

    public function update(UpdateExpenseRequest $request, $id)
    {
        $this->service->updateExpense($id, $request->validated());

        return $this->successResponse('Pengeluaran berhasil diperbarui');
    }

    public function destroy($id)
    {
        $this->service->deleteExpense($id);

        return $this->successResponse('Pengeluaran berhasil dihapus');
    }
}
