<?php

namespace App\Services\Finance;

use App\Models\{AccountingSetting, JournalEntry, JournalLine};
use Illuminate\Support\Carbon;

class FinancialReportService
{
    public function month(string $month, ?Carbon $since = null): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month, 'Asia/Jakarta');
        $end = $start->copy()->endOfMonth();
        if ($since !== null) $start = $since->copy()->startOfDay();
        $setting = AccountingSetting::findOrFail(1);
        $ready = $setting->start_date !== null && $setting->start_date->lte($end);
        $result = ['start' => $start, 'end' => $end, 'start_date' => $setting->start_date, 'ready' => $ready];
        if (! $ready) return $result;

        $balance = []; $period = []; $priorProfit = 0; $yearProfit = 0;
        $yearStart = $start->copy()->startOfYear()->format('Y-m-d');
        $lines = JournalLine::query()->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')->where('journal_entries.entry_date', '<=', $end->format('Y-m-d'))
            ->select('journal_lines.*', 'journal_entries.entry_date as ledger_date')->orderBy('journal_lines.id')->cursor();
        foreach ($lines as $line) {
            $value = in_array($line->category, ['Utang', 'Modal', 'Pendapatan'])
                ? $line->credit_cents - $line->debit_cents : $line->debit_cents - $line->credit_cents;
            $row = ['code' => $line->account_code, 'name' => $line->account_name, 'category' => $line->category, 'group' => $line->account_group, 'amount' => 0];
            $key = $line->chart_of_account_id;
            $balance[$key] ??= $row; $balance[$key]['amount'] += $value;
            if (in_array($line->category, ['Pendapatan', 'Biaya'])) {
                $profitValue = $line->category === 'Pendapatan' ? $value : -$value;
                if ($line->ledger_date < $yearStart) $priorProfit += $profitValue; else $yearProfit += $profitValue;
                if ($line->ledger_date >= $start->format('Y-m-d')) {
                    $period[$key] ??= $row; $period[$key]['amount'] += $value;
                }
            }
        }
        $balance = collect($balance)->sortBy('code')->values();
        $period = collect($period)->sortBy('code')->values();
        $assets = $balance->where('category', 'Harta')->values();
        $liabilities = $balance->where('category', 'Utang')->values();
        $equity = $balance->where('category', 'Modal')->values();
        $assetTotal = $assets->sum('amount'); $liabilityTotal = $liabilities->sum('amount');
        $equityTotal = $equity->sum('amount') + $priorProfit + $yearProfit;

        $sections = ['sales' => [], 'other_income' => [], 'cogs' => [], 'operating' => [], 'finance' => [], 'other_expense' => [], 'tax' => []];
        foreach ($period as $row) {
            $code = $row['code'];
            $section = $row['category'] === 'Pendapatan'
                ? (str_starts_with($code, '41') ? 'sales' : 'other_income')
                : (str_starts_with($code, '511') ? 'cogs'
                    : (str_starts_with($code, '53') ? 'finance'
                        : ($code === '54101' ? 'tax' : (str_starts_with($code, '54') ? 'other_expense' : 'operating'))));
            $sections[$section][] = $row;
        }
        $totals = array_map(fn ($rows) => array_sum(array_column($rows, 'amount')), $sections);
        $gross = $totals['sales'] - $totals['cogs'];
        $operatingProfit = $gross - $totals['operating'];
        $beforeTax = $operatingProfit + $totals['other_income'] - $totals['finance'] - $totals['other_expense'];
        return $result + [
            'assets' => $assets, 'liabilities' => $liabilities, 'equity' => $equity,
            'asset_total' => $assetTotal, 'liability_total' => $liabilityTotal, 'equity_total' => $equityTotal,
            'prior_profit' => $priorProfit, 'year_profit' => $yearProfit,
            'difference' => $assetTotal - $liabilityTotal - $equityTotal,
            'sections' => $sections, 'totals' => $totals, 'gross_profit' => $gross,
            'operating_profit' => $operatingProfit, 'before_tax' => $beforeTax, 'net_profit' => $beforeTax - $totals['tax'],
            'missing_cogs' => $totals['sales'] > 0 && empty($sections['cogs']),
            'posted_count' => JournalEntry::where('status', 'posted')->whereBetween('entry_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->count(),
        ];
    }
}
