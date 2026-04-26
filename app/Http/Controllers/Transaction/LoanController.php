<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreLoanRequest;
use App\Http\Requests\Transaction\UpdateLoanRequest;
use App\Models\Loan;
use App\Services\Master\FarmerService;
use App\Services\Transaction\LoanService;
use App\Traits\JsonResponser;

class LoanController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected LoanService $loanService,
        protected FarmerService $farmerService
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'loan_date', 'label' => 'Tanggal Pinjam', 'render' => 'longDate', 'sortable' => true],
            ['key' => 'farmer.name', 'label' => 'Petani', 'sortable' => true],
            ['key' => 'amount', 'label' => 'Jumlah', 'render' => 'currency', 'sortable' => true],
            ['key' => 'description', 'label' => 'Keterangan'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->loanService->getAllLoans();
        $farmers = $this->farmerService->getFarmerOptions();

        return view('pages.transaction.loan.index', compact('data', 'columns', 'farmers'));
    }

    public function data()
    {
        $data = $this->loanService->getAllLoans();

        return $this->successResponse('Data pinjaman berhasil diambil', $data);
    }

    public function store(StoreLoanRequest $request)
    {
        $this->loanService->createLoan($request->validated());

        return $this->successResponse('Pinjaman berhasil ditambahkan');
    }

    public function update(UpdateLoanRequest $request, Loan $manajemen_pinjaman)
    {
        $this->loanService->updateLoan($manajemen_pinjaman, $request->validated());

        return $this->successResponse('Pinjaman berhasil diperbarui');
    }

    public function destroy(Loan $manajemen_pinjaman)
    {
        $this->loanService->deleteLoan($manajemen_pinjaman);

        return $this->successResponse('Pinjaman berhasil dihapus');
    }
}
