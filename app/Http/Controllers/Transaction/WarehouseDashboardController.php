<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\FinishedBatch;
use App\Models\GoodsReceipt;
use App\Models\MaterialBatch;
use App\Models\ProductionRun;
use App\Models\WarehouseArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WarehouseDashboardController extends Controller
{
    public const STAGES = ['incoming' => 'Barang Datang', 'process' => 'WIP', 'wip' => 'WIP', 'press' => 'Press + Timbang + Packing', 'finished' => 'Barang Jadi'];

    public function index()
    {
        $confirmed = fn ($query) => $query->whereHas('line.receipt', fn ($q) => $q->where('status', 'confirmed'));
        $areas = WarehouseArea::with(['inventory', 'grades'])
            ->where('is_active', true)
            ->withCount(['batches as batch_count' => $confirmed])
            ->withSum(['batches as remaining_sacks' => $confirmed], 'remaining_sacks')
            ->withSum(['batches as received_weight' => $confirmed], 'incoming_weight_kg')
            ->orderBy('usage')->orderBy('id')->get();
        $productionReady = Schema::hasTable('production_runs');
        $hasRemainingWeight = Schema::hasColumn('material_batches', 'remaining_weight_kg');
        if ($hasRemainingWeight) {
            $areas->loadSum(['batches as remaining_weight' => $confirmed], 'remaining_weight_kg');
        }
        $areaLabels = $this->areaLabels();
        $wipByArea = [];
        $trackedWip = Schema::hasTable('wip_transfers');
        if ($productionReady) {
            $runs = ProductionRun::with('inputs.batch.line')
                ->when($trackedWip, fn ($query) => $query->with('transfers'))
                ->where('work_type', '!=', 'Stok Awal')->get();
            foreach ($runs as $run) {
                foreach ($run->inputs->groupBy(fn ($input) => $input->batch->line->warehouse_area_id) as $sourceId => $inputs) {
                    $row = $wipByArea[$sourceId] ?? ['sacks' => 0, 'weight' => 0, 'completed_sacks' => 0, 'output_weight' => 0, 'shrinkage' => 0];
                    if ($run->wip_mode) {
                        $row['sacks'] += $run->remaining_wip_sacks;
                        $row['weight'] += $run->status === 'packed' ? 0 : $run->input_reference_weight_kg - $run->transfers->sum('reference_weight_kg');
                        $row['completed_sacks'] += $run->transfers->sum('source_sacks');
                        $row['output_weight'] += $run->transfers->sum('output_weight_kg');
                        $row['shrinkage'] += $run->transfers->sum('reference_weight_kg') - $run->transfers->sum('output_weight_kg');
                    } elseif (in_array($run->status, ['processing', 'wip'])) {
                        $row['sacks'] += $inputs->sum('sack_count');
                        $row['weight'] += $inputs->sum('reference_weight_kg');
                    }
                    $wipByArea[$sourceId] = $row;
                }
            }
        }
        foreach ($areas as $area) {
            if ($productionReady && $area->usage === 'processing') {
                $area->process_count = ProductionRun::where('warehouse_area_id', $area->id)->whereIn('status', ['processing', 'wip'])->count();
                $area->process_sacks = ProductionRun::where('warehouse_area_id', $area->id)->whereIn('status', ['processing', 'wip'])->sum('input_sacks');
            } elseif ($productionReady && $area->usage === 'finished') {
                $area->finished_weight = FinishedBatch::where('warehouse_area_id', $area->id)->sum('remaining_weight_kg');
                $area->finished_packages = FinishedBatch::where('warehouse_area_id', $area->id)->sum('remaining_packages');
            }
        }

        return view('pages.transaction.warehouse.dashboard', [
            'areas' => $areas,
            'areaLabels' => $areaLabels,
            'wipByArea' => $wipByArea,
            'wipSacks' => array_sum(array_column($wipByArea, 'sacks')),
            'stages' => ['incoming' => 'Penerimaan', 'process' => 'WIP', 'finished' => 'Bahan Siap Jual'], 'batchCount' => MaterialBatch::whereHas('line.receipt', fn ($q) => $q->where('status', 'confirmed'))->count(),
            'weight' => MaterialBatch::sum('incoming_weight_kg'), 'sacks' => MaterialBatch::sum('remaining_sacks'),
            'drafts' => GoodsReceipt::where('status', 'draft')->count(),
            'productionReady' => $productionReady,
            'remainingWeight' => $hasRemainingWeight ? MaterialBatch::whereHas('line.receipt', fn ($q) => $q->where('status', 'confirmed'))->sum('remaining_weight_kg') : null,
            'finishedWeight' => $productionReady ? FinishedBatch::sum('remaining_weight_kg') : 0,
            'finishedPackages' => $productionReady ? FinishedBatch::sum('remaining_packages') : 0,
            'processedSacks' => $productionReady ? ProductionRun::sum('input_sacks') : 0,
            'packedWeight' => $productionReady ? FinishedBatch::whereHas('run', fn ($q) => $q->where('work_type', '!=', 'Stok Awal'))->sum('weight_kg') : 0,
            'packedPackages' => $productionReady ? FinishedBatch::whereHas('run', fn ($q) => $q->where('work_type', '!=', 'Stok Awal'))->sum('package_count') : 0,
            'stageCounts' => [
                'incoming' => MaterialBatch::where('remaining_sacks', '>', 0)->count(),
                'process' => $productionReady ? ProductionRun::whereIn('status', ['processing', 'wip'])->where('work_type', '!=', 'Stok Awal')->count() : 0,
                'wip' => $productionReady ? ProductionRun::where('status', 'wip')->count() : 0,
                'press' => $productionReady ? ProductionRun::where('status', 'wip')->count() : 0,
                'finished' => $productionReady ? FinishedBatch::where('remaining_weight_kg', '>', 0)->count() : 0,
            ],
        ]);
    }

    public function stage(string $stage, Request $request)
    {
        abort_unless(array_key_exists($stage, self::STAGES), 404);
        $filters = $request->validate(['area_id' => 'nullable|integer|exists:warehouse_areas,id']);
        $area = isset($filters['area_id']) ? WarehouseArea::with(['inventory', 'grades'])->findOrFail($filters['area_id']) : null;

        return view('pages.transaction.warehouse.stage', [
            'areaLabels' => $this->areaLabels(),
            'area' => $area,
            'stage' => $stage, 'title' => self::STAGES[$stage],
            'runs' => Schema::hasTable('production_runs') && in_array($stage, ['process', 'wip', 'press']) ? ProductionRun::with(['area', 'inputs.batch.line.area'])
                ->when(Schema::hasTable('wip_transfers'), fn ($query) => $query->with('transfers'))
                ->whereIn('status', $stage === 'process' ? ['processing', 'wip'] : ['wip'])->when($area, fn ($q) => $q->whereHas('inputs.batch.line', fn ($line) => $line->where('warehouse_area_id', $area->id)))->latest('id')->get() : collect(),
            'finished' => Schema::hasTable('finished_batches') && $stage === 'finished' ? FinishedBatch::with(['run', 'grade', 'area'])
                ->where('remaining_weight_kg', '>', 0)->when($area, fn ($q) => $q->where('warehouse_area_id', $area->id))->latest('id')->get() : collect(),
            'batches' => $stage === 'incoming' ? MaterialBatch::with(['line.receipt.farmer', 'line.grade', 'line.area'])
                ->whereHas('line.receipt', fn ($q) => $q->where('status', 'confirmed'))
                ->when($area, fn ($q) => $q->whereHas('line', fn ($line) => $line->where('warehouse_area_id', $area->id)))
                ->latest('id')->get() : collect(),
        ]);
    }

    public function area(WarehouseArea $area, Request $request)
    {
        $request->merge(['area_id' => $area->id]);
        $stage = ['receiving' => 'incoming', 'processing' => 'process', 'finished' => 'finished'][$area->usage];

        return $this->stage($stage, $request);
    }

    private function areaLabels(): array
    {
        return WarehouseArea::whereIn('usage', ['receiving', 'finished'])
            ->where('is_active', true)->pluck('name', 'id')->all();
    }
}
