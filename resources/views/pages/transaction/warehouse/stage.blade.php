@extends('layouts.app')
@section('title',$title)
@section('breadcrumb')<x-breadcrumb :pageTitle="$title" :links="[['name'=>'Manajemen Gudang','url'=>route('manajemen-gudang.index')]]" />
@endsection
@section('content')
<div class="space-y-4 rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
@if($area)
<div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"><h3 class="font-semibold">{{ $area->code }} — {{ $area->name }}</h3><p class="mt-1 text-sm text-gray-500">{{ $area->inventory->item_name }} · {{ $area->is_active ? 'Aktif' : 'Nonaktif' }}</p></div>
@endif
@if($stage==='incoming')
<div class="grid gap-3 sm:grid-cols-3">
<div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800"><p class="text-sm text-gray-500">Karung Belum Diproses</p><p class="mt-2 text-xl font-semibold">{{ number_format($batches->sum('remaining_sacks'),0,',','.') }} karung</p></div>
<div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800"><p class="text-sm text-gray-500">Batch Diterima</p><p class="mt-2 text-xl font-semibold">{{ $batches->count() }} batch</p></div>
<div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800"><p class="text-sm text-gray-500">Berat Diterima Kumulatif</p><p class="mt-2 text-xl font-semibold">{{ number_format($batches->sum('incoming_weight_kg'),2,',','.') }} kg</p></div>
</div>
<div class="flex justify-between gap-3"><x-info-help><p class="text-sm text-gray-500">Batch dari penerimaan yang sudah dikonfirmasi. Kg adalah berat datang, belum menjadi berat resmi barang jadi.</p></x-info-help><a href="{{ route('penerimaan-barang.index') }}" class="text-sm text-brand-500">Daftar Penerimaan →</a></div>
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>@foreach(['Batch','Petani','Area','Kg Datang','Karung Datang','Belum Diproses','Detail'] as $h)<th class="p-3">{{ $h }}</th>@endforeach</tr></thead><tbody>
@forelse($batches as $batch)<tr class="border-t border-gray-200 dark:border-gray-800"><td class="p-3 font-semibold">{{ $batch->code }}</td><td class="p-3">{{ $batch->line->receipt->farmer->name }}</td><td class="p-3">{{ $batch->line->area->name }}</td><td class="p-3">{{ number_format($batch->incoming_weight_kg,2,',','.') }}</td><td class="p-3">{{ $batch->incoming_sacks }}</td><td class="p-3">{{ $batch->remaining_sacks }}</td><td class="p-3"><a href="{{ route('penerimaan-barang.show',$batch->line->receipt) }}" @click.prevent="$dispatch('warehouse-detail', { url: $el.href })" class="text-brand-500">Lihat Penerimaan</a></td></tr>@empty<tr><td colspan="7" class="p-6 text-center text-gray-500">Belum ada batch penerimaan yang dikonfirmasi.</td></tr>@endforelse
</tbody></table></div>
@else
@include('pages.transaction.production.stage')
@endif
<a href="{{ route('manajemen-gudang.index') }}" class="inline-block text-sm text-brand-500">← Kembali ke Dashboard Gudang</a>
</div>
<x-warehouse-detail-modal />
@endsection
