@extends('layouts.app')

@section('title', 'Manajemen Kategori Pengeluaran')

@section('breadcrumb')
    <x-breadcrumb 
        pageTitle="Manajemen Kategori Pengeluaran" 
        :links="[
            ['name' => 'Manajemen Kategori Pengeluaran', 'url' => route('manajemen-kategori-pengeluaran.index')]
        ]" 
    />
@endsection

@section('content')
    <x-data-table 
        id="expenseCategoryTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('expense-category-data')" 
    >
        <x-slot:headerAction>
            <button 
                type="button" 
                @click="$dispatch('create-entity')" 
                class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Add Data
            </button>
        </x-slot:headerAction>

        <x-slot:statusBadge>
            <template x-if="item.is_active">
                <span class="bg-green-50 text-green-700 ring-1 ring-green-600/20 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium">Aktif</span>
            </template>
            <template x-if="!item.is_active">
                <span class="bg-red-50 text-red-700 ring-1 ring-red-600/20 inline-flex items-center rounded-md px-2 py-1 text-xs font-medium">Non-Aktif</span>
            </template>
        </x-slot:statusBadge>

        <x-slot:action>
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="$dispatch('edit-entity', item)" 
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-600"
                >
                    Edit
                </button>

                <button 
                    type="button" 
                    @click="$dispatch('delete-entity', item.id)" 
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-rose-600"
                >
                    Delete
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.master.expense-category._category-modal')
@endsection
