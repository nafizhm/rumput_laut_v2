<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\WarehouseArea;
use App\Services\Transaction\OpeningStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OpeningStockController extends Controller
{
    public function index()
    {
        return view('pages.transaction.warehouse.opening', [
            'areas' => WarehouseArea::with('inventory')->where('is_active', true)->whereIn('usage', ['receiving', 'finished'])->orderBy('usage')->orderBy('id')->get(),
            'entries' => DB::table('opening_stock_entries as e')->join('warehouse_areas as a', 'a.id', '=', 'e.warehouse_area_id')
                ->select('e.*', 'a.name as area_name', 'a.usage')->orderByDesc('e.id')->paginate(20),
        ]);
    }

    public function store(Request $request, OpeningStockService $service)
    {
        $data = $request->validate([
            'token' => 'required|uuid|unique:opening_stock_entries,token',
            'stock_date' => 'required|date_format:Y-m-d|before_or_equal:'.now('Asia/Jakarta')->format('Y-m-d'),
            'usage' => 'required|in:receiving,finished',
            'items' => 'required|array|min:1|max:1000',
            'items.*.warehouse_area_id' => ['required', 'integer', 'distinct', Rule::exists('warehouse_areas', 'id')->where('is_active', true)->where('usage', $request->input('usage'))],
            'items.*.weight_kg' => 'nullable|numeric|min:0|max:9999999999.99|decimal:0,2',
            'items.*.package_count' => 'nullable|integer|min:0|max:1000000',
            'notes' => 'nullable|string|max:2000',
        ]);
        $service->save($data, $request->user());

        return redirect()->route('stok-awal.index')->with('success', 'Stok awal disimpan dan saldo gudang bertambah.');
    }
}
