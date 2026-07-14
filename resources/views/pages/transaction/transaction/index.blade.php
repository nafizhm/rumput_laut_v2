@extends('layouts.app')

@section('title', 'Pemasukan & Pengeluaran')

@section('breadcrumb')
    <x-breadcrumb 
        pageTitle="Pemasukan & Pengeluaran" 
        :links="[
            ['name' => 'Pemasukan & Pengeluaran', 'url' => route('manajemen-pemasukan-pengeluaran.index')]
        ]" 
    />
@endsection

@section('content')
    <x-data-table 
        id="transactionTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('transaction-data')" 
    >
        <x-slot:headerAction>
            <button 
                type="button" 
                @click="$dispatch('create-entity')" 
                class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Tambah Data
            </button>
        </x-slot:headerAction>

        <x-slot:date>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatDisplayDate(item.date)"></span>
        </x-slot:date>

        <x-slot:typeBadge>
            <span x-show="item.type === 'income'"
                class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                Pemasukan
            </span>
            <span x-show="item.type === 'expense'"
                class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20">
                Pengeluaran
            </span>
        </x-slot:typeBadge>

        <x-slot:amount>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="'Rp ' + Number(item.amount).toLocaleString('id-ID')"></span>
        </x-slot:amount>

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

    @include('pages.transaction.transaction._transaction-modal')
@endsection
