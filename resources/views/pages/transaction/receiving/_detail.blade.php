<div class="space-y-5 rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
@if($errors->any())<ul class="text-sm text-red-600">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<div class="grid gap-3 sm:grid-cols-3"><p>Petani: <strong>{{ $receipt->farmer->name }}</strong></p><p>Tanggal: {{ $receipt->received_date->format('d/m/Y') }}</p><p>Status: <strong>{{ $receipt->status==='draft'?'Draft — stok belum bertambah':'Diterima — dokumen dikunci' }}</strong></p><p>Gudang: {{ $receipt->inventory->item_name }}</p><p>Pembelian: {{ $receipt->harvest?->batch_code ?? 'Tanpa referensi pembelian' }}</p><p>Nota: {{ $receipt->source_reference ?: '-' }}</p></div>
@if($receipt->confirmed_at)<p class="text-sm text-gray-500">Dikonfirmasi {{ $receipt->confirmed_at->format('d/m/Y H:i') }} oleh {{ $receipt->confirmed_by_name }}.</p>@endif
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>@foreach(['Batch Bahan','Area','Berat Aktual','Karung Datang','Sisa Belum Diproses'] as $h)<th class="p-3">{{ $h }}</th>@endforeach</tr></thead><tbody>@foreach($receipt->lines as $line)<tr class="border-t border-gray-200 dark:border-gray-800"><td class="p-3">{{ $line->batch?->code ?? 'Terbentuk setelah konfirmasi' }}</td><td class="p-3">{{ $line->area->name }}</td><td class="p-3">{{ number_format($line->actual_weight_kg,2,',','.') }} kg</td><td class="p-3">{{ $line->sack_count }}</td><td class="p-3">{{ $line->batch?->remaining_sacks ?? '-' }}</td></tr>@endforeach</tbody></table></div>
<p>Berat aktual total: <strong>{{ number_format($receipt->lines->sum('actual_weight_kg'),2,',','.') }} kg</strong> / {{ $receipt->lines->sum('sack_count') }} karung.</p>
@if($receipt->note_weight_kg!==null)<p>Berat nota: {{ number_format($receipt->note_weight_kg,2,',','.') }} kg. Selisih nota − aktual: {{ number_format($receipt->note_weight_kg-$receipt->lines->sum('actual_weight_kg'),2,',','.') }} kg. <span class="text-sm text-gray-500">Bukan otomatis kehilangan atau susut proses.</span></p>@endif
<p class="whitespace-pre-line text-sm text-gray-500">{{ $receipt->notes }}</p>
@if($receipt->status==='draft')
<div class="flex flex-wrap gap-3">@can('receiving.update')<a href="{{ route('penerimaan-barang.edit',$receipt) }}" class="text-brand-500">Edit Draft</a>@endcan @can('receiving.delete')<form method="POST" action="{{ route('penerimaan-barang.destroy',$receipt) }}">@csrf @method('DELETE')<button class="text-red-600">Hapus Draft</button></form>@endcan</div>
@can('receiving.confirm')<form method="POST" action="{{ route('penerimaan-barang.confirm',$receipt) }}" class="space-y-3 rounded-lg bg-green-50 p-4 dark:bg-green-900/20">@csrf<label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required> Saya memastikan barang sudah diterima, ditimbang, dan ditempatkan sesuai rincian. Konfirmasi menambah stok sekali dan mengunci dokumen.</label><button class="rounded-lg bg-green-600 px-5 py-2 text-white">Konfirmasi Diterima</button></form>@endcan
@endif
</div>

@if(!$receipt->harvest_transaction_id)
    <div class="mt-4 rounded-xl border bg-white p-4 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
        <x-info-help><p class="text-sm">Barang ini belum memiliki biaya pembelian dan belum dapat diproses. Hubungkan pembelian petani yang sesuai; stok tidak ditambahkan lagi.</p></x-info-help>
        @can('receiving.update')<form method="POST" action="{{ route('penerimaan-barang.link-purchase',$receipt) }}" class="mt-3 space-y-3">@csrf
            <select name="harvest_transaction_id" required class="w-full rounded-lg border bg-transparent p-3"><option value="">Pilih panen</option>@foreach($availablePurchases as $purchase)<option value="{{ $purchase->id }}">{{ $purchase->batch_code }} - {{ number_format($purchase->total_weight,2,',','.') }} kg</option>@endforeach</select>
            <label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required>Pembelian ini adalah sumber penerimaan barang tersebut.</label>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-white">Hubungkan Pembelian</button>
        </form>@endcan
    </div>
@endif