@extends('layouts.app')

@section('title', 'Penjualan Langsung')

@section('breadcrumb')
    <x-breadcrumb 
        pageTitle="Penjualan Langsung" 
        :links="[
            ['name' => 'Penjualan', 'url' => '#'],
            ['name' => 'Penjualan Langsung', 'url' => route('manajemen-penjualan.index')]
        ]" 
    />
@endsection

@section('content')
    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
        <p>Untuk pengiriman, pilih stok keluar melalui Surat Jalan: stok baru berkurang saat Konfirmasi Dikirim. Untuk barang yang langsung diserahkan, pilih pengeluaran langsung. Invoice dan pembayaran tidak memotong stok.</p>
        <p class="mt-2">Setelah menerima uang, gunakan tombol Pelunasan untuk mencatat tanggal dan jumlah pembayaran. Tahap ini mendukung pelunasan penuh; cicilan akan ditambahkan pada tahap berikutnya.</p>
    </div>
    <x-data-table 
        id="salesTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('manajemen-penjualan.data')" 
    >
        <x-slot:headerAction>
            <button 
                type="button" 
                @click="$dispatch('create-entity')" 
                class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tambah Data
            </button>
        </x-slot:headerAction>

        <x-slot:date>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatDisplayDate(item.sale_date)"></span>
        </x-slot:date>
        <x-slot:stock_mode><span x-text="item.stock_mode === 'delivery_note' ? 'Melalui Surat Jalan' : 'Langsung / Alur Lama'"></span></x-slot:stock_mode>

        <x-slot:weight>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatWeight(item.total_weight_kg)"></span>
        </x-slot:weight>

        <x-slot:currency>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatCurrency(item.price_per_kg)"></span>
        </x-slot:currency>

        <x-slot:total_sale_value>
            <span class="text-gray-900 dark:text-gray-400 text-sm font-medium" x-text="formatCurrency(item.total_sale_value)"></span>
        </x-slot:total_sale_value>

        <x-slot:payment_status>
            <span
                :class="item.payment_status === 'paid'
                    ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400'
                    : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400'"
                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                x-text="item.payment_status_label">
            </span>
        </x-slot:payment_status>

        <x-slot:paid_amount>
            <span x-text="item.paid_amount == null ? (item.payment_status === 'paid' ? 'Belum tercatat' : formatCurrency(0)) : formatCurrency(item.paid_amount)"></span>
        </x-slot:paid_amount>
        <x-slot:remaining_amount><span x-text="formatCurrency(item.remaining_amount)"></span></x-slot:remaining_amount>
        <x-slot:payment_date><span x-text="item.payment_date ? formatDisplayDate(item.payment_date) : '-'"></span></x-slot:payment_date>

        <x-slot:action>
            <div class="flex items-center gap-2">
                <template x-if="item.payment_status !== 'paid' && !item.invoice">
                    <button type="button" @click="$dispatch('open-pay-sale', item)"
                        class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-600">
                        Pelunasan
                    </button>
                </template>
                <template x-if="item.invoice">
                    <a href="{{ route('invoice.index') }}" class="text-sm text-brand-500" x-text="item.invoice.number"></a>
                </template>
                <template x-if="item.payment_status === 'paid' && item.payment_proof_url">
                    <a :href="item.payment_proof_url" target="_blank"
                        class="flex w-full items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        Bukti Bayar
                    </a>
                </template>
                <button type="button" x-show="!item.invoice" @click="$dispatch('delete-sale', item.id)"
                    class="flex items-center justify-center rounded-lg bg-rose-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-rose-600">
                    Hapus
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.transaction.sales._sales-modal')
@endsection
