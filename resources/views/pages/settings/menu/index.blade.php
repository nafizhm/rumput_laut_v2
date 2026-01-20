@extends('layouts.app')

@section('title', 'Manajemen Menu')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Manajemen Menu" :links="[['name' => 'Menu', 'url' => route('manajemen-menu.index')]]" />
@endsection

@section('content')
    {{-- <div class="mb-4 flex justify-end">
        <button onclick="refreshDataTable('usersTable');"
            class="flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="ml-2">Refresh All Tables</span>
        </button>
    </div> --}}

    <x-data-table id="usersTable" :rows="$data" :columns="$columns" :url="route('menu-data')">
        <x-slot:headerAction>
            <button type="button" @click="$dispatch('create-menu')"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto hover:bg-gray-50 dark:hover:bg-white/3 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-plus-icon lucide-plus">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                Tambah Menu
            </button>
        </x-slot:headerAction>

        <x-slot:status>
            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                :class="item.is_active ?
                    'bg-green-50 text-green-600 ring-green-500/10 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20' :
                    'bg-red-50 text-red-600 ring-red-500/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20'"
                x-text="item.is_active ? 'Active' : 'Inactive'">
            </span>
        </x-slot:status>
        <x-slot:parent_name>
            <span x-text="item.parent?.name || '-'"></span>
        </x-slot:parent_name>
        <x-slot:action>
            <div class="flex items-center gap-2">
                <button type="button" @click="$dispatch('edit-menu', item)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600">
                    Edit
                </button>
                <button type="button" @click="$dispatch('delete-menu', item.id)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-rose-500 hover:bg-rose-600">
                    Delete
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.settings.menu._menu-modal')
@endsection
