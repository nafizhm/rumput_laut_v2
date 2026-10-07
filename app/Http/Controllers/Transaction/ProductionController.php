<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MaterialBatch;
use App\Models\ProductionRun;
use App\Models\QualityGrade;
use App\Models\WarehouseArea;
use App\Services\Transaction\ProductionService;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function __construct(private ProductionService $service) {}

    public function create()
    {
        return view('pages.transaction.production.create', [
            'batches' => MaterialBatch::with(['line.receipt.farmer', 'line.grade', 'line.area'])->where('remaining_sacks', '>', 0)->where('is_costed', true)->get(),
            'areas' => WarehouseArea::where('usage', 'processing')->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['started_date' => 'required|date_format:Y-m-d', 'warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
            'pic' => 'required|string|max:255', 'work_type' => 'required|string|max:255', 'notes' => 'nullable|string|max:2000',
            'processing_cost' => 'required|numeric|min:0|max:9999999999|decimal:0,2', 'inputs' => 'required|array|min:1|max:50',
            'inputs.*.material_batch_id' => 'required|integer|distinct|exists:material_batches,id', 'inputs.*.sack_count' => 'required|integer|min:1|max:1000000', 'confirm' => 'required|accepted']);
        $run = $this->service->start($data, $request->user());

        return redirect()->route('produksi.show', $run)->with('success', 'Proses dimulai. Karung bahan dialokasikan dan biaya masuk WIP.');
    }

    public function show(ProductionRun $produksi)
    {
        return view('pages.transaction.production.show', ['run' => $produksi->load(['inputs.batch.line.receipt.farmer', 'outputs.grade', 'outputs.area', 'journals']),
            'areas' => WarehouseArea::with('grades')->where('usage', 'finished')->where('is_active', true)->get(),
            'grades' => QualityGrade::where('is_active', true)->orderBy('sort_order')->get()]);
    }

    public function complete(Request $request, ProductionRun $produksi)
    {
        $data = $request->validate(['completed_date' => 'required|date_format:Y-m-d', 'confirm' => 'required|accepted']);
        $this->service->complete($produksi, $data['completed_date']);

        return back()->with('success', 'Proses selesai dan masuk WIP, menunggu press/timbang/packing.');
    }

    public function pack(Request $request, ProductionRun $produksi)
    {
        $data = $request->validate(['packed_date' => 'required|date_format:Y-m-d', 'packing_cost' => 'required|numeric|min:0|max:9999999999|decimal:0,2',
            'outputs' => 'required|array|min:1|max:50', 'outputs.*.warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
            'outputs.*.quality_grade_id' => 'required|integer|exists:quality_grades,id', 'outputs.*.weight_kg' => 'required|numeric|min:0.01|max:999999999.99|decimal:0,2',
            'outputs.*.package_count' => 'required|integer|min:1|max:1000000', 'confirm' => 'required|accepted']);
        $this->service->pack($produksi, $data);

        return back()->with('success','Hasil packing dikonfirmasi. Batch barang jadi dan nilai persediaan siap dijual.');
    }
}
