<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MaterialBatch;
use App\Models\ProductionRun;
use App\Models\WarehouseArea;
use App\Services\Transaction\ProductionService;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function __construct(private ProductionService $service) {}

    public function index(Request $request)
    {
        $filters = $request->validate(['status' => 'nullable|in:processing,wip,packed']);

        return view('pages.transaction.production.index', [
            'status' => $filters['status'] ?? '',
            'runs' => ProductionRun::with('area')
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
            'areas' => WarehouseArea::where('usage', 'processing')->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['started_date' => 'required|date_format:Y-m-d', 'warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
            'pic' => 'required|string|max:255', 'work_type' => 'required|string|max:255', 'notes' => 'nullable|string|max:2000',
            'inputs' => 'required|array|min:1|max:50',
            'inputs.*.material_batch_id' => 'required|integer|distinct|exists:material_batches,id', 'inputs.*.sack_count' => 'required|integer|min:1|max:1000000', 'confirm' => 'required|accepted']);
        $run = $this->service->start($data + ['warehouse_only' => true], $request->user());

        return redirect()->route('produksi.show', $run)->with('success', 'Proses dimulai. Karung bahan dialokasikan dan stok bahan diperbarui.');
    }

    public function show(Request $request, ProductionRun $produksi)
    {
        return view($request->header('X-Warehouse-Detail') === 'modal' ? 'pages.transaction.production._detail' : 'pages.transaction.production.show', ['run' => $produksi->load(['inputs.batch.line.receipt.farmer', 'outputs.grade', 'outputs.area', 'journals']),
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
        $data = $request->validate(['packed_date' => 'required|date_format:Y-m-d', 'packing_cost' => $produksi->warehouse_only ? 'prohibited' : 'required|numeric|min:0|max:9999999999|decimal:0,2',
            'outputs' => 'required|array|min:1|max:50', 'outputs.*.warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
            'outputs.*.quality_grade_id' => 'nullable|integer|exists:quality_grades,id', 'outputs.*.weight_kg' => 'required|numeric|min:0.01|max:999999999.99|decimal:0,2',
            'outputs.*.package_count' => 'required|integer|min:1|max:1000000', 'confirm' => 'required|accepted']);
        $this->service->pack($produksi, $data);

        return back()->with('success', 'Hasil packing dikonfirmasi. Batch barang jadi dan stok siap jual dicatat.');
    }
}
