<?php

namespace App\Http\Controllers\CashFlow;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\HarvestTransaction;
use App\Models\Loan;
use App\Models\SalesTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function index()
    {
        return view('pages.cash-flow.index');
    }

    public function data(Request $request)
    {
        $type = $request->query('type', 'all');
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $summary = [];

        $salesQuery = SalesTransaction::with('factory')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where('total_sale_value', '>', 0);
        $cashInValue = $salesQuery->sum('total_sale_value');
        
        if ($type === 'all' || $type === 'income') {
            $summary[] = [
                'id' => 1,
                'category' => 'Penjualan Rumput Laut',
                'type' => 'Cash In',
                'amount' => $cashInValue,
                'amount_formatted' => 'Rp ' . number_format($cashInValue, 0, ',', '.'),
                'details' => $salesQuery->get()->map(fn($t) => [
                    'id' => $t->id,
                    'date' => $t->sale_date->format('d/m/Y'),
                    'description' => 'Penjualan ke ' . ($t->factory->name ?? 'Pabrik') . ' (#' . $t->invoice_number . ')',
                    'amount' => 'Rp ' . number_format($t->total_sale_value, 0, ',', '.')
                ])
            ];
        }

        $harvestQuery = HarvestTransaction::with('farmer')->whereBetween('harvest_date', [$startDate, $endDate])
            ->where('cash_payout', '>', 0);
        $harvestCashOut = $harvestQuery->sum('cash_payout');

        if ($type === 'all' || $type === 'expense') {
            $summary[] = [
                'id' => 2,
                'category' => 'Pembayaran Tunai Panen',
                'type' => 'Cash Out',
                'amount' => $harvestCashOut,
                'amount_formatted' => 'Rp ' . number_format($harvestCashOut, 0, ',', '.'),
                'details' => $harvestQuery->get()->map(fn($t) => [
                    'id' => $t->id,
                    'date' => $t->harvest_date->format('d/m/Y'),
                    'description' => 'Pembayaran panen kepada ' . ($t->farmer->name ?? 'Petani'),
                    'amount' => 'Rp ' . number_format($t->cash_payout, 0, ',', '.')
                ])
            ];

            $loanQuery = Loan::with('farmer')->whereBetween('loan_date', [$startDate, $endDate])->where('amount', '>', 0);
            $loanCashOut = $loanQuery->sum('amount');
            $summary[] = [
                'id' => 3,
                'category' => 'Pinjaman Petani (Modal)',
                'type' => 'Cash Out',
                'amount' => $loanCashOut,
                'amount_formatted' => 'Rp ' . number_format($loanCashOut, 0, ',', '.'),
                'details' => $loanQuery->get()->map(fn($t) => [
                    'id' => $t->id,
                    'date' => $t->loan_date->format('d/m/Y'),
                    'description' => 'Pinjaman modal ke ' . ($t->farmer->name ?? 'Petani'),
                    'amount' => 'Rp ' . number_format($t->amount, 0, ',', '.')
                ])
            ];

            $expenseQuery = Expense::with('category')
                ->whereBetween('expense_date', [$startDate, $endDate])
                ->where('amount', '>', 0);
            $expenseCashOut = $expenseQuery->sum('amount');
            $summary[] = [
                'id' => 4,
                'category' => 'Biaya Operasional',
                'type' => 'Cash Out',
                'amount' => $expenseCashOut,
                'amount_formatted' => 'Rp ' . number_format($expenseCashOut, 0, ',', '.'),
                'details' => $expenseQuery->get()->map(fn($t) => [
                    'id' => $t->id,
                    'date' => $t->expense_date->format('d/m/Y'),
                    'description' => ($t->category->name ?? 'Pengeluaran') . ': ' . $t->description,
                    'amount' => 'Rp ' . number_format($t->amount, 0, ',', '.')
                ])
            ];
        }

        return response()->json($summary);
    }
}
