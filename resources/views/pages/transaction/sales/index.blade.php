@extends('layouts.app')

@section('title', 'Manajemen Penjualan')

@section('breadcrumb')
    <x-breadcrumb 
        pageTitle="Manajemen Penjualan" 
        :links="[
            ['name' => 'Transaksi', 'url' => '#'],
            ['name' => 'Manajemen Penjualan', 'url' => route('manajemen-penjualan.index')]
        ]" 
    />
@endsection

@section('content')
    <x-data-table 
        id="salesTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('manajemen-penjualan.data')" 
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
                Add Data
            </button>
        </x-slot:headerAction>

        <x-slot:date>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(new Date(item.sale_date))"></span>
        </x-slot:date>

        <x-slot:currency>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="'Rp ' + Number(item.price_per_kg).toLocaleString('id-ID')"></span>
        </x-slot:currency>

        <x-slot:total_sale_value>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="'Rp ' + Number(item.total_sale_value).toLocaleString('id-ID')"></span>
        </x-slot:total_sale_value>
    </x-data-table>

    @include('pages.transaction.sales._sales-modal')
@endsection
