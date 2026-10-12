<div x-data="salesFormComponent()">
    <x-modal name="sale-detail-modal" maxWidth="max-w-4xl">
        <div class="max-h-[75vh] space-y-5 overflow-y-auto pr-1 text-gray-800 dark:text-gray-200">
            <div class="flex flex-wrap justify-between gap-3"><div><h3 class="text-xl font-semibold" x-text="detailItem?.sale_reference"></h3><p class="mt-1 text-sm text-gray-500" x-text="detailItem ? formatDisplayDate(detailItem.sale_date) + ' / ' + detailItem.factory?.name : ''"></p></div><span class="text-sm" x-text="detailItem?.payment_status_label"></span></div>
            <p x-show="detailItem?.purchase_order" class="text-sm" x-text="'Referensi PO: ' + (detailItem?.purchase_order?.po_number || '')"></p>
            <section class="space-y-3"><h4 class="font-semibold">Barang dalam Nota</h4>
                <template x-for="row in (detailItem?.items || [])" :key="row.id"><div class="rounded-lg border p-3 dark:border-gray-700">
                    <div class="flex flex-wrap justify-between gap-2"><strong class="text-sm" x-text="row.document_item_name"></strong><strong class="text-sm tabular-nums" x-text="formatCurrency(row.total_sale_value)"></strong></div>
                    <div class="mt-2 grid gap-2 text-sm text-gray-500 sm:grid-cols-3"><span x-text="formatWeight(row.total_weight_kg) + ' / ' + (row.sold_sacks || 0) + ' kemasan'"></span><span x-text="'Harga / Kg: ' + formatCurrency(row.price_per_kg)"></span><span x-text="'Bonus / kemasan: ' + formatWeight(row.deduction_weight_kg || 0)"></span></div>
                    <p class="mt-2 text-xs text-gray-500" x-text="'Pembayaran: ' + row.payment_status_label"></p>
                    @can('invoice.read')
                    <a x-show="row.invoice" href="{{ route('invoice.index') }}" class="mt-2 block text-sm text-brand-600" x-text="'Invoice: ' + (row.invoice?.number || '')"></a>
                    @endcan
                    <a x-show="row.payment_proof_url" :href="row.payment_proof_url" target="_blank" rel="noopener" class="mt-2 block text-sm text-brand-600">Bukti pembayaran</a>
                    <p x-show="row.payment_date" class="mt-2 text-xs text-gray-500" x-text="row.payment_date ? 'Tanggal bayar: ' + formatDisplayDate(row.payment_date) : ''"></p>
                    <p x-show="row.payment_notes" class="mt-2 text-xs text-gray-500" x-text="row.payment_notes"></p>
                </div></template>
            </section>
            <div class="space-y-3 rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-800">
                <div class="flex justify-between"><span>Nilai barang</span><span class="tabular-nums" x-text="formatCurrency(detailGross)"></span></div>
                <div class="flex justify-between"><span>Total bonus &amp; potongan</span><span class="tabular-nums" x-text="formatCurrency(detailGross - (detailItem?.total_sale_value || 0))"></span></div>
                <div class="flex justify-between font-semibold"><span>Total nota</span><span class="tabular-nums" x-text="formatCurrency(detailItem?.total_sale_value || 0)"></span></div>
                <div class="flex justify-between"><span>Sudah dibayar</span><span class="tabular-nums" x-text="formatCurrency(detailItem?.paid_amount || 0)"></span></div>
                <div class="flex justify-between font-semibold"><span>Sisa tagihan</span><span class="tabular-nums" x-text="formatCurrency(detailItem?.remaining_amount || 0)"></span></div>
            </div>
            <p x-show="detailItem?.has_invoice" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-700">Nota ini menggunakan invoice. Pelunasan dilakukan melalui menu Invoice.</p>
            <div class="flex flex-wrap justify-end gap-3">
                
@can('manajemen-penjualan.delete')
<button type="button" x-show="detailItem?.can_delete" @click="$dispatch('close-modal', 'sale-detail-modal'); $dispatch('delete-sale', detailItem.id)" class="rounded-lg border border-red-200 px-4 py-2 text-sm text-red-600">Hapus Nota</button>
@endcan

                <button type="button" @click="$dispatch('close-modal', 'sale-detail-modal')" class="rounded-lg border px-4 py-2 text-sm">Tutup</button>
                @can('invoice.read')
                <a x-show="detailItem?.has_invoice" href="{{ route('invoice.index') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white">Buka Invoice</a>
                @endcan
                
