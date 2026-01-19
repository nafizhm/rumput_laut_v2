@extends('layouts.app')

@section('title', 'Manajemen Menu')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Manajemen Permission" :links="[['name' => 'Permission', 'url' => route('manajemen-permission.index')]]" />
@endsection

@section('content')
    <x-data-table id="usersTable" :rows="$data" :columns="$columns" :url="route('permission-data.index')">
        <x-slot:headerAction>
            <button type="button" @click="$dispatch('create-permission')"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto hover:bg-gray-50 dark:hover:bg-white/3 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-plus-icon lucide-plus">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                Tambah Permission
            </button>
        </x-slot:headerAction>

        <x-slot:status>
            <div
                :class="{
                    'bg-success-50 text-theme-xs text-success-700 dark:bg-success-500/15 dark:text-success-500 inline-block rounded-full px-2 py-0.5 font-medium': item
                        .is_active,
                    'bg-error-50 text-theme-xs text-error-700 dark:bg-error-500/15 dark:text-error-500 inline-block rounded-full px-2 py-0.5 font-medium':
                        !item.is_active
                }">
                <span x-text="item.is_active ? 'Active' : 'Inactive'"></span>
            </div>
        </x-slot:status>
        <x-slot:parent_name>
            <span x-text="item.parent?.name || '-'"></span>
        </x-slot:parent_name>
        <x-slot:action>
            <div class="flex items-center gap-2">
                <button type="button" @click="$dispatch('edit-permission', item)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600">
                    Edit
                </button>
                <button type="button" @click="$dispatch('delete-permission', item.id)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-rose-500 hover:bg-rose-600">
                    Delete
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

    {{-- @include('pages.settings.permission._permission_modal') --}}
@endsection
