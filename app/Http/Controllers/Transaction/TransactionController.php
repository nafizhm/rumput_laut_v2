<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\ChartOfAccount;
use App\Services\Transaction\TransactionService;
use App\Traits\JsonResponser;

class TransactionController extends Controller
{
    use JsonResponser;

    public function __construct(
        protected TransactionService $service
    ) {}

    public function index()
    {
        $columns = [
            ['key' => 'date', 'label' => 'Tanggal', 'render' => 'date', 'sortable' => true],
            ['key' => 'type_label', 'label' => 'Jenis', 'render' => 'typeBadge', 'sortable' => true],
            ['key' => 'account.name', 'label' => 'Akun COA', 'render' => 'account'],
            ['key' => 'cash_account.name', 'label' => 'Kas / Bank', 'render' => 'cashAccount'],
            ['key' => 'amount', 'label' => 'Jumlah', 'render' => 'amount', 'sortable' => true, 'align' => 'right'],
            ['key' => 'description', 'label' => 'Keterangan'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->service->getAll();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get()->map(fn ($account) => ['value' => $account->id, 'label' => $account->code.' - '.$account->name]);
        $cashAccounts = ChartOfAccount::where('is_active', true)->where('category', 'Harta')->where('account_group', 'Kas dan Bank')->where('normal_balance', 'Debit')->orderBy('code')->get()->map(fn ($account) => ['value' => $account->id, 'label' => $account->code.' - '.$account->name]);

        return view('pages.transaction.transaction.index', compact('data', 'columns', 'accounts', 'cashAccounts'));
    }

    public function data()
    {
        $data = $this->service->getAll();

        return $this->successResponse('Data transaksi berhasil diambil', $data);
    }

    public function store(StoreTransactionRequest $request)
    {
        $this->service->create($request->validated());

        return $this->successResponse('Transaksi berhasil ditambahkan');
    }

    public function update(UpdateTransactionRequest $request, $id)
    {
        $this->service->update($id, $request->validated());

        return $this->successResponse('Transaksi berhasil diperbarui');
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return $this->successResponse('Transaksi berhasil dihapus');
    }
}
