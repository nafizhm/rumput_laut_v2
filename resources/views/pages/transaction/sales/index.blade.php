@extends('layouts.app')
@section('title', 'Penjualan')
@section('breadcrumb')<x-breadcrumb pageTitle="Penjualan" :links="[['name'=>'Penjualan','url'=>route('manajemen-penjualan.index')]]" />@endsection
@section('content')
<div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">Satu nota untuk seluruh barang. Buka Detail untuk melihat barang, potongan, dan pembayaran. Nota yang menggunakan invoice dibayar melalui menu Invoice.</div>
<x-data-table id="salesTable" :rows="$data" :columns="$columns" :url="route('manajemen-penjualan.data')">
<x-slot:headerAction><div class="flex flex-wrap gap-2">@can('manajemen-penjualan.create')<a href="{{ route('manajemen-penjualan.create') }}" class="rounded-lg bg-brand-600 px-4 py-3 text-sm font-medium text-white">+ Tambah Penjualan</a>@endcan<a href="{{ route('manajemen-penjualan.index', ['view'=>'products']) }}" class="rounded-lg border px-4 py-3 text-sm font-medium text-brand-600">Lihat per Produk</a></div></x-slot:headerAction>
<x-slot:date><span x-text="formatDisplayDate(item.sale_date)"></span></x-slot:date>
<x-slot:total_sale_value><span class="tabular-nums" x-text="formatCurrency(item.total_sale_value)"></span></x-slot:total_sale_value>
<x-slot:payment_status><span class="inline-flex rounded-full px-3 py-1 text-xs font-medium" :class="item.payment_status === 'paid' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'" x-text="item.payment_status_label"></span><span x-show="item.has_invoice" class="mt-1 block text-xs text-gray-500">Melalui Invoice</span></x-slot:payment_status>
<x-slot:action><div class="flex flex-wrap gap-2">
<button type="button" @click="$dispatch('view-sale-note', item)" class="rounded-lg border px-3 py-2 text-sm text-brand-600">Detail</button>
<button type="button" x-show="item.can_pay" @click="$dispatch('open-pay-sale', item)" class="rounded-lg bg-brand-600 px-3 py-2 text-sm text-white">Pelunasan</button>
</div></x-slot:action>
</x-data-table>
@include('pages.transaction.sales._sales-modal')
@endsection
@push('styles')
<style>
#salesTable .custom-scrollbar > div { display: block; width: 100%; }
#salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] { width: 100%; }
@media (max-width: 1023px) {
    #salesTable .custom-scrollbar { overflow-x: hidden; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"]:first-child { display: none; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] > div { flex-direction: column; align-items: flex-start; gap: 4px; border-right: 0; padding: 12px 16px; text-align: left; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] > div:first-child { display: none; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] > div:nth-child(2),
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] > div:last-child { grid-column: 1 / -1; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] > div:nth-child(3)::before { content: 'Tanggal'; font-size: 12px; opacity: .6; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] > div:nth-child(4)::before { content: 'Konsumen'; font-size: 12px; opacity: .6; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] > div:nth-child(5)::before { content: 'Total Nota'; font-size: 12px; opacity: .6; }
    #salesTable .custom-scrollbar > div > div[style*="grid-template-columns"] > div:nth-child(6)::before { content: 'Pembayaran'; font-size: 12px; opacity: .6; }
    #salesTable .custom-scrollbar .truncate { white-space: normal; overflow-wrap: anywhere; }
}
</style>
@endpush
