@extends('layouts.app')
@section('title','Catat Proses Harian')
@section('breadcrumb')<x-breadcrumb pageTitle="Catat Proses Harian" :links="[['name'=>'Proses Harian','url'=>route('produksi.index')]]" />@endsection
@section('content')
<div class="rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
<h2 class="text-xl font-semibold">Catat Proses Harian</h2>
<p class="mt-2 text-sm text-gray-500">Catat bahan yang diambil dan hasil packing. Simpan setelah pekerjaan selesai.</p>
@if($errors->any())<ul class="my-4 rounded-lg bg-red-50 p-4 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="POST" action="{{ route('produksi.store') }}" class="mt-5 space-y-5" x-data="{ sources: @js(old('sources', [['warehouse_area_id'=>'','sack_count'=>'']])), outputs: @js(old('outputs', [['warehouse_area_id'=>'','weight_kg'=>'','package_count'=>'']])) }">
@csrf
<label class="block text-sm">Tanggal<input type="date" name="date" value="{{ old('date',now('Asia/Jakarta')->format('Y-m-d')) }}" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required class="mt-2 rounded-lg border bg-transparent p-3"></label>
<section class="space-y-3"><h3 class="font-semibold">Diambil dari penerimaan</h3>
<template x-for="(row,index) in sources" :key="index"><div class="grid items-end gap-3 rounded-lg border p-3 sm:grid-cols-3">
<label class="text-sm">Area asal<select :name="'sources['+index+'][warehouse_area_id]'" x-model="row.warehouse_area_id" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900"><option value="">Pilih area</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $areaLabels[$area->id] }} / {{ $batches->filter(fn($batch) => $batch->line->warehouse_area_id == $area->id)->sum('remaining_sacks') }} karung tersedia</option>@endforeach</select></label>
<label class="text-sm">Karung diambil<input type="number" :name="'sources['+index+'][sack_count]'" x-model="row.sack_count" min="1" max="1000000" step="1" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
<button x-show="sources.length > 1" type="button" @click="sources.splice(index,1)" class="p-3 text-left text-sm text-red-600">Hapus</button>
</div></template>
<button type="button" @click="sources.push({warehouse_area_id:'',sack_count:''})" class="text-sm text-brand-600">+ Area asal lain</button></section>
<section class="space-y-3"><h3 class="font-semibold">Masuk bahan siap jual</h3>
<template x-for="(row,index) in outputs" :key="index"><div class="grid items-end gap-3 rounded-lg border p-3 sm:grid-cols-4">
<label class="text-sm">Area tujuan<select :name="'outputs['+index+'][warehouse_area_id]'" x-model="row.warehouse_area_id" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900"><option value="">Pilih area</option>@foreach($finishedAreas as $area)<option value="{{ $area->id }}">{{ $areaLabels[$area->id] }}</option>@endforeach</select></label>
<label class="text-sm">Berat hasil (Kg)<input type="number" :name="'outputs['+index+'][weight_kg]'" x-model="row.weight_kg" min="0.01" max="999999999.99" step="0.01" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
<label class="text-sm">Jumlah kemasan<input type="number" :name="'outputs['+index+'][package_count]'" x-model="row.package_count" min="1" max="1000000" step="1" required class="mt-2 w-full rounded-lg border bg-transparent p-3"></label>
<button x-show="outputs.length > 1" type="button" @click="outputs.splice(index,1)" class="p-3 text-left text-sm text-red-600">Hapus</button>
</div></template>
<button type="button" @click="outputs.push({warehouse_area_id:'',weight_kg:'',package_count:''})" class="text-sm text-brand-600">+ Area tujuan lain</button></section>
<label class="block text-sm">Catatan (opsional)<textarea name="notes" rows="2" maxlength="2000" class="mt-2 w-full rounded-lg border bg-transparent p-3">{{ old('notes') }}</textarea></label>
<p class="text-xs text-gray-500">Karung diambil dari stok paling lama otomatis. Berat bahan mengikuti alokasi stok asal; berat siap jual sesuai timbang packing.</p>
<div class="flex gap-3"><a href="{{ route('produksi.index') }}" class="rounded-lg border px-5 py-3 text-sm">Batal</a><button class="rounded-lg bg-brand-600 px-5 py-3 text-sm text-white" @disabled($batches->isEmpty() || $finishedAreas->isEmpty())>Simpan Proses Harian</button></div>
</form></div>
@endsection
