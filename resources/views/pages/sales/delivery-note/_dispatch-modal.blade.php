<div x-data="shipmentConfirmation()">
    <x-modal name="shipment-confirm-modal" maxWidth="max-w-lg">
        <h4 class="mb-5 text-lg font-medium text-gray-800 dark:text-white">Konfirmasi Barang Dikirim</h4>
        <form @submit.prevent="submit" class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400" x-text="note ? note.number + ': ' + formatWeight(note.quantity_kg) + ' / ' + note.sack_count + ' karung' : ''"></p>
            <x-input label="Tanggal Barang Keluar" name="shipped_date" type="date" x-model="form.data.shipped_date" alpine-error="form.getError('shipped_date')" required />
            <label class="flex gap-3 text-sm text-gray-600 dark:text-gray-400"><input type="checkbox" x-model="form.data.confirm" required> Saya memastikan barang sudah keluar dari gudang. Konfirmasi ini memotong stok dan mengunci Surat Jalan.</label>
            <p class="text-xs text-gray-500">Klik ulang tidak akan memotong stok lagi. Koreksi pengiriman yang sudah dikonfirmasi memerlukan proses retur/pembatalan terpisah.</p>
            <div class="flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'shipment-confirm-modal')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-500">Batal</button>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-green-600 px-4 py-2 text-sm text-white">Konfirmasi Dikirim</button>
            </div>
        </form>
    </x-modal>
</div>
@push('scripts')
<script>
function shipmentConfirmation() {
    return {
        note: null,
        form: useForm({ shipped_date: '', confirm: false }),
        init() {
            window.addEventListener('confirm-shipment', event => {
                this.note = event.detail;
                this.form.reset(); this.form.clearErrors();
                this.form.data.shipped_date = window.getLocalDateInputValue();
                this.$dispatch('open-modal', 'shipment-confirm-modal');
            });
        },
        async submit() {
            await this.form.submit('post', `{{ url('surat-jalan') }}/${this.note.id}/dispatch`, {
                onSuccess: response => {
                    toast.success('Berhasil', response.data.message);
                    refreshDataTable('deliveryNoteTable');
                    this.$dispatch('close-modal', 'shipment-confirm-modal');
                },
                onError: error => toast.error('Gagal', error.response?.data?.message || 'Pengiriman gagal dikonfirmasi')
            });
        }
    };
}
</script>
@endpush
