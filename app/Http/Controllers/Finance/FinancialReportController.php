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
        $data = $request->validate(['month' => 'nullable|date_format:Y-m']);
        $month = $data['month'] ?? now('Asia/Jakarta')->format('Y-m');
        return view('pages.finance.reports.index', ['type' => $type, 'month' => $month, 'report' => $this->reports->month($month)]);
    }
}
