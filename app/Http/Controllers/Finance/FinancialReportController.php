<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancialReportService;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function __construct(private FinancialReportService $reports) {}
    public function balanceSheet(Request $request) { return $this->render($request, 'balance'); }
    public function profitLoss(Request $request) { return $this->render($request, 'profit'); }
    private function render(Request $request, string $type) {
        $data = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'report_month' => 'required_with:report_year|nullable|integer|between:1,12',
            'report_year' => 'required_with:report_month|nullable|integer|between:1000,9999',
        ]);
        $month = isset($data['report_month'], $data['report_year'])
            ? sprintf('%04d-%02d', $data['report_year'], $data['report_month'])
            : ($data['month'] ?? now('Asia/Jakarta')->format('Y-m'));
        return view('pages.finance.reports.index', ['type' => $type, 'month' => $month, 'report' => $this->reports->month($month)]);
    }
}
