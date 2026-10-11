@extends('layouts.app')
@section('title', 'Kualitas Barang')
@section('breadcrumb')
    <x-breadcrumb pageTitle="Kualitas Barang" :links="[['name' => 'Master', 'url' => '#'], ['name' => 'Kualitas Barang', 'url' => route('kualitas-barang.index')]]" />
@endsection
@section('content')
    <div class="rounded-xl border border-gray-200 bg-white text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <x-info-help><p class="mb-2 text-sm text-gray-500">Daftar klasifikasi kualitas barang rumput laut. Kualitas dicatat pada barang/batch, bukan ditetapkan permanen pada petani.</p>
<p class="mb-5 text-sm text-gray-500">Isi kriteria setelah disepakati dengan client. Area penyimpanan dan status proses dicatat terpisah. Penandaan Reject tidak otomatis mengurangi stok fisik.</p></x-info-help>
            <form method="GET" action="{{ route('kualitas-barang.index') }}" class="flex flex-wrap items-center gap-3">
                <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Cari kode atau nama kualitas" aria-label="Cari kualitas" class="rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                <select name="status" aria-label="Status kualitas" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
                <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white">Cari</button>
                <a href="{{ route('kualitas-barang.index') }}" class="text-sm text-gray-500">Reset</a>
                @can('quality.create')
                    <a href="{{ route('kualitas-barang.create') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white sm:ml-auto">Tambah Kualitas</a>
                @endcan
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-500 dark:bg-gray-800"><tr>
                    @foreach (['Kode', 'Nama Kualitas', 'Keterangan', 'Kriteria Penilaian', 'Urutan', 'Status', 'Aksi'] as $heading)
                        <th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($grades as $grade)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-4 py-3 font-mono font-semibold text-brand-500">{{ $grade->code }}</td>
                            <td class="px-4 py-3 font-medium">{{ $grade->name }}</td>
                            <td class="min-w-64 whitespace-pre-line px-4 py-3 text-gray-500">{{ $grade->description }}</td>
                            <td class="min-w-64 whitespace-pre-line px-4 py-3 text-gray-500">{{ $grade->criteria ?: 'Belum ditetapkan' }}</td>
                            <td class="px-4 py-3">{{ $grade->sort_order }}</td>
                            <td class="px-4 py-3">{{ $grade->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-4 py-3">@can('quality.update')<a href="{{ route('kualitas-barang.edit', $grade) }}" class="text-brand-500">Edit</a>@endcan</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-500">Tidak ada kualitas yang sesuai pencarian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="p-4 text-sm text-gray-500">{{ $grades->count() }} kualitas ditampilkan.</p>
    </div>
@endsection
