<div x-data="salesFormComponent()">
    <x-modal name="sales-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Tambah Penjualan Langsung</h4>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Mekanisme Pengeluaran Stok</label>
                    <select x-model="form.data.stock_mode" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="immediate">Langsung — barang diserahkan saat penjualan</option>
                        <option value="delivery_note">Melalui Surat Jalan — stok keluar saat Konfirmasi Dikirim</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500" x-text="form.data.stock_mode === 'delivery_note' ? 'Penjualan ini belum memotong atau memesan stok. Siapkan Surat Jalan dan konfirmasi setelah barang keluar.' : 'Stok langsung berkurang saat penjualan disimpan. Jangan pilih ini untuk pengiriman melalui Surat Jalan.'"></p>
                </div>
                <x-select label="Pabrik" name="factory_id" :options="$factories"
                    x-on:change="$dispatch('update-sales-field', { field: 'factory_id', value: $event.detail })"
                    alpine-error="form.getError('factory_id')" required />

                <x-select label="Barang / Stok" name="inventory_id" :options="$inventories"
                    x-on:change="$dispatch('update-sales-field', { field: 'inventory_id', value: $event.detail })"
                    alpine-error="form.getError('inventory_id')" required />

                <div x-show="selectedInventory?.stock_stage === 'finished'" x-cloak>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Batch barang jadi / kualitas / area
                        <select x-model="form.data.finished_batch_id" :required="selectedInventory?.stock_stage === 'finished'" class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900">
                            <option value="">Pilih batch barang jadi</option>
                            <template x-for="batch in finishedBatches.filter(b => b.inventory_id == form.data.inventory_id)" :key="batch.id"><option :value="batch.id" x-text="batch.label"></option></template>
                        </select>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">Satu penjualan memakai satu batch jadi. Jumlah karung di bawah adalah jumlah kemasan jadi untuk batch ini.</p>
                    <p x-show="form.getError('finished_batch_id')" x-text="form.getError('finished_batch_id')" class="text-xs text-red-600"></p>
                </div>

                <div x-show="selectedInventory" class="mt-2 flex items-center gap-2" x-cloak>
                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                        Stok Saat Ini: <span x-text="selectedInventory ? formatWeight(selectedInventory.current_stock) : '0 Kg'" class="ml-1 font-bold"></span>
                    </span>
                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                        <span x-text="selectedInventory ? Number(selectedInventory.current_sacks || 0).toLocaleString('id-ID') : 0" class="mr-1 font-bold"></span> Karung
                    </span>
                </div>

                <x-input label="Tanggal Penjualan" name="sale_date" type="date" x-model="form.data.sale_date"
                    alpine-error="form.getError('sale_date')" required />

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Berat Terjual (Kg)" name="sold_weight_kg" x-model="form.data.sold_weight_kg" :isNumeric="true" :numericDecimals="2"
                        alpine-error="form.getError('sold_weight_kg')" required />
                    <x-input label="Jumlah Karung (Opsional)" name="sold_sacks" type="number" x-model="form.data.sold_sacks"
                        alpine-error="form.getError('sold_sacks')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Harga / Kg" name="price_per_kg" x-model="form.data.price_per_kg" :isCurrency="true"
                        alpine-error="form.getError('price_per_kg')" required />
                    <x-input label="Bonus / Karung (Kg)" name="deduction_weight_kg" x-model="form.data.deduction_weight_kg" :isNumeric="true" :numericDecimals="2"
                        alpine-error="form.getError('deduction_weight_kg')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-select label="Jenis Potongan" name="deduction_type" :options="[
                        ['label' => 'Tidak Ada', 'value' => 'none'],
                        ['label' => 'Persentase', 'value' => 'percentage'],
                        ['label' => 'Nominal', 'value' => 'nominal'],
                        ['label' => 'Berat (Kg)', 'value' => 'weight'],
                    ]"
                        x-on:change="$dispatch('update-sales-field', { field: 'deduction_type', value: $event.detail })"
                        alpine-error="form.getError('deduction_type')" required />

                    <div x-show="form.data.deduction_type === 'nominal'">
                        <x-input label="Nilai Potongan" name="deduction_value_nominal"
                            x-model="form.data.deduction_value"
                            :isCurrency="true"
                            alpine-error="form.getError('deduction_value')" />
                    </div>
                    <div x-show="form.data.deduction_type === 'percentage' || form.data.deduction_type === 'weight'">
                        <x-input label="Nilai Potongan" name="deduction_value_numeric"
                            x-model="form.data.deduction_value"
                            :isNumeric="true"
                            :numericDecimals="2"
                            alpine-error="form.getError('deduction_value')" />
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/70">
                    <h5 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Ringkasan Pembayaran</h5>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Nilai Kotor</span>
                            <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90" x-text="formatCurrency(grossSaleValue)"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Potongan Penjualan</span>
                            <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90" x-text="formatCurrency(totalDeductionAmount)"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Total Nilai Jual</span>
                            <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90" x-text="formatCurrency(totalSaleValue)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'sales-modal')" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Batal</button>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!form.processing">Simpan</span>
                    <span x-show="form.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-delete-modal name="delete-sale-modal"
        message="Apakah anda yakin ingin menghapus penjualan ini? Stok akan dikembalikan dan bukti bayar akan dihapus. Aksi ini tidak dapat dibatalkan."
        @confirm-delete-delete-sale-modal.window="deleteConfirmed" />

    <x-modal name="pay-sale-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Pelunasan Penjualan Langsung</h4>

        <form @submit.prevent="submitPayment">
            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/70">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">No. Penjualan</span>
                        <span class="font-semibold text-gray-900 dark:text-white/90" x-text="paymentItem?.invoice_number || '-'"></span>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Total Penjualan</span>
                        <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90" x-text="formatCurrency(paymentItem?.total_sale_value || 0)"></span>
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
        inventories: @json($inventories),
        finishedBatches: @json($finishedBatches),
        paymentItem: null,
        deleteId: null,

        form: useForm({
            stock_mode: 'immediate',
            factory_id: '',
            inventory_id: '',
            finished_batch_id: '',
            sale_date: window.getLocalDateInputValue(),
            sold_weight_kg: 0,
            price_per_kg: 0,
            sold_sacks: 0,
            deduction_type: 'none',
            deduction_value: 0,
            deduction_weight_kg: 1,
        }),

        paymentForm: useForm({
            payment_date: window.getLocalDateInputValue(),
            paid_amount: 0,
            payment_notes: '',
            payment_proof: null,
        }),

        get grossSaleValue() {
            return (parseFloat(this.form.data.sold_weight_kg) || 0) * (parseFloat(this.form.data.price_per_kg) || 0);
        },

        get weightDeductionAmount() {
            const sacks = parseInt(this.form.data.sold_sacks) || 0;
            const extra = parseFloat(this.form.data.deduction_weight_kg) || 0;
            const price = parseFloat(this.form.data.price_per_kg) || 0;
            return sacks * extra * price;
        },

        get typeDeductionAmount() {
            const price = parseFloat(this.form.data.price_per_kg) || 0;
            const weightDeductionAmount = this.weightDeductionAmount;
            const valueAfterWeight = Math.max(0, this.grossSaleValue - weightDeductionAmount);
            const deductionValue = parseFloat(this.form.data.deduction_value) || 0;

            if (this.form.data.deduction_type === 'percentage') {
                return valueAfterWeight * (deductionValue / 100);
            }
            if (this.form.data.deduction_type === 'nominal') {
                return deductionValue;
            }
            if (this.form.data.deduction_type === 'weight') {
                return deductionValue * price;
            }
            return 0;
        },

        get totalDeductionAmount() {
            return this.weightDeductionAmount + this.typeDeductionAmount;
        },

        get totalSaleValue() {
            return Math.max(0, this.grossSaleValue - this.totalDeductionAmount);
        },

        get selectedInventory() {
            return this.inventories.find(i => i.value == this.form.data.inventory_id);
        },

        init() {
            window.addEventListener('create-entity', () => {
                this.form.reset();
                this.form.clearErrors();
                this.form.data.sale_date = window.getLocalDateInputValue();
                this.form.data.deduction_weight_kg = 1;
                window.dispatchEvent(new CustomEvent('set-selected-factory_id', { detail: null }));
                window.dispatchEvent(new CustomEvent('set-selected-inventory_id', { detail: null }));
                window.dispatchEvent(new CustomEvent('set-selected-deduction_type', { detail: null }));
                this.$dispatch('open-modal', 'sales-modal');
            });

            window.addEventListener('update-sales-field', (e) => { this.form.data[e.detail.field] = e.detail.value; if(e.detail.field === 'inventory_id') this.form.data.finished_batch_id = ''; });
            window.addEventListener('open-pay-sale', (e) => this.openPaymentModal(e.detail));
            window.addEventListener('delete-sale', (e) => this.deleteItem(e.detail));
        },

        deleteItem(id) {
            this.deleteId = id;
            this.$dispatch('open-modal', 'delete-sale-modal');
        },

        async deleteConfirmed() {
            await this.form.submit('delete', `{{ url('manajemen-penjualan') }}/${this.deleteId}`, {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    refreshDataTable('salesTable');
                    this.$dispatch('close-modal', 'delete-sale-modal');
                    this.refreshInventories();
                },
                onError: (err) => {
                    toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan saat menghapus data');
                }
            });
        },

        openPaymentModal(item) {
            this.paymentItem = item;
            this.paymentForm.reset();
            this.paymentForm.clearErrors();
            this.paymentForm.data.payment_date = window.getLocalDateInputValue();
            this.paymentForm.data.paid_amount = item.total_sale_value || 0;
            this.paymentForm.data.payment_proof = null;
            if (this.$refs.paymentProofInput) {
                this.$refs.paymentProofInput.value = '';
            }
            this.$dispatch('open-modal', 'pay-sale-modal');
        },

        async refreshInventories() {
            try {
                const res = await fetch('/warehouse-stock');
                const result = await res.json();
                if (result.success && result.data) {
                    this.inventories = result.data.filter(item => item.stock_stage !== 'material');
                    const batchResult = await fetch('{{ route('manajemen-penjualan.batches') }}').then(r => r.json());
                    this.finishedBatches = batchResult.data || [];
                }
            } catch (e) {
                console.error('Gagal refresh stok:', e);
            }
        },

        async submit() {
            await this.form.submit('post', '{{ route("manajemen-penjualan.store") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    refreshDataTable('salesTable');
                    this.$dispatch('close-modal', 'sales-modal');
                    this.refreshInventories();
                },
                onError: (err) => {
                    toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem');
                }
            });
        },

        async submitPayment() {
            await this.paymentForm.submit('post', `{{ url('manajemen-penjualan') }}/${this.paymentItem.id}/pay`, {
                transform: (data) => {
                    const formData = new FormData();
                    Object.entries(data).forEach(([key, value]) => {
                        if (value !== null && value !== undefined) {
                            formData.append(key, value);
                        }
                    });
                    return formData;
                },
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    refreshDataTable('salesTable');
                    this.$dispatch('close-modal', 'pay-sale-modal');
                },
                onError: (err) => {
                    toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem');
                }
            });
        }
    }
}
</script>
@endpush
