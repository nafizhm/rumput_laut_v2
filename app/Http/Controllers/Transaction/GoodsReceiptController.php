<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\GoodsReceipt;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\WarehouseArea;
use App\Services\Transaction\GoodsReceiptService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GoodsReceiptController extends Controller
{
    public function __construct(private GoodsReceiptService $service) {}

    public function index(Request $request)
    {
        $filters = $request->validate(['farmer_id' => 'nullable|integer|exists:farmers,id']);
        $receipts = GoodsReceipt::with(['farmer', 'inventory', 'lines'])
            ->when($filters['farmer_id'] ?? null, fn ($q, $id) => $q->where('farmer_id', $id))->latest('id')->get();

        return view('pages.transaction.receiving.index', compact('receipts'));
    }

    public function create(Request $request)
    {
        $receipt = new GoodsReceipt(['received_date' => now('Asia/Jakarta')->format('Y-m-d')]);
        if ($request->filled('harvest_id')) {
            $harvest = HarvestTransaction::where('receipt_mode', 'separate')->where('is_received', false)->findOrFail($request->integer('harvest_id'));
            $receipt->harvest_transaction_id = $harvest->id;
            $receipt->farmer_id = $harvest->farmer_id;
        }

        return $this->form($receipt);
    }

    public function edit(GoodsReceipt $penerimaan_barang)
    {
        abort_unless($penerimaan_barang->status === 'draft', 403, 'Penerimaan dikunci.');

        return $this->form($penerimaan_barang->load('lines'));
    }

    private function form(GoodsReceipt $receipt)
    {
        return view('pages.transaction.receiving.form', [
            'receipt' => $receipt, 'farmers' => Farmer::orderBy('name')->get(),
            'harvests' => HarvestTransaction::with('farmer')->where('receipt_mode', 'separate')->where('is_received', false)->orderByDesc('id')->get(),
            'inventories' => Inventory::where('stock_stage', 'material')->get(),
            'areas' => WarehouseArea::query()->where('is_active', true)->where('usage', 'receiving')->get(),
        ]);
    }

    public function show(Request $request, GoodsReceipt $penerimaan_barang)
    {
        $receipt = $penerimaan_barang->load(['farmer', 'harvest', 'inventory', 'lines.grade', 'lines.area', 'lines.batch']);
        $availablePurchases = HarvestTransaction::where('farmer_id', $receipt->farmer_id)->where('receipt_mode', 'separate')->where('is_received', false)
            ->whereDate('harvest_date', '<=', $receipt->received_date)->whereDoesntHave('receipt')->get();

        return view($request->header('X-Warehouse-Detail') === 'modal' ? 'pages.transaction.receiving._detail' : 'pages.transaction.receiving.show', compact('receipt', 'availablePurchases'));
    }

    public function linkPurchase(Request $request, GoodsReceipt $penerimaan_barang)
    {
        $data = $request->validate(['harvest_transaction_id' => 'required|integer|exists:harvest_transactions,id', 'confirm' => 'required|accepted']);
        $this->service->linkPurchase($penerimaan_barang, $data['harvest_transaction_id']);

        return back()->with('success', 'Penerimaan terhubung ke pembelian dan biaya bahan tercatat.');
    }

    public function store(Request $request)
    {
        $receipt = $this->service->save($this->validated($request));

        return redirect()->route('penerimaan-barang.show', $receipt)->with('success', 'Draft penerimaan disimpan. Stok belum bertambah.');
    }

    public function update(Request $request, GoodsReceipt $penerimaan_barang)
    {
        $receipt = $this->service->save($this->validated($request, $penerimaan_barang), $penerimaan_barang);

        return redirect()->route('penerimaan-barang.show', $receipt)->with('success', 'Draft penerimaan diperbarui.');
    }

    public function destroy(GoodsReceipt $penerimaan_barang)
    {
        $this->service->delete($penerimaan_barang);

        return redirect()->route('penerimaan-barang.index')->with('success', 'Draft dihapus.');
    }

    public function confirm(Request $request, GoodsReceipt $penerimaan_barang)
    {
        $request->validate(['confirm' => 'required|accepted']);
        $receipt = $this->service->confirm($penerimaan_barang, $request->user());

        return redirect()->route('penerimaan-barang.show', $receipt)->with('success', 'Barang diterima. Stok dan batch terbentuk satu kali.');
    }

    private function validated(Request $request, ?GoodsReceipt $receipt = null): array
    {
        return $request->validate([
            'received_date' => 'required|date_format:Y-m-d', 'farmer_id' => 'required|integer|exists:farmers,id',
            'harvest_transaction_id' => ['nullable', 'integer', 'exists:harvest_transactions,id', Rule::unique('goods_receipts')->ignore($receipt?->id)],
            'source_reference' => ['nullable', 'string', 'max:100', Rule::unique('goods_receipts')->ignore($receipt?->id)],
            'inventory_id' => 'required|integer|exists:inventories,id', 'note_weight_kg' => 'nullable|numeric|decimal:0,2|min:0|max:999999999.99',
            'notes' => 'nullable|string|max:2000', 'lines' => 'required|array|min:1|max:50',
            'lines.*.quality_grade_id' => 'nullable|integer|exists:quality_grades,id', 'lines.*.warehouse_area_id' => 'required|integer|exists:warehouse_areas,id',
            'lines.*.actual_weight_kg' => 'required|numeric|decimal:0,2|min:0.01|max:999999999.99',
            'lines.*.sack_count' => 'required|integer|min:1|max:1000000',
        ]);
    }
}
