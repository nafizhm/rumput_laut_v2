<div x-data="{ invoice: null, form: useForm({}), async cancel(payment) {
    if (!window.confirm('Batalkan pencairan ini? Uang masuk dan potongan kerja sama akan dibalik, lalu sisa tagihan dihitung ulang.')) return;
    await this.form.submit('delete', `{{ url('invoice/payments') }}/${payment.id}`, {
        onSuccess: response => { toast.success('Berhasil', response.data.message); refreshDataTable('invoiceTable'); this.$dispatch('close-modal', 'invoice-payment-history'); },
        onError: error => toast.error('Gagal', error.response?.data?.message || 'Pencairan gagal dibatalkan')
    });
} }" @open-invoice-payment-history.window="invoice = $event.detail; $dispatch('open-modal', 'invoice-payment-history')">
    <x-modal name="invoice-payment-history" maxWidth="max-w-4xl">
        <h4 class="mb-3 text-lg font-medium text-gray-800 dark:text-white">Riwayat Pencairan Invoice</h4>
        <p class="mb-4 text-sm text-gray-500" x-text="invoice?.number"></p>
        <div class="overflow-x-auto text-gray-800 dark:text-gray-200"><table class="w-full text-left text-sm">
            <thead><tr>@foreach(['Tanggal','Uang diterima','Potongan kerja sama','Pengurang tagihan','Keterangan','Tindakan'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody><template x-for="payment in (invoice?.payments || [])" :key="payment.id"><tr class="border-t">
                <td class="p-3" x-text="formatDisplayDate(payment.payment_date)"></td>
                <td class="p-3 tabular-nums" x-text="formatCurrency(payment.amount)"></td>
                <td class="p-3 tabular-nums" x-text="formatCurrency(payment.cooperation_deduction || 0)"></td>
                <td class="p-3 tabular-nums" x-text="formatCurrency(payment.settlement_amount)"></td>
                <td class="p-3 whitespace-pre-line"><p x-text="payment.cooperation_notes || ''"></p><p x-text="payment.notes || ''"></p></td>
                <td class="p-3">@can('invoice.update')<button type="button" @click="cancel(payment)" :disabled="form.processing" class="text-rose-600">Batalkan</button>@endcan</td>
            </tr></template><tr x-show="!invoice?.payments?.length"><td colspan="6" class="p-4 text-gray-500">Belum ada pencairan tercatat.</td></tr></tbody>
        </table></div>
    </x-modal>
</div>
