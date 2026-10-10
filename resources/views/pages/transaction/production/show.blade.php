@extends('layouts.app')
@section('title',$run->number)
@section('breadcrumb')<x-breadcrumb :pageTitle="$run->number" :links="[['name'=>'Gudang','url'=>route('manajemen-gudang.index')]]" />@endsection
@section('content')
<div class="space-y-5 rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    @if($errors->any())<ul class="rounded-lg bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <h2 class="text-xl font-semibold">{{ $run->number }}</h2>
    <p class="text-sm">Status: <strong>{{ ['processing'=>'Proses aktif','wip'=>'WIP — menunggu press/timbang/packing','packed'=>'Selesai packing'][$run->status] }}</strong>. PIC: {{ $run->pic }}. Pekerjaan: {{ $run->work_type }}.</p>
    <p class="text-right tabular-nums text-sm text-gray-500">Mulai {{ $run->started_date->format('d/m/Y') }}. Bahan {{ $run->input_sacks }} karung. Nilai bahan {{ \App\Services\Finance\AccountingMoney::format($run->material_cost_cents) }}, biaya proses {{ \App\Services\Finance\AccountingMoney::format($run->processing_cost_cents) }}.</p>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>@foreach(['Batch bahan','Petani','Karung diambil','Biaya bahan'] as $h)<th class="p-3 {{ str_starts_with($h,'Nilai') ? 'text-right' : '' }}">{{ $h }}</th>@endforeach</tr></thead><tbody>@foreach($run->inputs as $input)<tr class="border-t"><td class="p-3"><a class="text-brand-600" href="{{ route('penerimaan-barang.show',$input->batch->line->receipt) }}">{{ $input->batch->code }}</a></td><td class="p-3">{{ $input->batch->line->receipt->farmer->name }}</td><td class="p-3">{{ $input->sack_count }}</td><td class="text-right tabular-nums p-3">{{ \App\Services\Finance\AccountingMoney::format($input->cost_cents) }}</td></tr>@endforeach</tbody></table></div>
    @can('production.confirm')
    @if($run->status==='processing')
        <form method="POST" action="{{ route('produksi.complete',$run) }}" class="space-y-4 rounded-lg border p-4">@csrf
            <h3 class="font-semibold">Selesaikan proses ke WIP</h3>
            <label class="block text-sm">Tanggal selesai<input type="date" name="completed_date" value="{{ old('completed_date',now('Asia/Jakarta')->format('Y-m-d')) }}" min="{{ $run->started_date->format('Y-m-d') }}" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
            <label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required>Proses blower/jemur selesai. Barang masuk WIP dan belum siap dijual.</label>
            <button class="rounded-lg bg-brand-600 px-5 py-3 text-white">Konfirmasi Selesai Proses</button>
        </form>
    @elseif($run->status==='wip')
        <form method="POST" action="{{ route('produksi.pack',$run) }}" class="space-y-4 rounded-lg border p-4" x-data="{ rows: @js(old('outputs', [['warehouse_area_id'=>'','quality_grade_id'=>'','weight_kg'=>'','package_count'=>'']])) }">@csrf
            <h3 class="font-semibold">Press, timbang aktual dan packing</h3>
            <label class="block text-sm">Tanggal packing<input type="date" name="packed_date" value="{{ old('packed_date',now('Asia/Jakarta')->format('Y-m-d')) }}" min="{{ $run->completed_date->format('Y-m-d') }}" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
            <p class="text-sm text-gray-500">Isi hasil aktual. Jumlah kemasan jadi terpisah dari karung bahan; berat hasil tidak dihitung otomatis dari berat datang. Biaya dibagi menurut berat hasil masing-masing batch.</p>
            <template x-for="(row,index) in rows" :key="index"><div class="grid gap-3 rounded-lg border p-3 sm:grid-cols-2">
                <label class="text-sm">Area barang jadi<select :name="`outputs[${index}][warehouse_area_id]`" x-model="row.warehouse_area_id" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900"><option value="">Pilih area</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->code }} / {{ $area->name }} — {{ $area->grades->pluck('name')->join(', ') }}</option>@endforeach</select></label>
                <label class="text-sm">Kualitas hasil<select :name="`outputs[${index}][quality_grade_id]`" x-model="row.quality_grade_id" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900"><option value="">Pilih kualitas</option>@foreach($grades as $grade)<option value="{{ $grade->id }}">{{ $grade->name }}</option>@endforeach</select></label>
                <label class="text-sm">Berat timbang aktual (kg)<input type="number" :name="`outputs[${index}][weight_kg]`" x-model="row.weight_kg" min="0.01" step="0.01" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
                <label class="text-sm">Jumlah kemasan jadi<input type="number" :name="`outputs[${index}][package_count]`" x-model="row.package_count" min="1" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
                <button type="button" @click="rows.splice(index,1)" :disabled="rows.length===1" class="text-left text-sm text-red-600">Hapus hasil</button>
            </div></template>
            <button type="button" @click="rows.push({warehouse_area_id:'',quality_grade_id:'',weight_kg:'',package_count:''})" class="text-sm text-brand-600">+ Tambah hasil/kualitas/area</button>
            <x-input label="Biaya packing dibayar tunai (Rp)" name="packing_cost" :value="old('packing_cost', 0)" :isCurrency="true" :currencyDecimals="2" required />
            <p class="text-xs text-gray-500">Biaya ini masuk barang jadi dan kas keluar. Catat satu kali di sini.</p>
            <label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required>Hasil timbang, kualitas, area dan jumlah kemasan sudah diperiksa. Konfirmasi mengunci hasil dan menambah stok siap jual.</label>
            <button class="rounded-lg bg-brand-600 px-5 py-3 text-white">Konfirmasi Packing</button>
        </form>
    @endif
    @endcan
    @if($run->status==='packed')
        <h3 class="font-semibold">Batch barang jadi</h3>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>@foreach(['Batch','Kualitas / area','Kg hasil','Kemasan hasil','Kg tersedia','Kemasan tersedia','Nilai hasil'] as $h)<th class="p-3 {{ str_starts_with($h,'Nilai') ? 'text-right' : '' }}">{{ $h }}</th>@endforeach</tr></thead><tbody>@foreach($run->outputs as $batch)<tr class="border-t"><td class="p-3">{{ $batch->code }}</td><td class="p-3">{{ $batch->grade->name }} / {{ $batch->area->code }}</td><td class="p-3">{{ number_format($batch->weight_kg,2,',','.') }}</td><td class="p-3">{{ $batch->package_count }}</td><td class="p-3">{{ number_format($batch->remaining_weight_kg,2,',','.') }}</td><td class="p-3">{{ $batch->remaining_packages }}</td><td class="text-right tabular-nums p-3">{{ \App\Services\Finance\AccountingMoney::format($batch->cost_cents) }}</td></tr>@endforeach</tbody></table></div>
        <a href="{{ route('manajemen-penjualan.index') }}" class="text-sm text-brand-600">Buka penjualan</a>
    @endif
    @can('journal.read')<div class="space-y-2 text-sm"><h3 class="font-semibold">Jurnal terkait</h3>@foreach($run->journals as $journal)<a class="block text-brand-600" href="{{ route('jurnal.show',$journal) }}">{{ $journal->number }} — {{ $journal->status }}</a>@endforeach</div>@endcan
</div>
@endsection
