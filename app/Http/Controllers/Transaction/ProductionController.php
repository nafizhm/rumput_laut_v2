<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MaterialBatch;
use App\Models\AreaActivity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\ProductionRun;
use App\Models\WarehouseArea;
use App\Services\Transaction\ProductionService;
use App\Services\Transaction\WipService;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function __construct(private ProductionService $service) {}

    public function index(Request $request)
    {
        $filters = $request->validate(['status' => 'nullable|in:processing,wip,packed', 'source_area_id' => 'nullable|integer|exists:warehouse_areas,id']);

        return view('pages.transaction.production.index', [
            'status' => $filters['status'] ?? '',
            'areaLabels' => $this->areaLabels(),
            'activities' => Schema::hasTable('area_activities') ? AreaActivity::with('area')->orderByDesc('activity_date')->orderByDesc('id')->paginate(20, ['*'], 'activities_page')->withQueryString() : null,
            'runs' => ProductionRun::with(['area', 'inputs.batch.line.area', 'outputs.area'])
                ->when(Schema::hasTable('wip_transfers'), fn ($query) => $query->with('transfers.outputs.area'))
                ->when($filters['source_area_id'] ?? null, fn ($query, $id) => $query->whereHas('inputs.batch.line', fn ($line) => $line->where('warehouse_area_id', $id)))
                ->where('work_type', '!=', 'Stok Awal')
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function edit(ProductionRun $produksi)
    {
        abort_unless($produksi->status === 'processing', 403, 'Hanya proses aktif yang dapat diedit.');

        return view('pages.transaction.production.edit', ['run' => $produksi]);
    }

    public function update(Request $request, ProductionRun $produksi)
    {
        $data = $request->validate([
            'pic' => 'required|string|max:255', 'work_type' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        $this->service->updateDetails($produksi, $data);

        return redirect()->route('produksi.show', $produksi)->with('success', 'Data proses diperbarui.');
    }

    public function create()
    {
        return view('pages.transaction.production.create', [
            'batches' => MaterialBatch::with(['line.receipt.farmer', 'line.grade', 'line.area'])
                ->where('remaining_sacks', '>', 0)->where('status', 'available')
                ->whereHas('line.receipt', fn ($query) => $query->where('status', 'confirmed'))->get(),
            'areas' => WarehouseArea::where('usage', 'receiving')->where('is_active', true)
                ->whereHas('inventory', fn ($q) => $q->where('stock_stage', 'material'))->orderBy('id')->get(),
            'finishedAreas' => WarehouseArea::where('usage', 'finished')->where('is_active', true)
                ->whereHas('inventory', fn ($q) => $q->where('stock_stage', 'finished'))->orderBy('id')->get(),
            'areaLabels' => $this->areaLabels(),
        ]);
    }

    public function createActivity()
    {
        return view('pages.transaction.production.activity', [
            'areas' => WarehouseArea::where('usage', 'receiving')->where('is_active', true)
                ->whereHas('inventory', fn ($query) => $query->where('stock_stage', 'material'))->orderBy('name')->get(),
        ]);
    }

    public function storeActivity(Request $request)
    {
        $data = $request->validate([
            'activity_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'warehouse_area_id' => ['required', 'integer', Rule::exists('warehouse_areas', 'id')->where('usage', 'receiving')->where('is_active', true)],
            'work_type' => 'required|string|max:255',
            'sack_count' => 'required|integer|min:1|max:1000000',
            'pic' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        $area = WarehouseArea::with('inventory')->findOrFail($data['warehouse_area_id']);
        abort_unless($area->inventory?->stock_stage === 'material', 422, 'Pilih area penerimaan bahan.');
        AreaActivity::create($data + ['created_by' => $request->user()->id, 'created_by_name' => $request->user()->name]);

        return redirect()->route('produksi.index')->with('success', 'Aktivitas area dicatat. Stok tetap di area semula.');
    }

    public function store(Request $request)
    {
        if ($request->has('source_area_id')) {
            $data = $request->validate([
                'token' => 'required|uuid',
                'date' => 'required|date_format:Y-m-d|before_or_equal:today',
                'source_area_id' => ['required', 'integer', Rule::exists('warehouse_areas', 'id')->where('usage', 'receiving')->where('is_active', true)],
                'sack_count' => 'required|integer|min:1|max:1000000',
                'work_type' => 'required|string|max:255', 'pic' => 'required|string|max:255',
                'notes' => 'nullable|string|max:2000', 'confirm' => 'required|accepted',
                'sources' => 'prohibited', 'inputs' => 'prohibited', 'outputs' => 'prohibited',
            ]);
            $run = app(WipService::class)->start($data, $request->user());
            return redirect()->route('produksi.show', $run)->with('success', 'Bahan dari area sumber dipindahkan ke WIP. Hasil jadi dicatat saat pemindahan ke area ready.');
        }
        if ($request->has('sources')) {
            $data = $request->validate([
                'date' => 'required|date_format:Y-m-d|before_or_equal:today', 'notes' => 'nullable|string|max:2000',
                'sources' => 'required|array|size:1',
                'sources.*.warehouse_area_id' => 'required|integer|distinct|exists:warehouse_areas,id',
                'sources.*.sack_count' => 'required|integer|min:1|max:1000000',
                'outputs' => 'required|array|min:1|max:50',
                'outputs.*.warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
                'outputs.*.weight_kg' => 'required|numeric|min:0.01|max:999999999.99|decimal:0,2',
                'outputs.*.package_count' => 'required|integer|min:1|max:1000000',
            ]);
            $this->service->recordDaily($data, $request->user());
            return redirect()->route('produksi.index')->with('success', 'Proses harian disimpan. Stok bahan berkurang dan hasil packing masuk stok siap jual.');
        }
        $data = $request->validate(['started_date' => 'required|date_format:Y-m-d', 'warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
            'pic' => 'required|string|max:255', 'work_type' => 'required|string|max:255', 'notes' => 'nullable|string|max:2000',
            'inputs' => 'required|array|min:1|max:50',
            'inputs.*.material_batch_id' => 'required|integer|distinct|exists:material_batches,id', 'inputs.*.sack_count' => 'required|integer|min:1|max:1000000', 'confirm' => 'required|accepted']);
        $run = $this->service->start($data + ['warehouse_only' => true], $request->user());

        return redirect()->route('produksi.show', $run)->with('success', 'Proses dimulai. Karung bahan dialokasikan dan stok bahan diperbarui.');
    }

    public function show(Request $request, ProductionRun $produksi)
    {
        if ($produksi->wip_mode) $produksi->load('sourceArea', 'transfers.outputs.area');
        return view($request->header('X-Warehouse-Detail') === 'modal' ? 'pages.transaction.production._detail' : 'pages.transaction.production.show', ['run' => $produksi->load(['inputs.batch.line.receipt.farmer', 'inputs.batch.line.area', 'outputs.grade', 'outputs.area', 'journals']),
            'areaLabels' => $this->areaLabels(),
            'areas' => WarehouseArea::query()->where('usage', 'finished')->where('is_active', true)->get(),
        ]);
    }

    public function complete(Request $request, ProductionRun $produksi)
    {
        $data = $request->validate(['completed_date' => 'required|date_format:Y-m-d', 'confirm' => 'required|accepted']);
        $this->service->complete($produksi, $data['completed_date']);

        return back()->with('success', 'Proses selesai dan masuk WIP, menunggu press/timbang/packing.');
    }

    public function pack(Request $request, ProductionRun $produksi)
    {
        if ($produksi->wip_mode) {
            $data = $request->validate([
                'token' => 'required|uuid', 'packed_date' => 'required|date_format:Y-m-d|before_or_equal:today',
                'source_sacks' => 'required|integer|min:1|max:1000000', 'notes' => 'nullable|string|max:2000',
                'outputs' => 'required|array|min:1|max:50',
                'outputs.*.warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
                'outputs.*.weight_kg' => 'required|numeric|min:0.01|max:999999999.99|decimal:0,2',
                'outputs.*.package_count' => 'required|integer|min:1|max:1000000', 'confirm' => 'required|accepted',
            ]);
            app(WipService::class)->transfer($produksi, $data, $request->user());
            return redirect()->route('produksi.show', $produksi)->with('success', 'Pemindahan WIP ke area jadi dicatat beserta hasil timbang dan selisih berat.');
        }
        $data = $request->validate(['packed_date' => 'required|date_format:Y-m-d', 'packing_cost' => $produksi->warehouse_only ? 'prohibited' : 'required|numeric|min:0|max:9999999999|decimal:0,2',
            'outputs' => 'required|array|min:1|max:50', 'outputs.*.warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
            'outputs.*.quality_grade_id' => 'nullable|integer|exists:quality_grades,id', 'outputs.*.weight_kg' => 'required|numeric|min:0.01|max:999999999.99|decimal:0,2',
            'outputs.*.package_count' => 'required|integer|min:1|max:1000000', 'confirm' => 'required|accepted']);
        $this->service->pack($produksi, $data);

        return back()->with('success', 'Hasil packing dikonfirmasi. Batch barang jadi dan stok siap jual dicatat.');
    }

    private function areaLabels(): array
    {
        return WarehouseArea::whereIn('usage', ['receiving', 'finished'])
            ->pluck('name', 'id')->all();
    }
}
