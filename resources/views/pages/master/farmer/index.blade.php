@extends('layouts.app')

@section('title', 'Manajemen Petani')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Manajemen Petani" :links="[['name' => 'Manajemen Petani', 'url' => route('manajemen-petani.index')]]" />
@endsection

@section('content')
    <x-data-table id="farmerTable" :rows="$data" :columns="$columns" :url="route('farmer-data')">
        
        <x-slot:headerAction>
            <button type="button" @click="$dispatch('create-farmer')"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto hover:bg-gray-50 dark:hover:bg-white/3 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-plus-icon lucide-plus">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                Tambah Data
            </button>
        </x-slot:headerAction>

        <x-slot:currency>
            <span class="text-right tabular-nums text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="'Rp ' + Number(item.current_debt).toLocaleString('id-ID')"></span>
        </x-slot:currency>

        <x-slot:action>
            <div class="flex items-center gap-2">
                @can('receiving.read')<a :href="`{{ route('penerimaan-barang.index') }}?farmer_id=${item.id}`" class="text-sm text-brand-500">Penerimaan / Kualitas</a>@endcan
                <button type="button" @click="$dispatch('detail-farmer', item)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 transition-colors rounded-lg hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                    Detail
                </button>
                <button type="button" @click="$dispatch('edit-farmer', item)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600">
                    Edit
                </button>
                <button type="button" @click="$dispatch('delete-farmer', item.id)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-rose-500 hover:bg-rose-600">
                    Delete
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.master.farmer._farmer-modal')
    @include('pages.master.farmer._farmer-detail-modal')
@endsection
