<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\{ChartOfAccount, HarvestTransaction};
use App\Services\Finance\HarvestFinanceService;
use Illuminate\Http\Request;

class HarvestFinanceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => 'nullable|in:waiting,unpaid,partial,paid,legacy'])['status'] ?? null;
        $query = HarvestTransaction::with(['farmer:id,name', 'receipt:id,harvest_transaction_id,status'])->orderByDesc('harvest_date')->orderByDesc('id');
        if ($status === 'legacy') $query->where('separate_payment', false);
        if ($status && $status !== 'legacy') {
            $query->where('separate_payment', true);
            if ($status === 'waiting') $query->whereNull('priced_at');
            else {
                $query->whereNotNull('priced_at');
                if ($status === 'paid') $query->whereRaw('cash_payout + debt_deduction >= total_harvest_value');
                else {
                    $query->whereRaw('cash_payout + debt_deduction < total_harvest_value');
                    $status === 'partial' ? $query->whereRaw('cash_payout + debt_deduction > 0') : $query->whereRaw('cash_payout + debt_deduction = 0');
                }
            }
        }
        return view('pages.finance.harvest.index', ['harvests' => $query->paginate(20)->withQueryString(), 'status' => $status]);
    }

    public function show(HarvestTransaction $harvest)
    {
        $harvest->load(['farmer', 'receipt.lines.grade', 'receipt.lines.area', 'payments.cashAccount']);
        $cashAccounts = ChartOfAccount::where('is_active', true)->where('category', 'Harta')->where('account_group', 'Kas dan Bank')->where('normal_balance', 'Debit')->orderBy('code')->get();
        return view('pages.finance.harvest.show', compact('harvest', 'cashAccounts'));
    }

    public function price(Request $request, HarvestTransaction $harvest, HarvestFinanceService $service)
    {
        $data = $request->validate([
            'confirm' => 'required|accepted', 'purchase_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'price_per_kg' => 'required|integer|min:1|max:1000000000',
            'sale_deduction_type' => 'required|in:none,percentage,nominal,weight',
            'sale_deduction_value' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999999',
        ]);
        $service->price($harvest, $data, $request->user());
        return back()->with('success', 'Nilai pembelian dan utang kepada petani dicatat. Harga dikunci; pembayaran dapat dilakukan terpisah.');
    }

    public function pay(Request $request, HarvestTransaction $harvest, HarvestFinanceService $service)
    {
        $data = $request->validate([
            'submission_key' => 'required|uuid', 'confirm' => 'required|accepted',
            'payment_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'amount' => 'required|integer|min:0|max:9999999999999',
            'debt_deduction' => 'required|integer|min:0|max:9999999999999',
            'cash_account_id' => 'nullable|integer|exists:chart_of_accounts,id',
            'notes' => 'nullable|string|max:5000',
        ]);
        $service->pay($harvest, $data, $request->user());
        return back()->with('success', 'Pembayaran / potongan pinjaman dan jurnal berhasil dicatat.');
    }
}
