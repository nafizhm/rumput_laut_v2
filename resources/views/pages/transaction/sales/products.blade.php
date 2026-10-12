@extends('layouts.app')
@section('title', 'Penjualan per Produk')
@section('breadcrumb')<x-breadcrumb pageTitle="Penjualan per Produk" :links="[['name'=>'Penjualan','url'=>route('manajemen-penjualan.index')]]" />@endsection
@section('content')
<p class="mb-5 rounded-xl border bg-white p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">Semua barang yang dijual ditampilkan satu baris per produk. Pelunasan tetap melalui daftar nota atau Invoice.</p>
<x-data-table id="salesProductsTable" :rows="$data" :columns="$columns" :url="route('manajemen-penjualan.data', ['view'=>'products'])">
    <x-slot:headerAction><div class="flex flex-wrap gap-2">@can('manajemen-penjualan.create')<a href="{{ route('manajemen-penjualan.create') }}" class="rounded-lg bg-brand-600 px-4 py-3 text-sm font-medium text-white">+ Tambah Penjualan</a>@endcan<a href="{{ route('manajemen-penjualan.index') }}" class="rounded-lg border px-4 py-3 text-sm font-medium text-brand-600">Lihat per Nota</a></div></x-slot:headerAction>
    <x-slot:date><span x-text="formatDisplayDate(item.sale_date)"></span></x-slot:date>
    <x-slot:weight><span class="tabular-nums" x-text="formatWeight(item.total_weight_kg)"></span></x-slot:weight>
    <x-slot:price><span class="tabular-nums" x-text="formatCurrency(item.price_per_kg)"></span></x-slot:price>
    <x-slot:total><span class="tabular-nums" x-text="formatCurrency(item.total_sale_value)"></span></x-slot:total>
</x-data-table>
@endsection
