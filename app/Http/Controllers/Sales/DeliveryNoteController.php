<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\SaveDeliveryNoteRequest;
use App\Models\CustomerPurchaseOrder;
use App\Models\DeliveryNote;
use App\Models\SalesTransaction;
use App\Models\FinishedBatch;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\Services\Sales\DeliveryNoteService;
use App\Traits\JsonResponser;

class DeliveryNoteController extends Controller
{
    use JsonResponser;

    public function __construct(protected DeliveryNoteService $service) {}

    public function index()
    {
        $columns = [
            ['key' => 'number', 'label' => 'Surat Jalan', 'sortable' => true, 'render' => 'document', 'width' => '240px'],
            ['key' => 'status', 'label' => 'Status', 'render' => 'status'],
            ['key' => 'recipient_name', 'label' => 'Penerima', 'sortable' => true],
            ['key' => 'item_name', 'label' => 'Barang', 'sortable' => true],
            ['key' => 'quantity_kg', 'label' => 'Jumlah (Kg)', 'render' => 'weight', 'align' => 'right'],
            ['key' => 'packaging', 'label' => 'Kemasan'],
            ['key' => 'action', 'label' => 'Aksi', 'width' => '240px', 'render' => 'action'],
        ];
        $data = $this->service->getAll();
        $orders = CustomerPurchaseOrder::orderByDesc('po_date')->get([
            'id', 'po_number', 'customer_name', 'customer_address', 'item_name', 'quantity_kg',
        ])->map(fn ($order) => [
            'value' => $order->id, 'label' => $order->po_number.' ? '.$order->customer_name,
            'recipient_name' => $order->customer_name, 'destination_address' => $order->customer_address,
            'item_name' => $order->item_name, 'quantity_kg' => $order->quantity_kg,
        ]);
        $sales = SalesTransaction::with(['factory', 'inventory'])->where('stock_mode', 'delivery_note')->orderByDesc('sale_date')->get()->map(fn ($sale) => [
            'value' => $sale->id, 'label' => $sale->sale_reference.' - '.$sale->factory->name.' - '.$sale->document_item_name,
            'recipient_name' => $sale->factory->name, 'destination_address' => $sale->factory->address ?? '',
            'item_name' => $sale->document_item_name, 'origin' => $sale->inventory->item_name,
            'quantity_kg' => $sale->total_weight_kg, 'sack_count' => $sale->sold_sacks ?? 0,
            'customer_purchase_order_id' => $sale->customer_purchase_order_id,
            'select_batch' => (bool) ($sale->sale_group_number && !$sale->finished_batch_id),
            'quality_grade_id' => $sale->quality_grade_id,
        ]);
        $batches = Schema::hasTable('finished_batches') ? FinishedBatch::with('area')->readyForSale()->get()->map(fn ($b) => [
                'id' => $b->id, 'quality_grade_id' => $b->quality_grade_id, 'area_name' => $b->area->name,
                'label' => $b->area->name.' / '.$b->code.' / '.$b->remaining_weight_kg.' Kg / '.$b->remaining_packages.' kemasan',
            ]) : collect();
        return view('pages.sales.delivery-note.index', compact('data', 'columns', 'orders', 'sales', 'batches'));
    }

    public function data()
    {
        return $this->successResponse('Data surat jalan berhasil diambil', $this->service->getAll());
    }

