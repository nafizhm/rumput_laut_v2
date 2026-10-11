@extends('layouts.app')
@section('title','Mulai Proses Harian')
@section('breadcrumb')<x-breadcrumb pageTitle="Mulai Proses Harian" :links="[['name'=>'Gudang','url'=>route('manajemen-gudang.index')]]" />@endsection
@section('content')
<div class="rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    <h2 class="text-xl font-semibold">Mulai Proses Harian</h2>
    <x-info-help><p class="mt-2 text-sm text-gray-500">Pilih karung dari batch bahan yang sudah diterima. Proses gudang dapat berjalan tanpa menunggu harga atau pembayaran; berat hasil resmi dicatat saat timbang dan packing.</p></x-info-help>
    @if($errors->any())<ul class="my-4 rounded-lg bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @if($batches->isEmpty())<p class="my-4 text-sm">Belum ada karung bahan yang tersedia. Konfirmasi penerimaan barang terlebih dahulu.</p>@endif
    <form method="POST" action="{{ route('produksi.store') }}" class="mt-5 space-y-4" x-data="{ rows: @js(old('inputs', [['material_batch_id'=>'','sack_count'=>1]])) }">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">Tanggal mulai<input type="date" name="started_date" value="{{ old('started_date',now('Asia/Jakarta')->format('Y-m-d')) }}" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
            <label class="block text-sm">Area proses<select name="warehouse_area_id" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900"><option value="">Pilih area</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('warehouse_area_id')==$area->id)>{{ $area->code }} — {{ $area->name }}</option>@endforeach</select></label>
            <label class="block text-sm">PIC<input name="pic" value="{{ old('pic',auth()->user()->name) }}" required maxlength="255" class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
            <label class="block text-sm">Jenis pekerjaan<input name="work_type" value="{{ old('work_type') }}" placeholder="Blower / jemur" required maxlength="255" class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
        </div>
        <h3 class="font-semibold">Karung bahan yang digunakan</h3>
        <template x-for="(row,index) in rows" :key="index">
            <div class="flex flex-wrap items-end gap-3 rounded-lg border p-3">
                <label class="min-w-48 flex-1 text-sm">Batch sumber<select :name="`inputs[${index}][material_batch_id]`" x-model="row.material_batch_id" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900"><option value="">Pilih batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->code }} / {{ $batch->line->receipt->farmer->name }} / {{ $batch->line->area->name }} / {{ $batch->remaining_sacks }} karung tersedia</option>@endforeach</select></label>
                <label class="text-sm">Karung diambil<input type="number" :name="`inputs[${index}][sack_count]`" x-model="row.sack_count" min="1" required class="mt-2 w-32 rounded-lg border bg-transparent p-3"></label>
                <button type="button" @click="rows.splice(index,1)" :disabled="rows.length===1" class="p-3 text-red-600">Hapus</button>
            </div>
        </template>
        <button type="button" @click="rows.push({material_batch_id:'',sack_count:1})" class="text-sm text-brand-600">+ Tambah batch sumber</button>
        <label class="block text-sm">Catatan<textarea name="notes" maxlength="2000" class="mt-2 w-full rounded-lg border bg-transparent p-3">{{ old('notes') }}</textarea></label>
        <label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required>Karung sumber, area, dan pekerjaan sudah diperiksa. Mulai proses akan mengurangi saldo bahan dan mengunci alokasi ini.</label>
        <button class="rounded-lg bg-brand-600 px-5 py-3 text-white" @disabled($batches->isEmpty())>Mulai Proses</button>
    </form>
</div>
@endsection
