@extends('layouts.app')
@section('title','Penerimaan Barang')
@section('breadcrumb')<x-breadcrumb pageTitle="Penerimaan Barang" :links="[['name'=>'Manajemen Gudang','url'=>route('manajemen-gudang.index')]]" />
@endsection
@section('content')
<div class="rounded-xl bg-white p-5 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
<div class="mb-5 flex flex-wrap justify-between gap-3"><x-info-help><p class="text-sm text-gray-500">Draft belum menambah stok. Konfirmasi hanya setelah barang diterima dan ditimbang.</p></x-info-help>@can('receiving.create')<a href="{{ route('penerimaan-barang.create') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-white">Tambah Penerimaan</a>@endcan</div>
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>@foreach(['Nomor','Tanggal','Petani','Gudang','Berat Aktual','Karung','Status','Detail'] as $h)<th class="p-3">{{ $h }}</th>@endforeach</tr></thead><tbody>
@forelse($receipts as $receipt)<tr class="border-t border-gray-200 dark:border-gray-800"><td class="p-3 font-semibold">{{ $receipt->number }}</td><td class="p-3">{{ $receipt->received_date->format('d/m/Y') }}</td><td class="p-3">{{ $receipt->farmer->name }}</td><td class="p-3">{{ $receipt->inventory->item_name }}</td><td class="p-3">{{ number_format($receipt->lines->sum('actual_weight_kg'),2,',','.') }} kg</td><td class="p-3">{{ $receipt->lines->sum('sack_count') }}</td><td class="p-3">{{ $receipt->status==='draft'?'Draft':'Diterima' }}</td><td class="p-3"><a href="{{ route('penerimaan-barang.show',$receipt) }}" @click.prevent="$dispatch('warehouse-detail', { url: $el.href })" class="text-brand-500">Lihat Detail</a></td></tr>@empty<tr><td colspan="8" class="p-6 text-center text-gray-500">Belum ada penerimaan sesuai filter.</td></tr>@endforelse
</tbody></table></div></div>
<x-warehouse-detail-modal />
@endsection