    public function create() { return view('pages.sales.delivery-note.form', $this->formData()); }
    public function edit(DeliveryNote $surat_jalan) {
        abort_unless($surat_jalan->status === 'draft',403,'Hanya draft yang dapat diedit.');
        return view('pages.sales.delivery-note.form', $this->formData($surat_jalan));
    }
    private function formData(?DeliveryNote $note=null): array {
        $processedSaleIds = DeliveryNote::query()->pluck('sales_transaction_id')->filter();
        if (Schema::hasTable('delivery_note_items')) {
            $processedSaleIds = $processedSaleIds->merge(\App\Models\DeliveryNoteItem::query()->pluck('sales_transaction_id'));
        }
        $sales = SalesTransaction::with(['factory','inventory','stockMovements','purchaseOrderItem','grade'])->where('stock_mode','delivery_note')->orderByDesc('sale_date')->get();
        $sales = $sales->groupBy(fn($s)=>$s->sale_group_number ?: 'sale:'.$s->id)
            ->filter(function ($rows) use ($note, $processedSaleIds) {
                if ($note && $rows->contains('id', $note->sales_transaction_id)) return true;

                return !$rows->contains(fn ($sale) => $processedSaleIds->contains($sale->id))
                    && $rows->contains(fn ($sale) => (float) $sale->total_weight_kg > $sale->stockMovements->sum('weight_kg')
                        && (int) $sale->sold_sacks > $sale->stockMovements->sum('sack_count'));
            })->map(function($rows) {
            $first=$rows->sortBy('id')->first();
            return ['id'=>$first->id,'label'=>$first->sale_reference.' / '.$first->factory->name,
                'recipient_name'=>$first->factory->name,'destination_address'=>$first->factory->address ?? '',
                'items'=>$rows->map(fn($s)=>['sales_transaction_id'=>$s->id,'item_name'=>$s->document_item_name,
                    'quantity_kg'=>max(0,(float)$s->total_weight_kg-$s->stockMovements->sum('weight_kg')),
                    'sack_count'=>max(0,(int)$s->sold_sacks-$s->stockMovements->sum('sack_count')),
                    'finished_batch_id'=>$s->finished_batch_id, 'select_batch'=>(bool)($s->sale_group_number && !$s->finished_batch_id), 'quality_grade_id'=>$s->quality_grade_id])->values()];
        })->values();
        if ($note) $note->load('items');
        $savedBatchIds = $note ? $note->items->pluck('finished_batch_id')->push($note->finished_batch_id)->filter()->unique()->all() : [];
        $batches = Schema::hasTable('finished_batches') ? FinishedBatch::with('area')
            ->where(fn ($query) => $query->readyForSale(true)->orWhereIn('id', $savedBatchIds))
            ->get()->map(fn($b)=>['id'=>$b->id,'warehouse_area_id'=>$b->warehouse_area_id,'area_name'=>$b->area?->name ?? 'Area tidak tersedia','weight_kg'=>$b->remaining_weight_kg,'packages'=>$b->remaining_packages,'quality_grade_id'=>$b->quality_grade_id,'label'=>($b->area?->name ?? 'Area tidak tersedia').' / '.$b->code.' / '.$b->remaining_weight_kg.' Kg / '.$b->remaining_packages.' kemasan']) : collect();
        return compact('sales','batches','note');
    }

    public function store(SaveDeliveryNoteRequest $request)
    {
        if ($request->has('items')) $this->service->saveItems($request->validated());
        else $this->service->store($request->validated());
        return $this->successResponse('Surat jalan berhasil ditambahkan');
    }

    public function update(SaveDeliveryNoteRequest $request, DeliveryNote $surat_jalan)
    {
        if ($request->has('items')) $this->service->saveItems($request->validated(), $surat_jalan);
        else $this->service->update($surat_jalan, $request->validated());
        return $this->successResponse('Surat jalan berhasil diperbarui');
    }

    public function destroy(DeliveryNote $surat_jalan)
    {
        $this->service->delete($surat_jalan);
        return $this->successResponse('Surat jalan berhasil dihapus');
    }

    public function print(DeliveryNote $surat_jalan)
    {
        if (Schema::hasTable('delivery_note_items')) $surat_jalan->load('items');
        return view('pages.sales.delivery-note.print', ['note' => $surat_jalan, 'profile' => \App\Models\CompanyProfile::current()]);
    }

    public function dispatch(Request $request, DeliveryNote $surat_jalan)
    {
        $data = $request->validate(['shipped_date' => 'required|date_format:Y-m-d', 'confirm' => 'required|accepted']);
        $this->service->dispatch($surat_jalan, $data['shipped_date'], $request->user());
        return $this->successResponse('Pengiriman dikonfirmasi dan stok dikurangi satu kali');
    }
}
