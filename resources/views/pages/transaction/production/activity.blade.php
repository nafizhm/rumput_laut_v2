@extends('layouts.app')
@section('title', 'Catat Aktivitas Area')
@section('breadcrumb')<x-breadcrumb pageTitle="Catat Aktivitas Area" :links="[['name'=>'Proses Harian','url'=>route('produksi.index')]]" />@endsection
@section('content')
<div class="rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    <h2 class="text-xl font-semibold">Catat Aktivitas Area</h2>
    <p class="mt-2 text-sm text-gray-500">Catat pekerjaan di area penerimaan, seperti jemur, sortir, atau pembalikan. Stok tetap di area semula. Jumlah karung hanya sebagai informasi pekerjaan.</p>
    @if($errors->any())<ul class="my-4 rounded-lg bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <form method="POST" action="{{ route('produksi.activity.store') }}" class="mt-5 space-y-5">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm">Tanggal<input name="activity_date" type="date" value="{{ old('activity_date', now('Asia/Jakarta')->format('Y-m-d')) }}" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
            <label class="text-sm">Area<select name="warehouse_area_id" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900"><option value="">Pilih area</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('warehouse_area_id') == $area->id)>{{ $area->name }}</option>@endforeach</select></label>
            <label class="text-sm">Jenis pekerjaan<input name="work_type" value="{{ old('work_type') }}" placeholder="Contoh: Jemur / Sortir / Pembalikan" maxlength="255" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
            <label class="text-sm">Karung dikerjakan<input name="sack_count" type="number" value="{{ old('sack_count') }}" min="1" max="1000000" step="1" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
            <label class="text-sm">PIC<input name="pic" value="{{ old('pic', auth()->user()->name) }}" maxlength="255" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
        </div>
        <label class="block text-sm">Catatan (opsional)<textarea name="notes" maxlength="2000" rows="3" class="mt-2 w-full rounded-lg border bg-transparent p-3">{{ old('notes') }}</textarea></label>
        <div class="flex gap-3"><a href="{{ route('produksi.index') }}" class="rounded-lg border px-5 py-3 text-sm">Batal</a><button class="rounded-lg bg-brand-600 px-5 py-3 text-sm text-white" @disabled($areas->isEmpty())>Simpan Aktivitas Area</button></div>
    </form>
</div>
@endsection
