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
        $filters = $request->validate([
            'type' => 'nullable|in:all,income,expense',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);
        $type = $filters['type'] ?? 'all';
        $startDate = ! empty($filters['start_date'])
            ? Carbon::parse($filters['start_date'], 'Asia/Jakarta')->startOfDay()
            : Carbon::now('Asia/Jakarta')->startOfMonth();
        $endDate = ! empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'], 'Asia/Jakarta')->endOfDay()
            : Carbon::now('Asia/Jakarta')->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $summary = app(CashFlowService::class)->summary($startDate, $endDate, $type);

        return response()->json($summary);
    }
}
