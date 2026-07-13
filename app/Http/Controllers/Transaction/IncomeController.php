<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreIncomeRequest;
use App\Http\Requests\Transaction\UpdateIncomeRequest;
use App\Services\Transaction\IncomeService;
use App\Traits\JsonResponser;

class IncomeController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected IncomeService $service
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'income_date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
            ['key' => 'amount', 'label' => 'Jumlah', 'render' => 'amount', 'sortable' => true, 'align' => 'right'],
            ['key' => 'description', 'label' => 'Keterangan'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->service->getAllIncomes();

        return view('pages.transaction.income.index', compact('data', 'columns'));
    }

    public function data()
    {
        $data = $this->service->getAllIncomes();

        return $this->successResponse('Data pemasukan berhasil diambil', $data);
    }

    public function store(StoreIncomeRequest $request)
    {
        $this->service->createIncome($request->validated());

        return $this->successResponse('Pemasukan berhasil ditambahkan');
    }

    public function update(UpdateIncomeRequest $request, $id)
    {
        $this->service->updateIncome($id, $request->validated());

        return $this->successResponse('Pemasukan berhasil diperbarui');
    }

    public function destroy($id)
    {
        $this->service->deleteIncome($id);

        return $this->successResponse('Pemasukan berhasil dihapus');
    }
}
