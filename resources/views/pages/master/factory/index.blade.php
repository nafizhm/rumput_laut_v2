@extends('layouts.app')

@section('title', 'Manajemen Pabrik')

@section('breadcrumb')
    <x-breadcrumb 
        pageTitle="Manajemen Pabrik" 
        :links="[
            ['name' => 'Master Data', 'url' => '#'],
            ['name' => 'Manajemen Pabrik', 'url' => route('manajemen-pabrik.index')]
        ]" 
    />
@endsection

@section('content')
    <x-data-table 
        id="factoryTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('manajemen-pabrik.data')" 
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

    @include('pages.master.factory._factory-modal')
@endsection
