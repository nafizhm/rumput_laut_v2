@extends('layouts.app')
@section('title', $grade->exists ? 'Edit Kualitas Barang' : 'Tambah Kualitas Barang')
@section('breadcrumb')
    <x-breadcrumb :pageTitle="$grade->exists ? 'Edit Kualitas Barang' : 'Tambah Kualitas Barang'" :links="[['name' => 'Master', 'url' => '#'], ['name' => 'Kualitas Barang', 'url' => route('kualitas-barang.index')]]" />
@endsection
@section('content')
    <form method="POST" action="{{ $grade->exists ? route('kualitas-barang.update', $grade) : route('kualitas-barang.store') }}" class="max-w-3xl space-y-5 rounded-xl border border-gray-200 bg-white p-6 text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
        @csrf
        @if ($grade->exists) @method('PUT') @endif
        @if ($errors->any())
            <ul class="list-inside list-disc rounded-lg bg-red-50 p-4 text-sm text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <label class="block text-sm">Kode Kualitas
                <input name="code" required maxlength="20" value="{{ old('code', $grade->code) }}" placeholder="Contoh: Q06" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">
            </label>
            <label class="block text-sm">Nama Kualitas
                <input name="name" required maxlength="100" value="{{ old('name', $grade->name) }}" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">
            </label>
            <label class="block text-sm">Urutan Tampilan
                <input name="sort_order" type="number" required min="0" max="9999" value="{{ old('sort_order', $grade->sort_order) }}" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">
            </label>
            <label class="block text-sm">Status
                <select name="is_active" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                    <option value="1" @selected(old('is_active', $grade->is_active))>Aktif</option>
                    <option value="0" @selected(!old('is_active', $grade->is_active))>Nonaktif</option>
                </select>
            </label>
        </div>
        <p class="text-sm text-gray-500">Kode harus unik, menggunakan huruf, angka, tanda hubung atau garis bawah. Kualitas yang tidak dipakai lagi dapat dinonaktifkan.</p>
        <label class="block text-sm">Keterangan
            <textarea name="description" maxlength="2000" rows="3" class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">{{ old('description', $grade->description) }}</textarea>
        </label>
        <label class="block text-sm">Kriteria Penilaian
            <textarea name="criteria" maxlength="5000" rows="4" placeholder="Isi spesifikasi hasil kesepakatan dengan client." class="mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">{{ old('criteria', $grade->criteria) }}</textarea>
        </label>
        <p class="text-sm text-gray-500">Kriteria dapat mencakup kadar air, kebersihan, kondisi barang, atau spesifikasi pembeli. Batas penilaian tidak diisi otomatis.</p>
        <div class="flex gap-3">
            <button class="rounded-lg bg-brand-500 px-5 py-2 text-sm text-white">Simpan Kualitas</button>
            <a href="{{ route('kualitas-barang.index') }}" class="rounded-lg border border-gray-300 px-5 py-2 text-sm dark:border-gray-700">Batal</a>
        </div>
    </form>
@endsection
