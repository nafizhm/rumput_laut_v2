@extends('layouts.app')

@section('title', 'Manajemen Panen')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Manajemen Panen" :links="[['name' => 'Manajemen Panen', 'url' => route('manajemen-panen.index')]]" />
@endsection

@section('content')
    <x-info-help><p class="mb-4 rounded-lg bg-white p-4 text-sm text-gray-500 dark:bg-gray-900">Catat petani, hasil timbang dan area barang masuk. Konfirmasi penerimaan menambah stok dan batch sekali, lalu mengunci rincian barang.</p></x-info-help>
    <x-data-table id="harvestTransactionTable" :rows="$data" :columns="$columns" :url="route('harvest-data')">
        <x-slot:headerAction>
            @if(auth()->user()->can('manajemen-panen.create') && auth()->user()->can('receiving.create') && auth()->user()->can('receiving.confirm'))
            <button type="button" @click="$dispatch('create-harvest-transaction')"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto hover:bg-gray-50 dark:hover:bg-white/3 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-plus-icon lucide-plus">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                Tambah Data
            </button>
            @endif
        </x-slot:headerAction>

        <x-slot:longDate>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatDisplayDate(item.harvest_date)"></span>
        </x-slot:longDate>
        <x-slot:receipt>
            <span x-show="item.receipt_mode !== 'separate'" class="text-sm text-gray-500">Alur Lama</span>
            <template x-if="item.receipt_mode === 'separate'"><div>
                <span class="text-sm" x-text="item.is_received ? 'Diterima' : (item.receipt ? 'Draft Penerimaan' : 'Belum Diterima')"></span>
                @can('receiving.read')<a x-show="item.receipt" :href="`{{ url('penerimaan-barang') }}/${item.receipt?.id}`" @click.prevent="$dispatch('warehouse-detail', { url: $el.href })" class="block text-sm text-brand-500">Lihat Penerimaan</a>@endcan
                @can('receiving.create')<a x-show="!item.receipt" :href="`{{ route('penerimaan-barang.create') }}?harvest_id=${item.id}`" class="block text-sm text-brand-500">Buat Penerimaan</a>@endcan
            </div></template>
        </x-slot:receipt>

        <x-slot:warehouse_weight>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatWeight(item.warehouse_weight)"></span>
        </x-slot:warehouse_weight>

        <x-slot:total_weight>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatWeight(item.total_weight)"></span>
        </x-slot:total_weight>

        <x-slot:action>
            <div class="flex items-center gap-2">
                @can('receiving.read')<a x-show="item.receipt" :href="`{{ url('penerimaan-barang') }}/${item.receipt?.id}`" @click.prevent="$dispatch('warehouse-detail', { url: $el.href })" class="text-sm text-brand-500">Lihat Barang</a>@endcan
                <span x-show="!item.receipt" class="text-sm text-gray-500">Riwayat panen</span>
            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.transaction.harvest._harvest-modal')
    <x-warehouse-detail-modal />
@endsection
