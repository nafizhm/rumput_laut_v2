@extends('layouts.app')
@section('title', $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('breadcrumb')
    <x-breadcrumb :pageTitle="$user->exists ? 'Edit Pengguna' : 'Tambah Pengguna'" :links="[['name' => 'Setting', 'url' => '#'], ['name' => 'Manajemen Pengguna', 'url' => route('users.index')]]" />
@endsection
@section('content')
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="mx-auto max-w-5xl">
        @csrf
        @if($user->exists) @method('PUT') @endif
        @if($errors->any())
            <div role="alert" class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        @endif
        <x-form-section title="Identitas Pengguna" description="Username dan password digunakan untuk login.">
            @foreach(['name' => 'Nama', 'username' => 'Username', 'email' => 'Email'] as $field => $label)
                <label class="block text-sm text-gray-700 dark:text-gray-300">
                    {{ $label }}
                    <input name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $user->$field) }}" required maxlength="255" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 dark:border-gray-700" />
                </label>
            @endforeach
            <div></div>
            <label class="block text-sm text-gray-700 dark:text-gray-300">
                Password {{ $user->exists ? '(kosongkan jika tidak diubah)' : '' }}
                <input name="password" type="password" autocomplete="new-password" minlength="8" @required(!$user->exists) class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 dark:border-gray-700" />
            </label>
            <label class="block text-sm text-gray-700 dark:text-gray-300">
                Konfirmasi Password
                <input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" @required(!$user->exists) class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 dark:border-gray-700" />
            </label>
        </x-form-section>
        <x-form-section title="Role" description="Hak akses dari role berlaku otomatis. Role yang tersedia sesuai hak akses Anda.">
            @forelse($roles as $role)
                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="mt-1 rounded" @checked(in_array($role->id, old('roles', $errors->any() ? [] : $user->roles->pluck('id')->all()))) />
                    <span><strong>{{ $role->name }}</strong><span class="mt-1 block text-xs text-gray-500">{{ $role->permissions->pluck('name')->join(', ') ?: 'Belum memiliki hak akses' }}</span></span>
                </label>
            @empty
                <p class="text-sm text-gray-500">Belum ada role yang dapat diberikan.</p>
            @endforelse
        </x-form-section>
        <x-form-section title="Hak Akses Tambahan" description="Izin ini ditambahkan ke hak akses role. Untuk mencabut izin dari role, ubah pilihan role pengguna.">
            @foreach($permissions->groupBy(fn($permission) => explode('.', $permission->name)[0]) as $group => $items)
                <fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <legend class="px-2 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $group }}</legend>
                    @foreach($items as $permission)
                        <label class="mb-2 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="rounded" @checked(in_array($permission->id, old('permissions', $errors->any() ? [] : $user->permissions->pluck('id')->all()))) />
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </fieldset>
            @endforeach
        </x-form-section>
        <div class="flex justify-end gap-3 pb-6">
            <a href="{{ route('users.index') }}" class="rounded-lg border border-gray-300 px-5 py-3 text-sm text-gray-700 dark:text-gray-300">Batal</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-3 text-sm text-white">Simpan Pengguna</button>
        </div>
    </form>
@endsection