@can('manajemen-penjualan.update')
<button type="button" x-show="detailItem?.can_pay" @click="$dispatch('close-modal', 'sale-detail-modal'); openPaymentModal(detailItem)" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white">Lunasi Nota</button>
@endcan

            </div>
        </div>
    </x-modal>


    <x-delete-modal name="delete-sale-modal"
        message="Apakah anda yakin ingin menghapus penjualan ini? Stok akan dikembalikan dan bukti bayar akan dihapus. Aksi ini tidak dapat dibatalkan."
        @confirm-delete-delete-sale-modal.window="deleteConfirmed" />

    <x-modal name="pay-sale-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Pelunasan Penjualan</h4>

        <form @submit.prevent="submitPayment">
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/70">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">No. Penjualan</span>
                        <span class="font-semibold text-gray-900 dark:text-white/90" x-text="paymentItem?.sale_reference || '-'"></span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Sisa Tagihan Nota</span>
                        <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90" x-text="formatCurrency(paymentItem?.remaining_amount || 0)"></span>
                    </div>
                </div>

                <x-input label="Tanggal Bayar" name="payment_date" type="date" x-model="paymentForm.data.payment_date"
                    alpine-error="paymentForm.getError('payment_date')" required />
                <x-input label="Nominal Bayar" name="paid_amount" x-model="paymentForm.data.paid_amount" :isCurrency="true"
                    alpine-error="paymentForm.getError('paid_amount')" required />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Bukti Bayar (Opsional)</label>
                    <input type="file" x-ref="paymentProofInput" @change="paymentForm.data.payment_proof = $event.target.files[0]"
                        class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <p x-show="paymentForm.getError('payment_proof')" x-text="paymentForm.getError('payment_proof')" class="mt-1.5 text-xs text-error-500"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Catatan</label>
                    <textarea x-model="paymentForm.data.payment_notes"
                        class="block min-h-[96px] w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'pay-sale-modal')" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Batal</button>
                <button type="submit" :disabled="paymentForm.processing" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!paymentForm.processing">Simpan Pembayaran</span>
                    <span x-show="paymentForm.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>

@push('scripts')
<script>
function salesFormComponent() {
    return {
        form: useForm({}), paymentItem: null, detailItem: null, deleteId: null,
        get detailGross() { return (this.detailItem?.items || []).reduce((sum, row) => sum + Number(row.total_weight_kg) * Number(row.price_per_kg), 0); },
        paymentForm: useForm({payment_date: window.getLocalDateInputValue(), paid_amount: 0, payment_notes: '', payment_proof: null}),
        init() {
            window.addEventListener('view-sale-note', event => { this.detailItem = event.detail; this.$dispatch('open-modal', 'sale-detail-modal'); });
            window.addEventListener('open-pay-sale', event => this.openPaymentModal(event.detail));
            window.addEventListener('delete-sale', event => { this.deleteId = event.detail; this.$dispatch('open-modal', 'delete-sale-modal'); });
        },
        async deleteConfirmed() {
            await this.form.submit('delete', `{{ url('manajemen-penjualan') }}/${this.deleteId}`, {
                onSuccess: response => { toast.success('Berhasil', response.data.message); refreshDataTable('salesTable'); this.$dispatch('close-modal', 'delete-sale-modal'); },
                onError: error => toast.error('Gagal', error.response?.data?.message || 'Penjualan belum dapat dihapus.')
            });
        },
        openPaymentModal(item) {
            if (!item.can_pay || item.has_invoice) return;
            this.paymentItem = item; this.paymentForm.reset(); this.paymentForm.clearErrors();
            this.paymentForm.data.payment_date = window.getLocalDateInputValue(); this.paymentForm.data.paid_amount = item.remaining_amount || 0;
            if (this.$refs.paymentProofInput) this.$refs.paymentProofInput.value = '';
            this.$dispatch('open-modal', 'pay-sale-modal');
        },
        async submitPayment() {
            await this.paymentForm.submit('post', `{{ url('manajemen-penjualan') }}/${this.paymentItem.id}/pay`, {
                transform: data => { const body = new FormData(); Object.entries(data).forEach(([key,value]) => { if (value !== null && value !== undefined) body.append(key,value); }); return body; },
                onSuccess: response => { toast.success('Berhasil', response.data.message); refreshDataTable('salesTable'); this.$dispatch('close-modal', 'pay-sale-modal'); },
                onError: error => toast.error('Gagal', error.response?.data?.message || 'Pembayaran belum dapat disimpan.')
            });
        }
    };
}
</script>
@endpush
