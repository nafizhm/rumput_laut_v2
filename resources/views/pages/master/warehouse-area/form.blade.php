@extends('layouts.app')
@section('title', 'Pengaturan Area Gudang')
@section('breadcrumb')<x-breadcrumb pageTitle="Pengaturan Area Gudang" :links="[['name'=>'Area Gudang','url'=>route('area-gudang.index')]]" />@endsection
@section('content')
@php($input='mt-2 w-full rounded-lg border border-gray-300 bg-transparent p-3 dark:border-gray-700')
<form method="POST" action="{{ $area->exists ? route('area-gudang.update',$area) : route('area-gudang.store') }}" class="max-w-3xl space-y-5 rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
@csrf @if($area->exists) @method('PUT') @endif
@if($errors->any())<ul class="text-sm text-red-600">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<p class="text-sm text-gray-500">Nama dan kode area bebas diatur sesuai lokasi gudang. Fungsi area mengikuti pilihan Jenis Area; angka atau huruf pada nama tidak menentukan barang masuk atau barang ready.</p>
<div class="grid gap-4 sm:grid-cols-2">
<label>Kode Area<input name="code" required maxlength="30" value="{{ old('code',$area->code) }}" class="{{ $input }}"></label>
<label>Nama Area<input name="name" required maxlength="100" value="{{ old('name',$area->name) }}" class="{{ $input }}"></label>
<label>Gudang<select name="inventory_id" required class="{{ $input }} dark:bg-gray-900">@foreach($inventories as $i)<option value="{{ $i->id }}" @selected(old('inventory_id',$area->inventory_id)==$i->id)>{{ $i->item_name }}</option>@endforeach</select></label>
<label>Jenis Area<select name="usage" class="{{ $input }} dark:bg-gray-900">@foreach(['receiving'=>'Barang Masuk','processing'=>'Proses/WIP','finished'=>'Barang Ready'] as $v=>$n)<option value="{{ $v }}" @selected(old('usage',$area->usage)===$v)>{{ $n }}</option>@endforeach</select></label>
<label>Status<select name="is_active" class="{{ $input }} dark:bg-gray-900"><option value="1" @selected(old('is_active',$area->is_active))>Aktif</option><option value="0" @selected(!old('is_active',$area->is_active))>Nonaktif</option></select></label>
</div>
<label class="block">Keterangan<textarea name="description" maxlength="2000" class="{{ $input }}">{{ old('description',$area->description) }}</textarea></label>
<div class="flex gap-3"><button class="rounded-lg bg-brand-500 px-5 py-2 text-white">Simpan Area</button><a href="{{ route('area-gudang.index') }}" class="px-5 py-2">Batal</a></div>
</form>
@endsection
