@extends('layouts.app')

@section('title', 'Surat jalan')

@section('breadcrumb')
    <x-breadcrumb 
        pageTitle="Surat jalan" 
        :links="[
            ['name' => 'Penjualan', 'url' => '#'],
            ['name' => 'Surat jalan', 'url' => route('surat-jalan.index')]
        ]" 
    />
@endsection

@section('content')
    <p class="mb-4 rounded-lg bg-white p-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-400">Surat Jalan baru berstatus Draft dan belum memotong stok. Konfirmasi Dikirim hanya saat barang benar-benar keluar dari gudang. Dokumen Data Lama tidak dapat memotong stok lagi.</p>
    <x-data-table 
        id="deliveryNoteTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('surat-jalan.data')" 
    >
        <x-slot:headerAction>
            <button 
                type="button" 
                @click="$dispatch('create-entity')" 
                class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tambah Surat Jalan
            </button>
        </x-slot:headerAction>

        <x-slot:date><span x-text="formatDisplayDate(item.delivery_date)"></span></x-slot:date>
        <x-slot:weight><span x-text="formatWeight(item.quantity_kg)"></span></x-slot:weight>
        <x-slot:status><span x-text="item.status === 'shipped' ? 'Dikirim' : (item.status === 'draft' ? 'Draft — belum dikirim' : 'Data Lama')" class="text-sm font-semibold"></span></x-slot:status>
        <x-slot:shipped_date><span x-text="item.shipped_date ? formatDisplayDate(item.shipped_date) : '-'"></span></x-slot:shipped_date>
        <x-slot:action>
            <div class="flex items-center gap-2">
                @can('manajemen-penjualan.create')
                    <button type="button" x-show="item.status === 'draft'" @click="$dispatch('confirm-shipment', item)" class="rounded-lg bg-green-600 px-4 py-2 text-sm text-white">Konfirmasi Dikirim</button>
                @endcan
                <a :href="`{{ url('surat-jalan') }}/${item.id}/cetak`" target="_blank" rel="noopener"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Cetak</a>
                <button 
                    type="button" 
                    x-show="item.status !== 'shipped'"
                    @click="$dispatch('edit-entity', item)" 
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-600"
                >
                    Edit
                </button>

                <button 
                    type="button" 
                    x-show="item.status !== 'shipped'"
                    @click="$dispatch('delete-entity', item.id)" 
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-rose-600"
                >
                    Hapus
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.sales.delivery-note._modal')
    @include('pages.sales.delivery-note._dispatch-modal')
@endsection
