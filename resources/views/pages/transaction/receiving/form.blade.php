@extends('layouts.app')
@section('title','Draft Penerimaan Barang')
@section('breadcrumb')<x-breadcrumb pageTitle="Draft Penerimaan Barang" :links="[['name'=>'Penerimaan Barang','url'=>route('penerimaan-barang.index')]]" />@endsection
@section('content')
@php
    $input='mt-2 w-full rounded-lg border border-gray-300 bg-transparent p-3 dark:border-gray-700';
    $initialLines=old('lines',$receipt->exists?$receipt->lines->map(fn($l)=>$l->only(['warehouse_area_id','actual_weight_kg','sack_count']))->all():[['warehouse_area_id'=>'','actual_weight_kg'=>'','sack_count'=>'']]);
    $areaOptions=$areas->map(fn($a)=>['id'=>$a->id,'name'=>$a->code.' — '.$a->name,'inventory_id'=>$a->inventory_id]);
@endphp
<form method="POST" action="{{ $receipt->exists?route('penerimaan-barang.update',$receipt):route('penerimaan-barang.store') }}" x-data="{lines:@js($initialLines), areas:@js($areaOptions), inventory:'{{ old('inventory_id',$receipt->inventory_id) }}'}" class="space-y-5 rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
@csrf @if($receipt->exists) @method('PUT') @endif
@if($errors->any())<ul class="text-sm text-red-600">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<x-info-help><p class="text-sm text-gray-500">Isi hasil timbang fisik dan pembagian area barang masuk. Dokumen ini masih Draft; belum menambah stok.</p></x-info-help>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
<label>Tanggal Diterima<input type="date" name="received_date" required value="{{ old('received_date',$receipt->received_date?->format('Y-m-d')) }}" class="{{ $input }}"></label>
<label>Petani<select name="farmer_id" required class="{{ $input }} dark:bg-gray-900"><option value="">Pilih petani</option>@foreach($farmers as $f)<option value="{{ $f->id }}" @selected(old('farmer_id',$receipt->farmer_id)==$f->id)>{{ $f->name }}</option>@endforeach</select></label>
@if($receipt->exists)
<label>Referensi Pembelian Panen<select name="harvest_transaction_id" class="{{ $input }} dark:bg-gray-900"><option value="">Tanpa referensi pembelian</option>@foreach($harvests as $h)<option value="{{ $h->id }}" @selected(old('harvest_transaction_id',$receipt->harvest_transaction_id)==$h->id)>{{ $h->batch_code }} — {{ $h->farmer->name }}</option>@endforeach</select></label>
<label>Referensi Nota / Kedatangan<input name="source_reference" maxlength="100" value="{{ old('source_reference',$receipt->source_reference) }}" class="{{ $input }}"><span class="text-xs text-gray-500">Wajib dan unik jika tanpa referensi pembelian.</span></label>
@else
<input type="hidden" name="harvest_transaction_id" value="{{ old('harvest_transaction_id', $receipt->harvest_transaction_id) }}">
@endif
<label>Gudang Bahan<select name="inventory_id" x-model="inventory" required class="{{ $input }} dark:bg-gray-900"><option value="">Pilih gudang</option>@foreach($inventories as $i)<option value="{{ $i->id }}">{{ $i->item_name }}</option>@endforeach</select></label>
<label>Berat Pada Nota (Opsional)<input name="note_weight_kg" type="number" min="0" step="0.01" value="{{ old('note_weight_kg',$receipt->note_weight_kg) }}" class="{{ $input }}"></label>
</div>
<h3 class="font-semibold">Rincian Barang Datang</h3>
<template x-for="(line,index) in lines" :key="index"><div class="grid items-end gap-3 rounded-lg border border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-800">
<label>Area<select :name="'lines['+index+'][warehouse_area_id]'" x-model="line.warehouse_area_id" required class="{{ $input }} dark:bg-gray-900"><option value="">Pilih area</option><template x-for="area in areas.filter(a=>String(a.inventory_id)===String(inventory))" :key="area.id"><option :value="area.id" x-text="area.name"></option></template></select></label>
<label>Berat Aktual (kg)<input :name="'lines['+index+'][actual_weight_kg]'" x-model="line.actual_weight_kg" type="number" min="0.01" step="0.01" required class="{{ $input }}"></label>
<label>Jumlah Karung<input :name="'lines['+index+'][sack_count]'" x-model="line.sack_count" type="number" min="1" step="1" required class="{{ $input }}"></label>
<button type="button" @click="lines.splice(index,1)" :disabled="lines.length===1" class="rounded-lg border border-gray-300 p-3 text-sm text-gray-500">Hapus Baris</button>
</div></template>
<button type="button" @click="lines.push({warehouse_area_id:'',actual_weight_kg:'',sack_count:''})" class="text-sm text-brand-500">+ Tambah Area Barang Masuk</button>
<p class="font-semibold" x-text="'Total aktual: '+lines.reduce((s,l)=>s+Number(l.actual_weight_kg||0),0).toLocaleString('id-ID')+' kg / '+lines.reduce((s,l)=>s+Number(l.sack_count||0),0)+' karung'"></p>
<label class="block">Catatan<textarea name="notes" maxlength="2000" class="{{ $input }}">{{ old('notes',$receipt->notes) }}</textarea></label>
<div class="flex gap-3"><button class="rounded-lg bg-brand-500 px-5 py-2 text-white">Simpan Draft</button><a href="{{ route('penerimaan-barang.index') }}" class="px-5 py-2">Batal</a></div>
</form>
@endsection
