<?php

namespace App\Http\Controllers;

use App\Services\Finance\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BusinessProcessController extends Controller
{
    public function __construct(private FinancialReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'month_number' => 'required_with:year|nullable|integer|between:1,12',
            'year' => 'required_with:month_number|nullable|integer|between:1900,2100',
        ]);
        $month = isset($filters['month_number'], $filters['year'])
            ? sprintf('%04d-%02d', $filters['year'], $filters['month_number'])
            : ($filters['month'] ?? now('Asia/Jakarta')->format('Y-m'));
        $selected = Carbon::createFromFormat('!Y-m', $month, 'Asia/Jakarta');
        $report = $this->reports->month($month);
        $lifetime = $report['ready'] ? $this->reports->month($month, $report['start_date']) : $report;
        $trend = collect(range(11, 0))->map(function (int $offset) use ($selected) {
            $period = $selected->copy()->subMonths($offset);
            $data = $this->reports->month($period->format('Y-m'));
            return [
                'month' => $period->format('Y-m'),
                'title' => $period->locale('id')->translatedFormat('F Y'),
                'ready' => $data['ready'],
                'sections' => $data['sections'] ?? [],
                'totals' => $data['totals'] ?? [],
                'gross_profit' => $data['gross_profit'] ?? 0,
                'missing_cogs' => $data['missing_cogs'] ?? false,
                'label' => $period->locale('id')->translatedFormat('M y'),
                'sales' => $data['totals']['sales'] ?? 0,
                'expense' => ($data['totals']['cogs'] ?? 0) + ($data['totals']['operating'] ?? 0) + ($data['totals']['finance'] ?? 0) + ($data['totals']['other_expense'] ?? 0) + ($data['totals']['tax'] ?? 0),
                'profit' => $data['net_profit'] ?? 0,
            ];
        });

        return view('pages.business-process.index', compact('month', 'report', 'trend', 'lifetime'));
    }
}
