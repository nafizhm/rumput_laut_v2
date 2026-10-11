@extends('layouts.app')

@section('title', 'COA - Chart of Accounts')
@section('breadcrumb')
    <x-breadcrumb pageTitle="COA - Chart of Accounts" :links="[['name' => 'Keuangan', 'url' => '#'], ['name' => 'COA', 'url' => route('coa.index')]]" />
@endsection

@section('content')
    <div class="space-y-6 text-gray-800 dark:text-gray-200">
        <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm leading-6 dark:border-gray-800 dark:bg-gray-900">
            <x-info-help label="Panduan memilih akun usaha rumput laut"><h2 class="font-semibold">Panduan memilih akun usaha rumput laut</h2>
            <p class="mt-2">COA adalah daftar akun, bukan tempat mengisi nominal. Isi nominal melalui Saldo Awal atau Jurnal Umum. Untuk setoran pemilik: debit Kas/Bank dan kredit akun Modal Pemilik.</p>
            <a href="{{ route('coa.index', ['q' => 'Modal Pemilik']) }}" class="mt-2 inline-block font-semibold text-brand-500 underline">Cari akun Modal Pemilik</a>
            <p class="mt-2">Alur nilai persediaan: bahan basah/kering → Dalam Proses (WIP) → Barang Jadi → HPP ketika terjual. Pemindahan tahap mengurangi akun asal dan menambah akun tujuan; barang yang sama tidak dicatat dua kali.</p>
            <p class="mt-2">Nilai persediaan memakai biaya, bukan kilogram atau harga jual. Biaya produksi yang dialokasikan ke persediaan jangan sekaligus dibebankan sebagai biaya operasional. Selisih berat saja belum menentukan kerugian persediaan.</p>
        </x-info-help>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $category => $prefix)
                <a href="{{ route('coa.index', ['category' => $category]) }}" class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="font-semibold">{{ $category }} <span class="text-brand-500">{{ $prefix }}xxxx</span></p>
                    <p class="mt-2 text-sm text-gray-500">{{ $counts[$category] ?? 0 }} akun</p>
                </a>
            @endforeach
        </div>
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-5 dark:border-gray-800">
                <x-info-help><p class="mb-4 text-sm text-gray-500">Daftar kode akun untuk pencatatan keuangan usaha rumput laut. Kode akun berbeda dari nomor dokumen transaksi (invoice, PO, atau batch panen).</p></x-info-help>
                <form method="GET" action="{{ route('coa.index') }}" class="flex flex-wrap items-center gap-3">
                    <input name="q" value="{{ request('q') }}" placeholder="Cari kode, nama, atau subkelompok" aria-label="Cari akun" class="rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm dark:border-gray-700">
                    <select name="category" aria-label="Kategori akun" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category => $prefix)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white">Cari</button>
                    <a href="{{ route('coa.index') }}" class="text-sm text-gray-500">Reset</a>
                    @can('coa.create')
                        <a href="{{ route('coa.create') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white sm:ml-auto">Tambah Akun</a>
                    @endcan
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-800"><tr>
                        @foreach (['Kode Akun', 'Nama Akun', 'Kategori', 'Subkelompok', 'Saldo Normal', 'Penggunaan', 'Status', 'Aksi'] as $heading)
                            <th class="px-4 py-3 whitespace-nowrap">{{ $heading }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="px-4 py-3 font-mono font-semibold text-brand-500">{{ $account->code }}</td>
                                <td class="px-4 py-3 font-medium">{{ $account->name }}</td>
                                <td class="px-4 py-3">{{ $account->category }}</td>
                                <td class="px-4 py-3">{{ $account->account_group }}</td>
                                <td class="px-4 py-3">{{ $account->normal_balance }}</td>
                                <td class="min-w-64 px-4 py-3 text-gray-500">{{ $account->description }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td class="px-4 py-3">@can('coa.update')<a class="text-brand-500" href="{{ route('coa.edit', $account) }}">Edit</a>@endcan</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-8 text-center text-gray-500">Tidak ada akun yang sesuai pencarian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="p-4 text-sm text-gray-500">{{ $accounts->count() }} akun ditampilkan.</p>
        </div>
    </div>
@endsection
