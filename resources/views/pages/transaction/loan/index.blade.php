@extends('layouts.app')

@section('title', 'Manajemen Pinjaman')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Manajemen Pinjaman" :links="[['name' => 'Keuangan', 'url' => '#'], ['name' => 'Manajemen Pinjaman', 'url' => route('manajemen-pinjaman.index')]]" />
@endsection

@section('content')
    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-5 text-sm leading-6 text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
        <p>Pinjaman menambah piutang kepada petani dan mengurangi Kas Operasional. Nilainya masuk Neraca sebagai piutang. Pengembalian tunai dicatat melalui pembayaran utang pada detail petani; potongan hasil panen dicatat melalui Pembelian &amp; Pembayaran Panen.</p>
        <p class="mt-2">Tabel ini menampilkan riwayat pemberian pinjaman. Saldo pinjaman yang masih harus dibayar tersedia pada detail petani.</p>
        @can('manajemen-petani.read')<a href="{{ route('manajemen-petani.index') }}" class="mt-2 inline-block font-semibold text-brand-600 dark:text-brand-300">Buka saldo dan pembayaran petani</a>@endcan
    </div>
    <x-data-table id="loanTable" :rows="$data" :columns="$columns" :url="route('loan-data')">
        
        <x-slot:headerAction>
            
@can('manajemen-pinjaman.create')
<button type="button" @click="$dispatch('create-loan')"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto hover:bg-gray-50 dark:hover:bg-white/3 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-plus-icon lucide-plus">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                Tambah Data
            </button>
@endcan

        </x-slot:headerAction>

        <x-slot:longDate>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatDisplayDate(item.loan_date)"></span>
        </x-slot:longDate>

        <x-slot:currency>
            <span class="text-right tabular-nums text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="'Rp ' + Number(item.amount).toLocaleString('id-ID')"></span>
        </x-slot:currency>

        <x-slot:action>
            <div class="flex items-center gap-2">
                
@can('manajemen-pinjaman.update')
<button type="button" @click="$dispatch('edit-loan', item)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600">
                    Edit
                </button>
@endcan

                
@can('manajemen-pinjaman.delete')
<button type="button" @click="$dispatch('delete-loan', item.id)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-rose-500 hover:bg-rose-600">
                    Delete
                </button>
@endcan

            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.transaction.loan._loan-modal')
@endsection
