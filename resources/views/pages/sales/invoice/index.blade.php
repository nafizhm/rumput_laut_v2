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
    <x-info-help><p class="mb-4 rounded-lg bg-white p-4 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-400">Catat penjualan terlebih dahulu di Penjualan Langsung, lalu pilih nomor penjualan saat membuat invoice. Invoice tidak mengurangi stok lagi. Invoice lama yang belum terhubung ditandai pada daftar.</p></x-info-help>
    <x-data-table 
        id="invoiceTable" 
        :rows="$data" 
        :columns="$columns" 
        :url="route('invoice.data')" 
    >
        <x-slot:headerAction>
            
@can('invoice.create')
<a 
                href="{{ route('invoice.create') }}" 
                class="flex w-full sm:w-auto items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Tambah Invoice
            </a>
@endcan

        </x-slot:headerAction>

        <x-slot:document>
            <div class="flex min-w-0 flex-col items-start gap-1.5 text-sm">
                <span class="text-gray-500 dark:text-gray-400" x-text="formatDisplayDate(item.invoice_date)"></span>
                <span class="break-words font-semibold text-gray-800 dark:text-gray-200" x-text="item.number"></span>
                <span class="rounded-full px-3 py-1 text-xs font-medium"
                :class="item.payment_status === 'paid' ? 'bg-green-100 text-green-700' : (item.payment_status === 'partial' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700')"
                x-text="item.payment_status_label"></span>
            </div>
        </x-slot:document>
        <x-slot:customer>
            <div class="min-w-0 space-y-1 text-sm">
                <p class="break-words font-medium text-gray-800 dark:text-gray-200" x-text="item.customer_name"></p>
                <p class="break-words text-xs text-gray-500 dark:text-gray-400" x-text="item.sale?.sale_reference || item.sale?.invoice_number || 'Belum terhubung (data lama)'"></p>
            </div>
        </x-slot:customer>
        <x-slot:items>
            <div class="space-y-2 text-sm">
                <template x-for="product in item.document_items" :key="product.id">
                    <div><p class="font-medium text-gray-800 dark:text-gray-200" x-text="product.item_name"></p><p class="text-xs text-gray-500" x-text="formatWeight(product.quantity_kg)"></p></div>
                </template>
            </div>
        </x-slot:items>
        <x-slot:total>
            <div class="text-right text-sm">
                <div class="text-right tabular-nums font-medium text-gray-900 dark:text-gray-300" x-text="Number(item.total_amount).toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})"></div>
                <div class="text-xs text-gray-500" x-show="item.paid_amount > 0" x-text="'Dibayar: Rp ' + Number(item.paid_amount).toLocaleString('id-ID')"></div>
                <div class="text-xs text-gray-500" x-show="item.remaining_amount > 0" x-text="'Sisa: Rp ' + Number(item.remaining_amount).toLocaleString('id-ID')"></div>
            </div>
        </x-slot:total>
        <x-slot:due><div><span x-text="formatDisplayDate(item.due_date)"></span><p class="text-xs" :class="item.payment_status !== 'paid' && item.days_until_due < 0 ? 'text-rose-600' : 'text-gray-500'" x-text="item.due_label"></p><p class="text-xs text-gray-500" x-text="'Termin ' + item.term_days + ' hari'"></p></div></x-slot:due>
        <x-slot:action>
            <div class="flex flex-wrap items-center gap-1.5">
                
@can('invoice.update')
<button type="button" x-show="item.payment_status !== 'paid'" @click="$dispatch('open-invoice-payment', item)" class="rounded-lg bg-brand-500 px-2.5 py-1.5 text-xs text-white">Bayar</button>
@endcan

                <a :href="`{{ url('invoice') }}/${item.id}/cetak`" target="_blank" rel="noopener"
                    class="rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Cetak</a>
                
@can('invoice.update')
<button 
                    type="button" 
                    @click="window.location.href = `{{ url('invoice') }}/${item.id}/edit`" 
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-2.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-brand-600"
                >
                    Edit
                </button>
@endcan


                
@can('invoice.delete')
<button 
                    type="button" 
                    @click="$dispatch('delete-entity', item.id)" 
                    class="inline-flex items-center justify-center rounded-lg bg-rose-500 px-2.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-rose-600"
                >
                    Hapus
                </button>
@endcan

            </div>
        </x-slot:action>
    </x-data-table>

    <div x-data="{ deleteId: null, form: useForm({}), async remove() { await this.form.submit('delete', `{{ url('invoice') }}/${this.deleteId}`, { onSuccess: () => { refreshDataTable('invoiceTable'); this.$dispatch('close-modal', 'delete-entity-modal'); }, onError: error => toast.error('Gagal', error.response?.data?.message || 'Invoice gagal dihapus') }); } }" @delete-entity.window="deleteId = $event.detail; $dispatch('open-modal', 'delete-entity-modal')">
        <x-delete-modal name="delete-entity-modal" message="Yakin ingin menghapus invoice ini?" @confirm-delete-delete-entity-modal.window="remove" />
    </div>
    @include('pages.sales.invoice._payment-modal')
@endsection
