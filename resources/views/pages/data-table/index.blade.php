@extends('layouts.app')

@section('title', 'Data Users')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Data Users" :links="[['name' => 'Users', 'url' => route('users.index')]]" />
@endsection

@section('content')
    <x-data-table :rows="$data" :columns="$columns">
        <x-slot:headerAction>
            <button class="btn-primary">Tambah User</button>
        </x-slot:headerAction>

        <x-slot:status>
            <div :class="{
                'bg-green-500/10 text-green-500': item.status === 'Active',
                'bg-red-500/10 text-red-500': item.status === 'Inactive'
            }"
                class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium">
                <span x-text="item.status"></span>
            </div>
        </x-slot:status>

        <x-slot:action>
            <button>Edit</button>
        </x-slot:action>
    </x-data-table>
@endsection
