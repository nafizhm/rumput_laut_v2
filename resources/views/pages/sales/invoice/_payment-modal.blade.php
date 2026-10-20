<div x-data="linkedInvoicePaymentForm()">
    <x-modal name="invoice-payment-modal" maxWidth="max-w-lg">
        <h4 class="mb-5 text-lg font-medium text-gray-800 dark:text-white">Pencairan Invoice</h4>
        <form @submit.prevent="submit" class="space-y-4">
            <p class="text-sm text-gray-500" x-text="invoice?.number || ''"></p>
            <p class="text-right tabular-nums text-sm text-gray-500" x-text="'Sisa tagihan: ' + formatCurrency(invoice?.remaining_amount || 0)"></p>
            <x-input label="Tanggal Pembayaran" name="invoice_payment_date" type="date" x-model="form.data.payment_date" alpine-error="form.getError('payment_date')" required />
            <x-input label="Uang Diterima (Rp)" name="invoice_payment_amount" x-model="form.data.amount" :isCurrency="true" alpine-error="form.getError('amount')" required />
            <x-input label="Potongan Kerja Sama (Rp)" name="invoice_cooperation_deduction" x-model="form.data.cooperation_deduction" :isCurrency="true" alpine-error="form.getError('cooperation_deduction')" />
            <x-input label="Keterangan Potongan Kerja Sama" name="invoice_cooperation_notes" x-model="form.data.cooperation_notes" alpine-error="form.getError('cooperation_notes')" />
            <div class="rounded-lg border p-3 text-sm dark:text-gray-300">
                <p x-text="'Pengurang tagihan: ' + formatCurrency(Number(form.data.amount || 0) + Number(form.data.cooperation_deduction || 0))"></p>
                <p class="mt-1" x-text="'Sisa setelah pencairan: ' + formatCurrency(Number(invoice?.remaining_amount || 0) - Number(form.data.amount || 0) - Number(form.data.cooperation_deduction || 0))"></p>
            </div>
            <x-input label="Catatan" name="invoice_payment_notes" x-model="form.data.notes" alpine-error="form.getError('notes')" />
            <x-info-help><p class="text-xs text-gray-500">Isi uang yang benar-benar diterima dan nominal potongan yang telah dihitung di luar aplikasi. Keduanya mengurangi tagihan. Potongan dicatat sebagai biaya kerja sama, tanpa menambah kas. Untuk cicilan, isi potongan hanya pada pencairan yang terkait; keterangannya wajib jika ada potongan.</p></x-info-help>
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
        form: useForm({ invoice_id: null, payment_date: '', amount: 0, cooperation_deduction: 0, cooperation_notes: '', notes: '' }),
        init() {
            this.$watch('form.data.cooperation_deduction', (value, previous) => {
                const remaining = Number(this.invoice?.remaining_amount || 0);
                if (Number(this.form.data.amount || 0) + Number(previous || 0) === remaining) {
                    this.form.data.amount = Math.max(0, remaining - Number(value || 0));
                }
            });
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
