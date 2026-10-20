@extends('layouts.app')
@section('title','Mulai WIP')
@section('breadcrumb')<x-breadcrumb pageTitle="Mulai WIP" :links="[['name'=>'WIP','url'=>route('produksi.index')]]" />@endsection
@section('content')
<div class="rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
<h2 class="text-xl font-semibold">Mulai WIP</h2>
<p class="mt-2 text-sm text-gray-500">Work in Process / Work in Progress. Ambil bahan dari satu area penerimaan. Bahan tetap tercatat di WIP sampai dipindahkan ke area jadi.</p>
@if($errors->any())<ul class="my-4 rounded-lg bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="POST" action="{{ route('produksi.store') }}" class="mt-5 space-y-5">
@csrf
<input type="hidden" name="token" value="{{ old('token',(string) \Illuminate\Support\Str::uuid()) }}">
<div class="grid gap-4 sm:grid-cols-2">
<label class="block text-sm">Tanggal mulai<input type="date" name="date" value="{{ old('date',now('Asia/Jakarta')->format('Y-m-d')) }}" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
<label class="text-sm">Area sumber<select name="source_area_id" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900"><option value="">Pilih satu area penerimaan</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('source_area_id') == $area->id)>{{ $areaLabels[$area->id] }} / {{ $batches->filter(fn($batch) => $batch->line->warehouse_area_id == $area->id)->sum('remaining_sacks') }} karung tersedia</option>@endforeach</select></label>
<label class="text-sm">Karung diambil ke WIP<input type="number" name="sack_count" value="{{ old('sack_count') }}" min="1" max="1000000" step="1" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
<label class="text-sm">Pekerjaan<input name="work_type" value="{{ old('work_type') }}" maxlength="255" required class="mt-2 w-full rounded-lg border bg-transparent p-3" placeholder="Contoh: jemur / blower"></label>
<label class="text-sm">PIC<input name="pic" value="{{ old('pic',auth()->user()->name) }}" maxlength="255" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
</div>
<label class="block text-sm">Catatan (opsional)<textarea name="notes" rows="2" maxlength="2000" class="mt-2 w-full rounded-lg border bg-transparent p-3">{{ old('notes') }}</textarea></label>
<p class="text-sm text-gray-500">Bahan diambil otomatis dari batch terlama pada area sumber. Berat WIP mengikuti alokasi berat bahan asal. Area tujuan, berat hasil dan kemasan dicatat saat hasil selesai, termasuk jika selesai sebagian.</p>
<label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required>Area sumber dan jumlah karung telah diperiksa. Simpan akan mengurangi stok penerimaan dan menambah WIP.</label>
<div class="flex gap-3"><a href="{{ route('produksi.index') }}" class="rounded-lg border px-5 py-3 text-sm">Batal</a><button class="rounded-lg bg-brand-600 px-5 py-3 text-sm text-white" @disabled($batches->isEmpty())>Mulai WIP</button></div>
</form></div>
@endsection
