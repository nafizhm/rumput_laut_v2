@extends('layouts.app')

@section('title', 'Manajemen Role')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Manajemen Role" :links="[['name' => 'Role', 'url' => route('manajemen-role.index')]]" />
@endsection

@section('content')
    <x-data-table id="rolesTable" :rows="$data" :columns="$columns" :url="route('role-data')">
        <x-slot:headerAction>
            <button type="button" @click="$dispatch('create-role')"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto hover:bg-gray-50 dark:hover:bg-white/3 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-plus-icon lucide-plus">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                Tambah Role
            </button>
        </x-slot:headerAction>

        <x-slot:permission>
            <div class="space-y-2 text-sm">
                <template x-for="(actions, menu) in item.permission_grouped" :key="menu">
                    <div>
                        <div class="font-medium capitalize mb-1 dark:text-gray-400" x-text="menu"></div>

                        <div class="flex flex-wrap gap-1">
                            <template x-for="action in actions" :key="action">
                                <span
                                    class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium
                                   text-gray-600 ring-1 ring-inset ring-gray-500/10
                                   dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20"
                                    x-text="action.charAt(0).toUpperCase() + action.slice(1)">
                                </span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </x-slot:permission>
        <x-slot:action>
            <div class="flex items-center gap-2">
                <button type="button" @click="$dispatch('edit-role', item)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600">
                    Edit
                </button>
                <button type="button" @click="$dispatch('delete-role', item.id)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-rose-500 hover:bg-rose-600">
                    Delete
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.settings.role._role-modal')
@endsection
