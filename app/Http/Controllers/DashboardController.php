<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Farmer;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\SalesTransaction;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        return view('pages.dashboard.index');
    }

    public function stats()
    {
        Carbon::setLocale('id');
        $totalSales = SalesTransaction::sum('total_sale_value');
        $totalHarvest = HarvestTransaction::sum('total_harvest_value');
        $totalExpenses = Expense::sum('amount');
        
        $netProfit = $totalSales - $totalHarvest - $totalExpenses;
        $farmerDebt = Farmer::sum('current_debt');
        $warehouseStock = Inventory::sum('current_stock_kg');

        $recentInvoices = SalesTransaction::with('factory')
            ->orderBy('sale_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($sale) {
                return [
                    'invoice_number' => $sale->invoice_number,
                    'sale_date' => $sale->sale_date->format('d M Y'),
                    'factory_name' => $sale->factory->name,
                    'total_sale_value' => $sale->total_sale_value,
                    'status' => 'Complete', // For now hardcoded as complete
                ];
            });

        $activities = StockMovement::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($movement) {
                return [
                    'type' => $movement->type,
                    'direction' => $movement->direction,
                    'weight' => $movement->weight_kg,
                    'time' => $movement->created_at->diffForHumans(),
                    'description' => $this->getActivityDescription($movement),
                ];
            });

        $startOfWeek = Carbon::now()->startOfWeek();

        $weeklyHarvestCount = HarvestTransaction::where('harvest_date', '>=', $startOfWeek)->count();
        $weeklyHarvestValue = HarvestTransaction::where('harvest_date', '>=', $startOfWeek)->sum('total_harvest_value');
        $recentHarvests = HarvestTransaction::with('farmer')
            ->orderBy('harvest_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($h) => [
                'name' => $h->farmer->name,
                'weight' => $h->net_weight_kg,
                'value' => $h->total_harvest_value,
                'date' => $h->harvest_date->format('d/m'),
            ]);

        $weeklyExpenseCount = Expense::where('expense_date', '>=', $startOfWeek)->count();
        $weeklyExpenseValue = Expense::where('expense_date', '>=', $startOfWeek)->sum('amount');
        $recentExpenses = Expense::with('category')
            ->orderBy('expense_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'name' => $e->category->name,
                'amount' => $e->amount,
                'date' => $e->expense_date->format('d/m'),
            ]);

        $weeklySalesCount = SalesTransaction::where('sale_date', '>=', $startOfWeek)->count();
        $weeklySalesValue = SalesTransaction::where('sale_date', '>=', $startOfWeek)->sum('total_sale_value');
        $recentSales = SalesTransaction::with('factory')
            ->orderBy('sale_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'name' => $s->factory->name,
                'weight' => $s->total_weight_kg,
                'value' => $s->total_sale_value,
                'date' => $s->sale_date->format('d/m'),
            ]);

        return response()->json([
            'netProfit' => $netProfit,
            'totalSales' => $totalSales,
            'farmerDebt' => $farmerDebt,
            'warehouseStock' => $warehouseStock,
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
            'recentLoans' => \App\Models\Loan::with('farmer')
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

    private function getActivityDescription($movement)
    {
        switch ($movement->type) {
            case 'in':
                return "Penerimaan barang masuk sebesar " . number_format($movement->weight_kg, 2) . " kg";
            case 'out':
                return "Pengiriman barang keluar sebesar " . number_format($movement->weight_kg, 2) . " kg";
            case 'shrinkage':
                return "Penyusutan stok sebesar " . number_format($movement->weight_kg, 2) . " kg";
            case 'adjustment':
                return "Penyesuaian stok sebesar " . number_format($movement->weight_kg, 2) . " kg";
            default:
                return "Aktivitas stok lainnya";
        }
    }

    public function chartData()
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');
            $months[] = $monthName;

            // Income: Sales
            $income = SalesTransaction::whereMonth('sale_date', $date->month)
                ->whereYear('sale_date', $date->year)
                ->sum('total_sale_value');
            $incomeData[] = $income;

            // Expenses: Harvest + General Expenses
            $harvestExp = HarvestTransaction::whereMonth('harvest_date', $date->month)
                ->whereYear('harvest_date', $date->year)
                ->sum('total_harvest_value');
            
            $generalExp = Expense::whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');

            $expenseData[] = $harvestExp + $generalExp;
        }

        // Summary Stats
        $totalShrinkage = StockMovement::where('type', 'shrinkage')->sum('weight_kg');
        $totalReceived = StockMovement::where('type', 'in')->sum('weight_kg');
        $shrinkageRate = $totalReceived > 0 ? ($totalShrinkage / $totalReceived) * 100 : 0;
        
        $totalDiscrepancy = StockMovement::sum('discrepancy_kg');

        return response()->json([
            'months' => $months,
            'income' => $incomeData,
            'expenses' => $expenseData,
            'shrinkageRate' => round($shrinkageRate, 2),
            'logisticsDiscrepancy' => round($totalDiscrepancy, 2),
        ]);
    }
}
