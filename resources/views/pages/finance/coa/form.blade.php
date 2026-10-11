@extends('layouts.app')
@section('title', $account->exists ? 'Edit Akun COA' : 'Tambah Akun COA')
@section('breadcrumb')
    <x-breadcrumb :pageTitle="$account->exists ? 'Edit Akun COA' : 'Tambah Akun COA'" :links="[['name' => 'Keuangan', 'url' => '#'], ['name' => 'COA', 'url' => route('coa.index')]]" />
@endsection
@section('content')
    <form method="POST" action="{{ $account->exists ? route('coa.update', $account) : route('coa.store') }}" class="max-w-3xl space-y-5 rounded-xl border border-gray-200 bg-white p-6 text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
        @csrf
        @if ($account->exists) @method('PUT') @endif
        @if ($errors->any())
            <ul class="list-inside list-disc rounded-lg bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <label class="block text-sm">Kode Akun
                <input name="code" required maxlength="5" pattern="[1-5][0-9]{4}" value="{{ old('code', $account->code) }}" placeholder="Contoh: 11201" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">
            </label>
            <label class="block text-sm">Kategori
                <select name="category" required class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                    @foreach ($categories as $category => $prefix)
                        <option value="{{ $category }}" @selected(old('category', $account->category) === $category)>{{ $category }} ({{ $prefix }}xxxx)</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Nama Akun
                <input name="name" required maxlength="150" value="{{ old('name', $account->name) }}" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">
            </label>
            <label class="block text-sm">Subkelompok
                <input name="account_group" required maxlength="100" value="{{ old('account_group', $account->account_group) }}" placeholder="Contoh: Piutang" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">
            </label>
            <label class="block text-sm">Saldo Normal
                <select name="normal_balance" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                    @foreach (['Debit', 'Kredit'] as $balance)<option @selected(old('normal_balance', $account->normal_balance) === $balance)>{{ $balance }}</option>@endforeach
                </select>
            </label>
            <label class="block text-sm">Status
                <select name="is_active" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                    <option value="1" @selected(old('is_active', $account->is_active))>Aktif</option>
                    <option value="0" @selected(!old('is_active', $account->is_active))>Nonaktif</option>
                </select>
            </label>
        </div>
        <x-info-help><p class="text-sm text-gray-500">Kode terdiri dari 5 digit sesuai kategori. Harta dan Biaya umumnya Debit; Utang, Modal, dan Pendapatan umumnya Kredit. Akun pengurang memiliki saldo normal berlawanan.</p></x-info-help>
        <label class="block text-sm">Keterangan Penggunaan
            <textarea name="description" maxlength="2000" rows="3" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">{{ old('description', $account->description) }}</textarea>
        </label>
        <div class="flex gap-3">
            <button class="rounded-lg bg-brand-500 px-5 py-2 text-sm text-white">Simpan Akun</button>
            <a href="{{ route('coa.index') }}" class="rounded-lg border border-gray-300 px-5 py-2 text-sm dark:border-gray-700">Batal</a>
        </div>
    </form>
@endsection
