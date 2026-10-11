@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('breadcrumb')
    <x-breadcrumb pageTitle="Manajemen Pengguna" :links="[['name' => 'Setting', 'url' => '#']]" />
@endsection
@section('content')
    @if(session('success'))
        <p role="status" class="mb-5 rounded-lg bg-green-50 p-4 text-green-700">{{ session('success') }}</p>
    @endif
    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-6 flex items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Daftar Pengguna</h2>
            @can('users.create')
                <a href="{{ route('users.create') }}" class="rounded-lg bg-brand-500 px-4 py-3 text-sm text-white">Tambah Pengguna</a>
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                <thead><tr class="border-b border-gray-200 dark:border-gray-800">
                    <th class="p-3">Nama</th><th class="p-3">Username</th><th class="p-3">Email</th><th class="p-3">Role</th><th class="p-3">Aksi</th>
                </tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="p-3">{{ $user->name }}</td><td class="p-3">{{ $user->username }}</td><td class="p-3">{{ $user->email }}</td>
                            <td class="p-3">{{ $user->roles->pluck('name')->join(', ') ?: 'Tanpa role' }}</td>
                            <td class="p-3">
                                @can('users.update')
                                    @if((!$user->hasRole('superadmin') || auth()->user()->hasRole('superadmin')) && (auth()->user()->hasRole('superadmin') || $user->getAllPermissions()->pluck('id')->diff(auth()->user()->getAllPermissions()->pluck('id'))->isEmpty()))
                                        <a class="text-brand-500" href="{{ route('users.edit', $user) }}">Edit & Hak Akses</a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center">Belum ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $users->links() }}</div>
    </div>
@endsection
