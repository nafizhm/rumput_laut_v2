<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\Finance\AccountingMoney;
use App\Services\Finance\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class JournalController extends Controller
{
    public function __construct(private JournalService $service) {}

    public function index(Request $request)
    {
        $filters = $request->validate(['month' => 'nullable|date_format:Y-m', 'status' => ['nullable', Rule::in(['draft', 'posted'])]]);

        return $this->listing('general', $filters);
    }

    public function opening()
    {
        return $this->listing('opening', []);
    }

    public function startZero(Request $request)
    {
        $data = $request->validate(['entry_date' => 'required|date_format:Y-m-d', 'confirm' => 'required|accepted']);
        $entry = $this->service->startZero($data['entry_date'], $request->user());

        return redirect()->route('jurnal.show', $entry)->with('success', 'Pembukuan dimulai. Jurnal operasional yang sudah tersedia diposting sesuai tanggal.');
    }

    private function listing(string $kind, array $filters)
    {
        $entries = JournalEntry::with(['lines', 'reversal'])->whereIn('kind', $kind === 'opening' ? ['opening'] : ['opening', 'general', 'reversal', 'operational', 'operational-reversal'])
            ->when($filters['month'] ?? null, fn ($q, $month) => $q->whereBetween('entry_date', [$month.'-01', Carbon::parse($month.'-01')->endOfMonth()->format('Y-m-d')]))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->orderByDesc('entry_date')->orderByDesc('id')->get();

        $setting = AccountingSetting::findOrFail(1);
        $openingSummary = null;
        if ($kind === 'opening' && $setting->start_date) {
            $lines = JournalLine::whereHas('entry', fn ($q) => $q->where('status', 'posted')
                ->whereDate('entry_date', $setting->start_date->format('Y-m-d')))->get();
            $cash = $lines->where('category', 'Harta')->where('account_group', 'Kas dan Bank');
            $capital = $lines->where('account_code', '31101');
            $openingSummary = [
                'cash' => $cash->sum('debit_cents') - $cash->sum('credit_cents'),
                'capital' => $capital->sum('credit_cents') - $capital->sum('debit_cents'),
            ];
        }

        return view('pages.finance.journal.index', compact('entries', 'kind', 'setting', 'openingSummary'));
    }

    public function create(Request $request)
    {
        $kind = $request->routeIs('saldo-awal.create') ? 'opening' : 'general';
        abort_if($kind === 'opening' && AccountingSetting::findOrFail(1)->start_date !== null, 403, 'Saldo awal sudah diposting. Koreksi lewat jurnal penyesuaian.');

        return $this->form(new JournalEntry(['entry_date' => now('Asia/Jakarta')->format('Y-m-d'), 'kind' => $kind]));
    }

    public function edit(JournalEntry $jurnal)
    {
        abort_unless($jurnal->status === 'draft' && ! $jurnal->is_automatic, 403, 'Jurnal Posted dikunci.');

        return $this->form($jurnal->load('lines'));
    }

    private function form(JournalEntry $entry)
    {
        $accounts = ChartOfAccount::where('is_active', true)->where('code', '!=', '32102')
            ->when($entry->kind === 'opening', fn ($q) => $q->whereIn('category', ['Harta', 'Utang', 'Modal']))->orderBy('code')->get();
        $rows = $entry->exists ? $entry->lines->map(fn ($l) => [
            'chart_of_account_id' => $l->chart_of_account_id, 'debit' => AccountingMoney::decimal($l->debit_cents), 'credit' => AccountingMoney::decimal($l->credit_cents),
        ])->all() : [['chart_of_account_id' => '', 'debit' => '0', 'credit' => '0'], ['chart_of_account_id' => '', 'debit' => '0', 'credit' => '0']];

        return view('pages.finance.journal.form', compact('entry', 'accounts', 'rows'));
    }

    public function show(Request $request, JournalEntry $jurnal)
    {
        return view($request->boolean('modal') ? 'pages.finance.journal._detail' : 'pages.finance.journal.show', ['entry' => $jurnal->load(['lines', 'reversal', 'original'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['kind'] = $request->routeIs('saldo-awal.store') ? 'opening' : 'general';
        $entry = $this->service->save($data, $request->user());

        return redirect()->route('jurnal.show', $entry)->with('success', 'Draft jurnal disimpan. Belum masuk laporan.');
    }

    public function update(Request $request, JournalEntry $jurnal)
    {
        $entry = $this->service->save($this->validated($request, $jurnal), $request->user(), $jurnal);

        return redirect()->route('jurnal.show', $entry)->with('success', 'Draft jurnal diperbarui.');
    }

    public function destroy(JournalEntry $jurnal)
    {
        $kind = $jurnal->kind;
        $this->service->delete($jurnal);

        return redirect()->route($kind === 'opening' ? 'saldo-awal.index' : 'jurnal.index')->with('success', 'Draft jurnal dihapus.');
    }

    public function post(Request $request, JournalEntry $jurnal)
    {
        $request->validate(['confirm' => 'required|accepted']);
        $entry = $this->service->post($jurnal, $request->user());

        return redirect()->route('jurnal.show', $entry)->with('success', 'Jurnal diposting dan masuk laporan.');
    }

    public function reverse(Request $request, JournalEntry $jurnal)
    {
        $data = $request->validate(['entry_date' => 'required|date_format:Y-m-d', 'reason' => 'required|string|min:5|max:1000', 'confirm' => 'required|accepted']);
        $entry = $this->service->reverse($jurnal, $data['entry_date'], $data['reason'], $request->user());

        return redirect()->route('jurnal.show', $entry)->with('success', 'Jurnal pembalik diposting. Jurnal asal tetap tersimpan.');
    }

    private function validated(Request $request, ?JournalEntry $entry = null): array
    {
        return $request->validate([
            'entry_date' => 'required|date_format:Y-m-d', 'description' => 'required|string|min:5|max:2000',
            'source_reference' => ['nullable', 'string', 'max:150', Rule::unique('journal_entries')->ignore($entry?->id)],
            'lines' => 'required|array|min:2|max:100',
            'lines.*.chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'lines.*.debit' => ['nullable', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
            'lines.*.credit' => ['nullable', 'regex:/^\d{1,12}(?:\.\d{1,2})?$/'],
        ]);
    }
}
