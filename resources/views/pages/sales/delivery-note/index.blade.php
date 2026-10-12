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
    <x-info-help><p class="mb-4 rounded-lg bg-white p-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-400">Surat Jalan baru berstatus Draft dan belum memotong stok. Konfirmasi Dikirim hanya saat barang benar-benar keluar dari gudang. Dokumen Data Lama tidak dapat memotong stok lagi.</p></x-info-help>
    <x-data-table 
        id="deliveryNoteTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('surat-jalan.data')" 
    >
        <x-slot:headerAction>
            
@can('surat-jalan.create')
<a
                href="{{ route('surat-jalan.create') }}"
                class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tambah Surat Jalan
            </a>
@endcan

        </x-slot:headerAction>

        <x-slot:document>
            <div class="flex min-w-0 flex-col gap-1 text-sm">
                <span class="text-gray-500 dark:text-gray-400" x-text="formatDisplayDate(item.delivery_date)"></span>
                <span class="break-words font-semibold text-gray-800 dark:text-gray-200" x-text="item.number"></span>
                <span class="break-words text-gray-500 dark:text-gray-400" x-text="'No. Penjualan: ' + (item.sale?.sale_reference || item.sale?.invoice_number || '-')"></span>
            </div>
        </x-slot:document>
        <x-slot:weight><span x-text="formatWeight(item.quantity_kg)"></span></x-slot:weight>
        <x-slot:status>
            <div class="flex flex-col gap-1">
                <span x-text="item.status === 'shipped' ? 'Dikirim' : (item.status === 'draft' ? 'Draft — belum dikirim' : 'Data Lama')" class="text-sm font-semibold"></span>
                <span x-show="item.shipped_date" x-text="item.shipped_date ? formatDisplayDate(item.shipped_date) : ''" class="text-xs text-gray-500 dark:text-gray-400"></span>
            </div>
        </x-slot:status>
        <x-slot:action>
            <div class="flex flex-wrap items-center gap-2">
                @can('surat-jalan.update')
                    <button type="button" x-show="item.status === 'draft'" @click="$dispatch('confirm-shipment', item)" class="whitespace-nowrap rounded-md bg-green-600 px-2 py-1 text-xs text-white">Konfirmasi Dikirim</button>
                @endcan
                <a :href="`{{ url('surat-jalan') }}/${item.id}/cetak`" target="_blank" rel="noopener"
                    class="whitespace-nowrap rounded-md border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Cetak</a>
                
@can('surat-jalan.update')
<button 
                    type="button" 
                    x-show="item.status !== 'shipped'"
                    @click="item.status === 'draft' ? window.location.href = `{{ url('surat-jalan') }}/${item.id}/edit` : $dispatch('edit-entity', item)"
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-brand-500 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-brand-600"
                >
                    Edit
                </button>
@endcan


                
@can('surat-jalan.delete')
<button 
                    type="button" 
                    :disabled="item.status === 'shipped' || !!item.shipped_at"
                    :title="item.status === 'shipped' || item.shipped_at ? 'Surat jalan sudah dikirim dan tidak dapat dihapus.' : 'Hapus surat jalan'"
                    @click="$dispatch('delete-entity', item.id)" 
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-md bg-rose-500 px-2 py-1 text-xs font-medium text-white transition-colors hover:bg-rose-600 disabled:cursor-not-allowed disabled:bg-gray-400 disabled:opacity-60"
                >
                    Hapus
                </button>
@endcan

            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.sales.delivery-note._modal')
    @include('pages.sales.delivery-note._dispatch-modal')
@endsection
