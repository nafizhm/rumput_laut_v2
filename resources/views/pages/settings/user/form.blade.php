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
        <x-form-section title="Role" description="Pilih jenis pengguna. Hak akses menu mengikuti centang di bawah; superadmin mengikuti seluruh akses role.">
            @forelse($roles as $role)
                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="mt-1 rounded" @checked(in_array($role->id, old('roles', $errors->any() ? [] : $user->roles->pluck('id')->all()))) />
                    <span>{{ $role->name }}</span>
                </label>
            @empty
                <p class="text-sm text-gray-500">Belum ada role yang dapat diberikan.</p>
            @endforelse
        </x-form-section>
        <section class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900" x-data="{ enabledParents: @js($enabledParents), visible(parents) { return parents.every(id => this.enabledParents.includes(id)); } }">
            <input type="hidden" name="menu_hierarchy" value="1">
            <div class="p-5"><h3 class="font-semibold text-gray-800 dark:text-white">Hak Akses Menu</h3><p class="mt-1 text-sm text-gray-500">Pengguna hanya mendapat akses yang dicentang. Hilangkan centang untuk mencabut akses.</p></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-gray-700 dark:text-gray-300">
                    <thead class="border-y bg-gray-50 dark:border-gray-700 dark:bg-gray-800"><tr><th class="px-5 py-3 text-left">Nama Menu</th>@foreach(['read'=>'Lihat', 'create'=>'Tambah', 'update'=>'Edit', 'delete'=>'Hapus'] as $heading)<th class="px-4 py-3 text-center">{{ $heading }}</th>@endforeach</tr></thead>
                    <tbody>
                    @forelse($permissionRows as $row)
                        <tr x-show="visible(@js($row['parents']))" @if($row['parents']) x-cloak @endif @class(['border-b border-gray-100 dark:border-gray-800', 'bg-blue-50 font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-200' => $row['is_parent']])>
                            <td class="py-3 pr-5" style="padding-left: {{ 20 + $row['level'] * 24 }}px">{{ $row['label'] }}</td>
                            @foreach(['read'=>'Lihat', 'create'=>'Tambah', 'update'=>'Edit', 'delete'=>'Hapus'] as $action=>$label)
                                <td class="px-4 py-3 text-center">
                                    @if(isset($row['parent_key']) && $action === 'read')
                                        <input type="checkbox" name="enabled_parents[]" value="{{ $row['parent_key'] }}" x-model="enabledParents" :disabled="!visible(@js($row['parents']))" aria-label="{{ $row['label'] }}: Lihat" class="rounded border-gray-300 text-brand-500" @checked(in_array($row['parent_key'], $enabledParents, true)) />
                                    @elseif(isset($row['cells'][$action]))
                                        @php($permission = $row['cells'][$action])
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" :disabled="!visible(@js($row['parents']))" aria-label="{{ $row['label'] }}: {{ $label }}" class="rounded border-gray-300 text-brand-500" @checked(in_array($permission->id, old('permissions', $errors->any() ? [] : $user->getAllPermissions()->pluck('id')->all()))) />
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-5 text-center text-gray-500">Belum ada hak akses yang dapat diberikan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div class="flex justify-end gap-3 pb-6">
            <a href="{{ route('users.index') }}" class="rounded-lg border border-gray-300 px-5 py-3 text-sm text-gray-700 dark:text-gray-300">Batal</a>
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-3 text-sm text-white">Simpan Pengguna</button>
        </div>
    </form>
@endsection
