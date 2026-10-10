<div x-data="linkedInvoicePaymentForm()">
    <x-modal name="invoice-payment-modal" maxWidth="max-w-lg">
        <h4 class="mb-5 text-lg font-medium text-gray-800 dark:text-white">Pembayaran Invoice</h4>
        <form @submit.prevent="submit" class="space-y-4">
            <p class="text-sm text-gray-500" x-text="invoice?.number || ''"></p>
            <p class="text-right tabular-nums text-sm text-gray-500" x-text="'Sisa tagihan: ' + formatCurrency(invoice?.remaining_amount || 0)"></p>
            <x-input label="Tanggal Pembayaran" name="invoice_payment_date" type="date" x-model="form.data.payment_date" alpine-error="form.getError('payment_date')" required />
            <x-input label="Jumlah Pembayaran (Rp)" name="invoice_payment_amount" x-model="form.data.amount" :isCurrency="true" alpine-error="form.getError('amount')" required />
            <x-input label="Catatan" name="invoice_payment_notes" x-model="form.data.notes" alpine-error="form.getError('notes')" />
            <p class="text-xs text-gray-500">Pembayaran dapat dicicil. Jumlah dibayar dan sisa tagihan pada penjualan terkait ikut diperbarui.</p>
            <div class="flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'invoice-payment-modal')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-500">Batal</button>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white">Simpan Pembayaran</button>
            </div>
        </form>
    </x-modal>
</div>
@push('scripts')
<script>
function linkedInvoicePaymentForm() {
    return {
        invoice: null,
        form: useForm({ invoice_id: null, payment_date: '', amount: 0, notes: '' }),
        init() {
            window.addEventListener('open-invoice-payment', event => {
                this.invoice = event.detail;
                this.form.reset();
                this.form.clearErrors();
                this.form.data.invoice_id = this.invoice.id;
                this.form.data.payment_date = window.getLocalDateInputValue();
                this.form.data.amount = this.invoice.remaining_amount;
                this.$dispatch('open-modal', 'invoice-payment-modal');
            });
        },
        async submit() {
            await this.form.submit('post', `{{ url('invoice') }}/${this.invoice.id}/pay`, {
                onSuccess: response => {
                    toast.success('Berhasil', response.data.message);
                    refreshDataTable('invoiceTable');
                    this.$dispatch('close-modal', 'invoice-payment-modal');
                },
                onError: error => toast.error('Gagal', error.response?.data?.message || 'Pembayaran gagal disimpan')
            });
        }
    };
}
</script>
@endpush
