<div x-data="salesFormComponent()">
    <x-modal name="sales-modal" maxWidth="max-w-[720px]">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Tambah Penjualan</h4>

        <form @submit.prevent="submit" class="max-h-[70vh] overflow-y-auto pr-1">
            <div class="space-y-4">
                <div>
                    <label for="sale-source" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Jenis Penjualan</label>
                    <select id="sale-source" x-model="form.data.sale_source" @change="changeSource()" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="direct">Penjualan langsung (tanpa PO)</option>
                        <option value="po">Penjualan melalui PO konsumen</option>
                    </select>
                </div>
                <div x-show="form.data.sale_source === 'po'" x-cloak>
                    <label for="sale-po" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">PO Konsumen</label>
                    <select id="sale-po" x-model="form.data.customer_purchase_order_id" @change="selectPurchaseOrder()" :required="form.data.sale_source === 'po'" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Pilih PO konsumen</option>
                        <template x-for="order in purchaseOrders" :key="order.id"><option :value="order.id" x-text="order.label + ' / Sisa ' + formatWeight(order.remaining_kg)"></option></template>
                    </select>
                    <p x-show="form.getError('customer_purchase_order_id')" x-text="form.getError('customer_purchase_order_id')" class="mt-1 text-xs text-error-500"></p>
                    <x-info-help><p class="mt-1 text-xs text-gray-500">PO yang sudah dipenuhi tidak ditampilkan. PO lama perlu dilengkapi pabriknya terlebih dahulu.</p></x-info-help>
                    <label for="sale-po-item" class="mt-3 mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Barang yang Dipenuhi</label>
                    <select id="sale-po-item" x-model="form.data.customer_purchase_order_item_id" @change="selectPurchaseOrderItem()" :required="form.data.sale_source === 'po'" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Pilih barang dari PO</option>
                        <template x-for="item in (selectedOrder?.items || [])" :key="item.id"><option :value="item.id" x-text="item.description + ' / Sisa ' + formatWeight(item.remaining_kg)"></option></template>
                    </select>
                    <p class="mt-1 text-xs text-error-500" x-text="form.getError('customer_purchase_order_item_id')"></p>
                    <p x-show="selectedOrderItem" class="mt-2 text-sm text-gray-700 dark:text-gray-300" x-text="selectedOrderItem ? 'Barang pesanan: ' + selectedOrderItem.description + ' · Sisa pesanan: ' + formatWeight(selectedOrderItem.remaining_kg) : ''"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Mekanisme Pengeluaran Stok</label>
                    <select x-model="form.data.stock_mode" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="immediate">Langsung — barang diserahkan saat penjualan</option>
                        <option value="delivery_note">Melalui Surat Jalan — stok keluar saat Konfirmasi Dikirim</option>
                    </select>
                    <x-info-help label="Keterangan pengeluaran stok"><p x-text="form.data.stock_mode === 'delivery_note' ? 'Penjualan ini belum memotong atau memesan stok. Siapkan Surat Jalan dan konfirmasi setelah barang keluar.' : 'Stok langsung berkurang saat penjualan disimpan. Jangan pilih ini untuk pengiriman melalui Surat Jalan.'"></p></x-info-help>
                </div>
                <div x-show="form.data.sale_source === 'direct'">
                <x-select label="Pabrik" name="factory_id" :options="$factories"
                    x-on:change="$dispatch('update-sales-field', { field: 'factory_id', value: $event.detail })"
                    alpine-error="form.getError('factory_id')" required />
                </div>
                <div x-show="form.data.sale_source === 'po'" x-cloak>
                    <x-input label="Konsumen dari PO" name="po_customer" x-bind:value="selectedOrder ? selectedOrder.label : ''" readonly />
                </div>

                <div>
                    <label class="block text-sm text-gray-700 dark:text-gray-300">Area Ready / Batch Siap Jual
                        <select x-model="stockKey" @change="selectGrade()" required class="mt-2 w-full rounded-lg border bg-white p-3 dark:bg-gray-900">
                            <option value="">Pilih area ready / batch</option>
                            <template x-for="stock in gradeStocks.filter(s => !selectedOrderItem?.quality_grade_id || s.gradeId == selectedOrderItem.quality_grade_id)" :key="stock.key">
                                <option :value="stock.key" x-text="stock.label + ' / ' + formatWeight(stock.weight) + ' / ' + stock.packages + ' kemasan'"></option>
                            </template>
                        </select>
                    </label>
                    <p x-show="form.getError('quality_grade_id')" x-text="form.getError('quality_grade_id')" class="text-xs text-red-600"></p>
                    <p x-show="form.getError('sold_sacks')" x-text="form.getError('sold_sacks')" class="text-xs text-red-600"></p>
                    <p x-show="!gradeStocks.length" class="text-sm text-gray-500">Belum ada barang jadi siap jual. Selesaikan timbang dan packing terlebih dahulu.</p>
                    <x-info-help>Pilih batch dari area barang ready sesuai lokasi penyimpanan fisik. Untuk Surat Jalan, stok diambil saat Konfirmasi Dikirim.</x-info-help>
                </div>
                <x-input label="Tanggal Penjualan" name="sale_date" type="date" x-model="form.data.sale_date"
                    alpine-error="form.getError('sale_date')" required />

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Berat Terjual (Kg)" name="sold_weight_kg" x-model="form.data.sold_weight_kg" :isNumeric="true" :numericDecimals="2"
                        alpine-error="form.getError('sold_weight_kg')" required />
                    <x-input label="Jumlah Kemasan Jadi" name="sold_sacks" type="number" min="1" x-model="form.data.sold_sacks"
                        alpine-error="form.getError('sold_sacks')" required />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Harga / Kg" name="price_per_kg" x-model="form.data.price_per_kg" :isCurrency="true"
                        :currencyDecimals="2" x-bind:readonly="form.data.sale_source === 'po'" alpine-error="form.getError('price_per_kg')" required />
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
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Pelunasan Penjualan</h4>

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
        purchaseOrders: @json($purchaseOrders),
        finishedBatches: @json($finishedBatches),
        stockKey: '',
        paymentItem: null,
        deleteId: null,

        form: useForm({
            sale_source: 'direct',
            customer_purchase_order_id: '',
            customer_purchase_order_item_id: '',
            stock_mode: 'immediate',
            factory_id: '',
            inventory_id: '',
            quality_grade_id: '',
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

        get gradeStocks() {
            return this.finishedBatches.map(batch => ({
                key: String(batch.id), batchId: batch.id,
                inventoryId: batch.inventory_id, gradeId: batch.quality_grade_id,
                label: batch.label, weight: Number(batch.weight_kg), packages: Number(batch.packages),
            }));
        },
        selectGrade() {
            const stock = this.gradeStocks.find(s => s.key === this.stockKey);
            this.form.data.inventory_id = stock?.inventoryId || '';
            this.form.data.finished_batch_id = stock?.batchId || '';
            this.form.data.quality_grade_id = '';
        },
        get selectedInventory() {
            return this.inventories.find(i => i.value == this.form.data.inventory_id);
        },

        get selectedOrder() {
            return this.purchaseOrders.find(order => order.id == this.form.data.customer_purchase_order_id);
        },

        get selectedOrderItem() {
            return this.selectedOrder?.items.find(item => item.id == this.form.data.customer_purchase_order_item_id);
        },

        changeSource() {
            this.form.data.customer_purchase_order_id = '';
            this.form.data.customer_purchase_order_item_id = '';
            this.form.data.finished_batch_id = ''; this.form.data.quality_grade_id = ''; this.stockKey = ''; this.form.data.inventory_id = '';
            this.form.data.factory_id = '';
            this.form.data.sold_weight_kg = 0;
            this.form.data.price_per_kg = 0;
            this.form.data.deduction_weight_kg = this.form.data.sale_source === 'po' ? 0 : 1;
            this.form.clearErrors();
            window.dispatchEvent(new CustomEvent('set-selected-factory_id', { detail: null }));
        },

        selectPurchaseOrder() {
            const order = this.selectedOrder;
            this.form.data.factory_id = order?.factory_id || '';
            this.form.data.customer_purchase_order_item_id = order?.items.length === 1 ? order.items[0].id : '';
            this.selectPurchaseOrderItem();
            this.form.clearErrors();
        },

        selectPurchaseOrderItem() {
            const item = this.selectedOrderItem;
            this.form.data.price_per_kg = item?.price_per_kg || 0;
            this.form.data.sold_weight_kg = item?.remaining_kg || 0;
            this.form.data.finished_batch_id = ''; this.form.data.quality_grade_id = ''; this.stockKey = ''; this.form.data.inventory_id = '';
            this.form.clearErrors();
        },

        async refreshPurchaseOrders() {
            try {
                const response = await fetch('{{ route('manajemen-penjualan.purchase-orders') }}', { headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (result.success) this.purchaseOrders = result.data;
            } catch (error) { console.error('Gagal refresh PO:', error); }
        },

        init() {
            window.addEventListener('create-entity', () => {
                this.refreshPurchaseOrders();
                this.form.reset();
                this.stockKey = '';
                this.refreshInventories();
                this.form.clearErrors();
                this.form.data.sale_date = window.getLocalDateInputValue();
                this.form.data.deduction_weight_kg = 1;
                window.dispatchEvent(new CustomEvent('set-selected-factory_id', { detail: null }));
                window.dispatchEvent(new CustomEvent('set-selected-inventory_id', { detail: null }));
                window.dispatchEvent(new CustomEvent('set-selected-deduction_type', { detail: null }));
                this.$dispatch('open-modal', 'sales-modal');
            });

            window.addEventListener('update-sales-field', (e) => {
                if (e.detail.field === 'factory_id' && this.form.data.sale_source === 'po') return;
                this.form.data[e.detail.field] = e.detail.value;
            });
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
            this.refreshPurchaseOrders();
            try {
                const res = await fetch('/warehouse-stock');
                const result = await res.json();
                if (result.success && result.data) {
                    const batchResult = await fetch('{{ route('manajemen-penjualan.batches') }}').then(r => r.json());
                    this.finishedBatches = batchResult.data || [];
                    this.inventories = result.data.filter(item => item.stock_stage === 'finished' && this.finishedBatches.some(batch => batch.inventory_id == item.value));
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
