<?php

namespace App\Http\Controllers;

use App\Models\AccountingSetting;
use App\Models\Farmer;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\ProductionRun;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Services\Finance\CashFlowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        return view('pages.dashboard.index');
    }

    public function stats(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $salesQuery = SalesTransaction::with('factory')->whereBetween('sale_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        $harvestQuery = HarvestTransaction::with('farmer')->whereBetween('harvest_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        $expenseQuery = Transaction::with('category')->where('type', 'expense')->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        $loanQuery = Loan::with('farmer')->whereBetween('loan_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        $movementQuery = StockMovement::whereBetween('movement_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        $totalSales = (clone $salesQuery)->sum('total_sale_value');
        $totalHarvest = (clone $harvestQuery)->sum('total_harvest_value');
        $totalExpenses = (clone $expenseQuery)->sum('amount');

        $recognizedSales = (clone $movementQuery)->where('direction', 'out')->whereNotNull('sales_transaction_id')->with('salesTransaction')->get()
            ->sum(fn ($m) => $m->salesTransaction ? $m->salesTransaction->total_sale_value * $m->weight_kg / $m->salesTransaction->total_weight_kg : 0);
        $cogs = Schema::hasColumn('stock_movements', 'cost_cents')
            ? (clone $movementQuery)->whereNotNull('sales_transaction_id')->sum('cost_cents') / 100 : 0;
        $otherIncome = Transaction::where('type', 'income')->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])->sum('amount');
        $netProfit = $recognizedSales + $otherIncome - $cogs - $totalExpenses;
        $profitReady = true;
        if (Schema::hasTable('operational_events')) {
            $setting = AccountingSetting::findOrFail(1);
            $profitReady = (bool) $setting->start_date;
            $netProfit = JournalLine::query()->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->where('journal_entries.status', 'posted')->whereBetween('journal_entries.entry_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->whereIn('journal_lines.category', ['Pendapatan', 'Biaya'])->get(['journal_lines.debit_cents', 'journal_lines.credit_cents'])
                ->sum(fn ($l) => $l->credit_cents - $l->debit_cents) / 100;
        }
        $farmerDebt = Farmer::sum('current_debt');
        $warehouseStock = Inventory::sum('current_stock_kg');

        $recentInvoices = Invoice::with('sale.inventory')
            ->orderBy('invoice_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'invoice_date' => $invoice->invoice_date->format('d M Y'),
                    'customer_name' => $invoice->customer_name,
                    'total_amount' => $invoice->total_amount,
                    'status' => $invoice->payment_status_label,
                    'payment_status' => $invoice->payment_status,
                    'url' => route('invoice.print', $invoice),
                ];
            });

        $weeklyHarvestCount = (clone $harvestQuery)->count();
        $weeklyHarvestValue = (clone $harvestQuery)->sum('total_harvest_value');
        $recentHarvests = (clone $harvestQuery)
            ->orderBy('harvest_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($h) => [
                'name' => $h->farmer->name,
                'weight' => $h->warehouse_weight,
                'value' => $h->total_harvest_value,
                'date' => $h->harvest_date->format('d/m'),
            ]);

        $weeklyExpenseCount = (clone $expenseQuery)->count();
        $weeklyExpenseValue = (clone $expenseQuery)->sum('amount');
        $recentExpenses = (clone $expenseQuery)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'name' => $e->category?->name ?? 'Pengeluaran',
                'amount' => $e->amount,
                'date' => $e->date->format('d/m'),
            ]);

        $weeklySalesCount = (clone $salesQuery)->count();
        $weeklySalesValue = (clone $salesQuery)->sum('total_sale_value');
        $recentSales = (clone $salesQuery)
            ->orderBy('sale_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'name' => $s->factory->name,
                'weight' => $s->total_weight_kg,
                'value' => $s->total_sale_value,
                'date' => $s->sale_date->format('d/m'),
            ]);

        $totalShrinkage = (clone $movementQuery)->where('type', 'shrinkage')->sum('weight_kg');
        $totalReceived = (clone $movementQuery)->where('type', 'in')->sum('weight_kg');
        $shrinkageRate = $totalReceived > 0 ? ($totalShrinkage / $totalReceived) * 100 : 0;
        $totalDiscrepancy = (clone $movementQuery)->sum('discrepancy_kg');

        $production = ['count' => 0, 'processing' => 0, 'wip' => 0, 'packed' => 0, 'sacks' => 0, 'recent' => []];
        if (Schema::hasTable('production_runs')) {
            $runs = ProductionRun::whereBetween('started_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            $production['count'] = (clone $runs)->count();
            $production['sacks'] = (clone $runs)->sum('input_sacks');
            foreach (['processing', 'wip', 'packed'] as $status) {
                $production[$status] = (clone $runs)->where('status', $status)->count();
            }
            $production['recent'] = (clone $runs)->with('area')->orderByDesc('started_date')->orderByDesc('id')->limit(5)->get()
                ->map(fn ($run) => ['number' => $run->number, 'date' => $run->started_date->format('d/m/Y'),
                    'work_type' => $run->work_type, 'area' => $run->area->code, 'sacks' => $run->input_sacks,
                    'status' => $run->status, 'url' => route('produksi.show', $run)]);
        }

        return response()->json([
            'netProfit' => $netProfit,
            'profitReady' => $profitReady,
            'totalSales' => $totalSales,
            'farmerDebt' => $farmerDebt,
            'warehouseStock' => $warehouseStock,
            'shrinkageRate' => round($shrinkageRate, 2),
            'logisticsDiscrepancy' => round($totalDiscrepancy, 2),
            'recentInvoices' => $recentInvoices,
            'production' => $production,
            'weeklyHarvest' => [
                'count' => $weeklyHarvestCount,
                'value' => $weeklyHarvestValue,
                'recent' => $recentHarvests,
            ],
            'weeklyExpense' => [
                'count' => $weeklyExpenseCount,
                'value' => $weeklyExpenseValue,
                'recent' => $recentExpenses,
            ],
            'weeklySales' => [
                'count' => $weeklySalesCount,
                'value' => $weeklySalesValue,
                'recent' => $recentSales,
            ],
            'recentLoans' => (clone $loanQuery)
                ->orderBy('loan_date', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($loan) {
                    return [
                        'farmer_name' => $loan->farmer->name,
                        'amount' => $loan->amount,
                        'date' => $loan->loan_date->format('d M Y'),
                    ];
                }),
        ]);
    }

    public function chartData(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : Carbon::now()->subMonths(5)->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        $months = [];
        $incomeData = [];
        $expenseData = [];
        $cursor = $startDate->copy()->startOfMonth();
        $lastMonth = $endDate->copy()->startOfMonth();

        while ($cursor <= $lastMonth) {
            $months[] = $cursor->translatedFormat('M Y');
            $cash = app(CashFlowService::class)->summary($cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth(), 'all');
            $incomeData[] = collect($cash)->where('type', 'Cash In')->sum('amount');
            $expenseData[] = collect($cash)->where('type', 'Cash Out')->sum('amount');
            $cursor->addMonth();
        }

        return response()->json([
            'months' => $months,
            'income' => $incomeData,
            'expenses' => $expenseData,
        ]);
    }

    protected function resolveDateRange(Request $request): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }
}
