@extends('layouts.app')

@section('title', 'Invoice')

@section('breadcrumb')
    <x-breadcrumb 
        pageTitle="Invoice" 
        :links="[
            ['name' => 'Penjualan', 'url' => '#'],
            ['name' => 'Invoice', 'url' => route('invoice.index')]
        ]" 
    />
@endsection

@section('content')
    <p class="mb-4 rounded-lg bg-white p-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-400">Catat penjualan terlebih dahulu di Penjualan Langsung, lalu pilih nomor penjualan saat membuat invoice. Invoice tidak mengurangi stok lagi. Invoice lama yang belum terhubung ditandai pada daftar.</p>
    <x-data-table 
        id="invoiceTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('invoice.data')" 
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
                Tambah Invoice
            </button>
        </x-slot:headerAction>

        <x-slot:date><span x-text="formatDisplayDate(item.invoice_date)"></span></x-slot:date>
        <x-slot:sale><span x-text="item.sale?.invoice_number || 'Belum terhubung (data lama)'"></span></x-slot:sale>
        <x-slot:payment>
            <span class="rounded-full px-3 py-1 text-xs font-medium"
                :class="item.payment_status === 'paid' ? 'bg-green-100 text-green-700' : (item.payment_status === 'partial' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700')"
                x-text="item.payment_status_label"></span>
        </x-slot:payment>
        <x-slot:total>
            <div class="text-right text-sm">
                <div class="text-right tabular-nums font-medium text-gray-900 dark:text-gray-300" x-text="Number(item.total_amount).toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})"></div>
                <div class="text-xs text-gray-500" x-show="item.paid_amount > 0" x-text="'Dibayar: Rp ' + Number(item.paid_amount).toLocaleString('id-ID')"></div>
                <div class="text-xs text-gray-500" x-show="item.remaining_amount > 0" x-text="'Sisa: Rp ' + Number(item.remaining_amount).toLocaleString('id-ID')"></div>
            </div>
        </x-slot:total>
        <x-slot:due><div><span x-text="formatDisplayDate(item.due_date)"></span><p class="text-xs" :class="item.payment_status !== 'paid' && item.days_until_due < 0 ? 'text-rose-600' : 'text-gray-500'" x-text="item.due_label"></p><p class="text-xs text-gray-500" x-text="'Termin ' + item.term_days + ' hari'"></p></div></x-slot:due>
        <x-slot:action>
            <div class="flex items-center gap-2">
                <button type="button" x-show="item.payment_status !== 'paid'" @click="$dispatch('open-invoice-payment', item)" class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white">Bayar</button>
                <a :href="`{{ url('invoice') }}/${item.id}/cetak`" target="_blank" rel="noopener"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Cetak</a>
                <button 
                    type="button" 
                    @click="$dispatch('edit-entity', item)" 
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-brand-600"
                >
                    Edit
                </button>

                <button 
                    type="button" 
                    @click="$dispatch('delete-entity', item.id)" 
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-rose-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-rose-600"
                >
                    Hapus
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

    @include('pages.sales.invoice._modal')
    @include('pages.sales.invoice._payment-modal')
@endsection
