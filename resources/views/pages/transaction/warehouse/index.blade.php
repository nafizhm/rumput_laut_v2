@extends('layouts.app')

@section('title', $pageTitle ?? 'Manajemen Gudang')

@section('breadcrumb')
    <x-breadcrumb :pageTitle="$pageTitle ?? 'Manajemen Gudang'" :links="[
        ['name' => 'Manajemen Gudang', 'url' => '#'],
        ['name' => $pageTitle ?? 'Manajemen Gudang', 'url' => route('manajemen-gudang.index')],
    ]" />
@endsection

@section('content')
    <x-data-table id="movementTable" :rows="$data" :columns="$columns" :url="$inventoryId
        ? route('manajemen-gudang.data', ['inventory_id' => $inventoryId])
        : route('manajemen-gudang.data')">
        <x-slot:headerAction>
            <div class="flex flex-wrap gap-2">
                @if ($showActions)
                    @can('receiving.create')
                    <a href="{{ route('penerimaan-barang.create') }}"
                        class="flex items-center gap-2 rounded-lg bg-white border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-theme-xs transition-colors dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Penerimaan Barang
                    </a>
                    @endcan
                    @can('manajemen-gudang.update')
                    <button type="button" @click="$dispatch('open-transfer-modal')"
                        class="flex items-center gap-2 rounded-lg bg-white border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-theme-xs transition-colors dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7 16l-4-4m0 0l4-4m-4 4h18" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Transfer Stok
                    </button>
                    <button type="button" @click="$dispatch('open-adjustment-modal')"
                        class="flex items-center gap-2 rounded-lg bg-white border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-theme-xs transition-colors dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/3">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-diff-icon lucide-diff">
                            <path d="M12 3v14" />
                            <path d="M5 10h14" />
                            <path d="M5 21h14" />
                        </svg>
                        Penyesuaian
                    </button>
                    @endcan
                @endif
            </div>
        </x-slot:headerAction>

        <x-slot:date>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium"
                x-text="formatDisplayDate(item.movement_date)"></span>
        </x-slot:date>

        <x-slot:type_badge>
            <span
                class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20 uppercase"
                x-text="item.type">
            </span>
        </x-slot:type_badge>

        <x-slot:direction_badge>
            <template x-if="item.direction === 'in'">
                <span
                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20 uppercase">
                    In
                </span>
            </template>
            <template x-if="item.direction === 'out'">
                <span
                    class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20 uppercase">
                    Out
                </span>
            </template>
        </x-slot:direction_badge>

        <x-slot:weight>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatWeight(item.weight_kg)"></span>
        </x-slot:weight>

        <x-slot:balance_after_kg>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium"
                x-text="formatWeight(item.balance_after_kg)"></span>
        </x-slot:balance_after_kg>
    </x-data-table>

    @if ($showActions)
        @include('pages.transaction.warehouse._warehouse-modals')
    @endif
@endsection
