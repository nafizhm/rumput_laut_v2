<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['category' => ['nullable', Rule::in(array_keys(ChartOfAccount::CATEGORIES))], 'q' => 'nullable|string|max:150']);
        $accounts = ChartOfAccount::query()
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')
                ->orWhere('account_group', 'like', '%'.$search.'%')))
            ->orderBy('code')->get();
        return view('pages.finance.coa.index', [
            'accounts' => $accounts, 'categories' => ChartOfAccount::CATEGORIES,
            'counts' => ChartOfAccount::selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category'),
        ]);
    }

    public function create()
    {
        return $this->form(new ChartOfAccount(['is_active' => true, 'normal_balance' => 'Debit']));
    }

    public function edit(ChartOfAccount $coa)
    {
        return $this->form($coa);
    }

    private function form(ChartOfAccount $account)
    {
        return view('pages.finance.coa.form', ['account' => $account, 'categories' => ChartOfAccount::CATEGORIES]);
    }

    public function store(Request $request)
    {
        ChartOfAccount::create($this->validated($request));
        return redirect()->route('coa.index')->with('success', 'Akun COA berhasil ditambahkan.');
    }

    public function update(Request $request, ChartOfAccount $coa)
    {
        $data = $this->validated($request, $coa);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $coa) {
            $coa = ChartOfAccount::lockForUpdate()->findOrFail($coa->id);
            foreach (['code', 'category', 'account_group', 'normal_balance'] as $field) {
                if ($data[$field] !== $coa->$field && \App\Models\JournalLine::where('chart_of_account_id', $coa->id)->whereHas('entry', fn ($q) => $q->where('status', 'posted'))->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([$field => 'Kode dan klasifikasi akun yang sudah dipakai jurnal Posted tidak dapat diganti. Buat akun baru bila perlu.']);
                }
            }
            $coa->update($data);
        });
        return redirect()->route('coa.index')->with('success', 'Akun COA berhasil diperbarui.');
    }

    private function validated(Request $request, ?ChartOfAccount $account = null): array
    {
        return $request->validate([
            'code' => ['required', 'regex:/^[1-5][0-9]{4}$/', Rule::unique('chart_of_accounts', 'code')->ignore($account?->id),
                function ($attribute, $value, $fail) use ($request) {
                    $prefix = ChartOfAccount::CATEGORIES[$request->input('category')] ?? null;
                    if ($prefix && ! str_starts_with($value, $prefix)) {
                        $fail('Awalan kode akun harus sesuai kategori: '.$prefix.'xxxx.');
                    }
                }],
            'name' => 'required|string|max:150',
            'category' => ['required', Rule::in(array_keys(ChartOfAccount::CATEGORIES))],
            'account_group' => 'required|string|max:100',
            'normal_balance' => ['required', Rule::in(['Debit', 'Kredit'])],
            'description' => 'nullable|string|max:2000',
            'is_active' => 'required|boolean',
        ]);
    }
}
