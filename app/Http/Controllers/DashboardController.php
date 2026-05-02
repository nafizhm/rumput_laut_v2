<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Farmer;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\Loan;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('pages.dashboard.index');
    }

    public function stats(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $salesQuery = SalesTransaction::with('factory')->whereBetween('sale_date', [$startDate, $endDate]);
        $harvestQuery = HarvestTransaction::with('farmer')->whereBetween('harvest_date', [$startDate, $endDate]);
        $expenseQuery = Expense::with('category')->whereBetween('expense_date', [$startDate, $endDate]);
        $loanQuery = Loan::with('farmer')->whereBetween('loan_date', [$startDate, $endDate]);
        $movementQuery = StockMovement::whereBetween('movement_date', [$startDate, $endDate]);

        $totalSales = (clone $salesQuery)->sum('total_sale_value');
        $totalHarvest = (clone $harvestQuery)->sum('total_harvest_value');
        $totalExpenses = (clone $expenseQuery)->sum('amount');

        $netProfit = $totalSales - $totalHarvest - $totalExpenses;
        $farmerDebt = Farmer::sum('current_debt');
        $warehouseStock = Inventory::sum('current_stock_kg');

        $recentInvoices = (clone $salesQuery)
            ->orderBy('sale_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($sale) {
                return [
                    'invoice_number' => $sale->invoice_number,
                    'sale_date' => $sale->sale_date->format('d M Y'),
                    'factory_name' => $sale->factory->name,
                    'total_sale_value' => $sale->total_sale_value,
                    'status' => $sale->payment_status_label,
                    'payment_status' => $sale->payment_status,
                ];
            });

        $weeklyHarvestCount = (clone $harvestQuery)->count();
        $weeklyHarvestValue = (clone $harvestQuery)->sum('total_harvest_value');
        $recentHarvests = (clone $harvestQuery)
            ->orderBy('harvest_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($h) => [
                'name' => $h->farmer->name,
                'weight' => $h->warehouse_weight,
                'value' => $h->total_harvest_value,
                'date' => $h->harvest_date->format('d/m'),
            ]);

        $weeklyExpenseCount = (clone $expenseQuery)->count();
        $weeklyExpenseValue = (clone $expenseQuery)->sum('amount');
        $recentExpenses = (clone $expenseQuery)
            ->orderBy('expense_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'name' => $e->category->name,
                'amount' => $e->amount,
                'date' => $e->expense_date->format('d/m'),
            ]);

        $weeklySalesCount = (clone $salesQuery)->count();
        $weeklySalesValue = (clone $salesQuery)->sum('total_sale_value');
        $recentSales = (clone $salesQuery)
            ->orderBy('sale_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'name' => $s->factory->name,
                'weight' => $s->total_weight_kg,
                'value' => $s->total_sale_value,
                'date' => $s->sale_date->format('d/m'),
            ]);

        $totalShrinkage = (clone $movementQuery)->where('type', 'shrinkage')->sum('weight_kg');
        $totalReceived = (clone $movementQuery)->where('type', 'in')->sum('weight_kg');
        $shrinkageRate = $totalReceived > 0 ? ($totalShrinkage / $totalReceived) * 100 : 0;
        $totalDiscrepancy = (clone $movementQuery)->sum('discrepancy_kg');

        return response()->json([
            'netProfit' => $netProfit,
            'totalSales' => $totalSales,
            'farmerDebt' => $farmerDebt,
            'warehouseStock' => $warehouseStock,
            'shrinkageRate' => round($shrinkageRate, 2),
            'logisticsDiscrepancy' => round($totalDiscrepancy, 2),
            'recentInvoices' => $recentInvoices,
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
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $months = [];
        $incomeData = [];
        $expenseData = [];
        $cursor = $startDate->copy()->startOfMonth();
        $lastMonth = $endDate->copy()->startOfMonth();

        while ($cursor <= $lastMonth) {
            $months[] = $cursor->translatedFormat('M Y');
            $incomeData[] = SalesTransaction::whereBetween('sale_date', [$cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()])
                ->sum('total_sale_value');
            $expenseData[] = HarvestTransaction::whereBetween('harvest_date', [$cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()])
                ->sum('total_harvest_value')
                + Expense::whereBetween('expense_date', [$cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()])
                ->sum('amount');
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
