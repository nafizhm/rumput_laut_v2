<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\ChartOfAccount;
use App\Models\TransactionHistory;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Http\Request;
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

    public function history(Request $request)
    {
        $filters = $request->validate(['transaction_id' => 'nullable|integer|min:1']);
        $id = $filters['transaction_id'] ?? null;
        $histories = TransactionHistory::when($id, fn ($q) => $q->where('transaction_id', $id))
            ->orderByDesc('id')->paginate(25)->withQueryString();
        $journals = $id ? JournalEntry::with('lines')->where('source_type', (new Transaction)->getMorphClass())
            ->where('source_id', $id)->orderBy('id')->get() : collect();

        return view('pages.transaction.transaction.history', compact('histories', 'journals', 'id'));
    }

    public function destroy(Request $request, $id)
    {
        $data = $request->validate(['change_reason' => 'required|string|max:2000']);
        $this->service->delete($id, $data['change_reason']);

        return $this->successResponse('Transaksi berhasil dihapus');
    }
}
