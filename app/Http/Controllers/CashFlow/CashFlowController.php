<?php

namespace App\Http\Controllers\CashFlow;

use App\Http\Controllers\Controller;
use App\Services\Finance\CashFlowService;
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

        $summary = app(CashFlowService::class)->summary($startDate, $endDate, $type);

        return response()->json($summary);
    }
}
