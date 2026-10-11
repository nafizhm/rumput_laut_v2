<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\FinishedBatch;
use App\Models\GoodsReceiptLine;
use App\Models\Inventory;
use App\Models\ProductionRun;
use App\Models\WarehouseArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WarehouseAreaController extends Controller
{
    public function index(Request $request)
    {
        $usage = $request->route('usage') ?? $request->validate(['usage' => 'nullable|in:receiving,processing,finished'])['usage'] ?? null;

        return view('pages.master.warehouse-area.index', ['usage' => $usage, 'areas' => WarehouseArea::with('inventory')->when($usage, fn ($q) => $q->where('usage', $usage))->orderBy('code')->get()]);
    }

    public function create(Request $request)
    {
        $data = $request->validate(['usage' => 'nullable|in:receiving,processing,finished']);
        $usage = $data['usage'] ?? 'receiving';

        return $this->form(new WarehouseArea(['usage' => $usage, 'is_active' => true, 'inventory_id' => Inventory::where('stock_stage', $usage === 'finished' ? 'finished' : 'material')->value('id')]));
    }

    public function edit(WarehouseArea $area_gudang)
    {
        return $this->form($area_gudang);
    }

    private function form(WarehouseArea $area)
    {
        return view('pages.master.warehouse-area.form', [
            'area' => $area, 'inventories' => Inventory::orderBy('item_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->save($request);

        return redirect()->route('area-gudang.index', ['usage' => $request->input('usage')])->with('success', 'Area gudang ditambahkan.');
    }

    public function update(Request $request, WarehouseArea $area_gudang)
    {
        $this->save($request, $area_gudang);

        return redirect()->route('area-gudang.index', ['usage' => $request->input('usage')])->with('success', 'Area gudang diperbarui.');
    }

    private function save(Request $request, ?WarehouseArea $area = null): void
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('warehouse_areas')->ignore($area?->id)],
            'name' => 'required|string|max:100', 'inventory_id' => 'required|integer|exists:inventories,id',
            'usage' => ['required', Rule::in(['receiving', 'processing', 'finished'])],
            'description' => 'nullable|string|max:2000', 'is_active' => 'required|boolean',
        ]);
        DB::transaction(function () use ($data, $area) {
            $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);
            if ($data['usage'] === 'receiving' && $inventory->stock_stage !== 'material') {
                throw ValidationException::withMessages(['inventory_id' => 'Area bahan masuk harus berada di gudang bahan, bukan saldo lama atau barang jadi.']);
            }
            if ($data['usage'] === 'finished' && $inventory->stock_stage !== 'finished') {
                throw ValidationException::withMessages(['inventory_id' => 'Area barang jadi harus berada di gudang barang jadi.']);
            }
            if ($area) {
                $area = WarehouseArea::lockForUpdate()->findOrFail($area->id);
                $used = GoodsReceiptLine::where('warehouse_area_id', $area->id)->exists();
                if (Schema::hasTable('production_runs')) {
                    $used = $used || ProductionRun::where('warehouse_area_id', $area->id)->exists() || FinishedBatch::where('warehouse_area_id', $area->id)->exists();
                }
                if ($used
                    && ((int) $area->inventory_id !== (int) $data['inventory_id'] || $area->usage !== $data['usage'])) {
                    throw ValidationException::withMessages(['inventory_id' => 'Gudang/fungsi area yang sudah dipakai penerimaan tidak dapat diganti.']);
                }
            } else {
                $area = new WarehouseArea;
            }
            $area->fill($data)->save();
        });
    }
}
