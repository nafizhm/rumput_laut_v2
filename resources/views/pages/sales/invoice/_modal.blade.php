<div x-data="invoiceForm()">
    <x-modal name="entity-modal" maxWidth="max-w-[820px]">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Invoice' : 'Tambah Invoice'"></h4>

        <form @submit.prevent="submit" class="max-h-[70vh] overflow-y-auto pr-1">
            <x-form-section title="Informasi Invoice" description="Pilih PO untuk mengisi customer, barang, harga, dan referensi pengiriman.">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Penjualan yang Ditagihkan</label>
                    <select x-model="form.data.sales_transaction_id" @change="selectSale()" :disabled="editMode && !!originalSaleId" :required="!editMode" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Pilih penjualan (data lama dapat tetap belum terhubung)</option>
                        <template x-for="sale in availableSales" :key="sale.value"><option :value="sale.value" x-text="sale.label"></option></template>
                    </select>
                    <p class="mt-1 text-xs text-error-500" x-text="form.getError('sales_transaction_id')"></p>
                    <x-info-help><p class="mt-1 text-xs text-gray-500">Satu penjualan untuk satu invoice. Nilai barang dan potongan mengikuti penjualan; stok tidak dipotong lagi.</p></x-info-help>
                </div>
                <div class="sm:col-span-2">
                    <x-select label="Nomor PO" name="invoice_po_id" :options="$orders" :searchable="true" placeholder="Pilih nomor PO konsumen" required
                        x-on:change="selectOrder($event.detail)" alpine-error="form.getError('customer_purchase_order_id')" />
                </div>
                <div class="sm:col-span-2 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Surat Jalan terkait PO <span x-show="deliveryNotes.length" x-text="'(' + deliveryNotes.length + ')' "></span></p>
                    <div class="space-y-2">
                        <template x-for="note in deliveryNotes" :key="note.id">
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                <span class="font-medium text-gray-800 dark:text-white" x-text="note.number"></span>
                                <span class="text-xs text-gray-500" x-text="note.date"></span>
                            </div>
                        </template>
                    </div>
                    <p x-show="!deliveryNotes.length" class="text-sm text-gray-500" x-text="form.data.customer_purchase_order_id ? 'Belum ada Surat Jalan untuk PO ini' : 'Pilih nomor PO terlebih dahulu'"></p>
                    <x-info-help><p class="mt-2 text-xs text-gray-500">Untuk alur pengiriman baru, hanya Surat Jalan yang sudah dikonfirmasi Dikirim dari penjualan ini yang disertakan.</p></x-info-help>
                </div>
                <x-input label="No. Invoice" name="number" type="text" x-model="form.data.number" x-bind:readonly="!editMode" placeholder="Otomatis saat disimpan sesuai aturan Profile" alpine-error="form.getError('number')" maxlength="100" />
                <x-input label="Tanggal Invoice" name="invoice_date" type="date" x-model="form.data.invoice_date" alpine-error="form.getError('invoice_date')" required />
            </x-form-section>
            <x-form-section title="Data Customer" description="Identitas dan alamat penagihan konsumen.">
                <x-input label="Konsumen" name="customer_name" type="text" x-model="form.data.customer_name" x-bind:readonly="!!form.data.sales_transaction_id" alpine-error="form.getError('customer_name')" required />
                <x-input label="Alamat Konsumen" name="customer_address" type="text" x-model="form.data.customer_address" alpine-error="form.getError('customer_address')" required />
            </x-form-section>
            <x-form-section title="Detail Barang & Harga" description="Periksa jumlah dan harga barang yang ditagihkan.">
                <x-input label="Deskripsi Barang" name="item_name" type="text" x-model="form.data.item_name" x-bind:readonly="!!form.data.sales_transaction_id" alpine-error="form.getError('item_name')" required />
                <x-input label="Jumlah (Kg)" name="quantity_kg" type="number" x-model="form.data.quantity_kg" x-bind:readonly="!!form.data.sales_transaction_id" alpine-error="form.getError('quantity_kg')" required min="0.01" step="0.01" />
                <x-input label="Harga per Kg (Rp)" name="price_per_kg" :isCurrency="true" :currencyDecimals="2" x-model="form.data.price_per_kg" x-bind:readonly="!!form.data.sales_transaction_id" alpine-error="form.getError('price_per_kg')" required min="0.01" step="0.01" />
                <div class="sm:col-span-2 rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-white">
                    Total tagihan setelah potongan: <strong x-text="Number(invoiceTotal).toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})" class="text-right tabular-nums"></strong>
                </div>
            </x-form-section>
            <x-form-section title="Pembayaran & Jatuh Tempo" description="Atur batas pembayaran dan status pelunasan.">
                <x-input label="Jatuh Tempo" name="due_date" type="date" x-model="form.data.due_date" alpine-error="form.getError('due_date')" required x-bind:min="form.data.invoice_date" />
                <div>
                    <label for="payment_status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Status Pembayaran</label>
                    <x-info-help><p class="text-sm text-gray-500">Status mengikuti pembayaran yang dicatat melalui tombol Bayar, bukan diubah manual.</p></x-info-help>
                    <p class="mt-1 text-xs text-error-500" x-text="form.getError('payment_status')"></p>
                </div>
                <x-input label="Cara Pembayaran / Rekening" name="payment_terms" type="text" x-model="form.data.payment_terms" alpine-error="form.getError('payment_terms')"  />
                <x-input label="Catatan Invoice" name="notes" type="text" x-model="form.data.notes" alpine-error="form.getError('notes')"  />
                <p class="sm:col-span-2 text-xs text-gray-500" x-show="form.data.invoice_date && form.data.due_date" x-text="'Termin pembayaran: ' + Math.round((Date.parse(form.data.due_date) - Date.parse(form.data.invoice_date)) / 86400000) + ' hari'"></p>
            </x-form-section>

            <div class="sticky bottom-0 z-10 mt-6 flex justify-end gap-3 border-t border-gray-200 bg-white py-4 dark:border-gray-700 dark:bg-gray-900">
                <button type="button" @click="$dispatch('close-modal', 'entity-modal')"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    Batal
                </button>

                <button type="submit" :disabled="form.processing"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!form.processing">Simpan</span>
                    <span x-show="form.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-delete-modal name="delete-entity-modal"
        message="Yakin ingin menghapus invoice ini?"
        @confirm-delete-delete-entity-modal.window="deleteConfirmed" />
</div>

@push('scripts')
    <script>
        function invoiceForm() {
            return {
                orders: @js($orders),
                sales: @js($sales),
                originalSaleId: null,
                deliveryNotes: [],
                editMode: false,
                editId: null,
                deleteId: null,

                form: useForm({
                    sales_transaction_id: '',
                    customer_purchase_order_id: '',
                    number: '',
                    invoice_date: '',
                    due_date: '',
                    customer_name: '',
                    customer_address: '',
                    item_name: '',
                    quantity_kg: '',
                    price_per_kg: '',
                    payment_status: 'unpaid',
                    payment_terms: '',
                    notes: ''
                }),

                init() {
                    window.addEventListener('create-entity', () => this.create());
                    window.addEventListener('edit-entity', (event) => this.edit(event.detail));
                    window.addEventListener('delete-entity', (event) => this.deleteItem(event.detail));
                },

                create() {
                    this.originalSaleId = null;
                    this.editMode = false;
                    this.editId = null;
                    this.form.reset();
                    this.form.data.invoice_date = window.getLocalDateInputValue();
                    const due = new Date(this.form.data.invoice_date + 'T12:00:00');
                    due.setDate(due.getDate() + 30);
                    this.form.data.due_date = [due.getFullYear(), String(due.getMonth() + 1).padStart(2, '0'), String(due.getDate()).padStart(2, '0')].join('-');
                    this.form.data.payment_status = 'unpaid';
                    this.form.data.payment_terms = 'Transfer / sesuai termin';
                    this.deliveryNotes = [];
                    this.$dispatch('set-selected-invoice_po_id', null);
                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'entity-modal');
                },

                edit(item) {
                    this.originalSaleId = item.sales_transaction_id ?? null;
                    this.editMode = true;
                    this.editId = item.id;
                    this.form.clearErrors();
                    this.form.data = {
                        sales_transaction_id: item.sales_transaction_id ?? '',
                        customer_purchase_order_id: item.customer_purchase_order_id ?? '',
                        number: item.number ?? '',
                        invoice_date: item.invoice_date ?? '',
                        due_date: item.due_date ?? '',
                        customer_name: item.customer_name ?? '',
                        customer_address: item.customer_address ?? '',
                        item_name: item.item_name ?? '',
                        quantity_kg: item.quantity_kg ?? '',
                        price_per_kg: item.price_per_kg ?? '',
                        payment_status: item.payment_status ?? 'unpaid',
                        payment_terms: item.payment_terms ?? '',
                        notes: item.notes ?? ''
                    };
                    this.refreshDeliveryNotes();
                    this.$dispatch('set-selected-invoice_po_id', item.customer_purchase_order_id ?? null);
                    this.$dispatch('open-modal', 'entity-modal');
                },

                selectOrder(id) {
                    if (String(this.form.data.customer_purchase_order_id || '') === String(id || '')) return;
                    this.form.data.customer_purchase_order_id = id || '';
                    const order = this.orders.find(order => String(order.value) === String(id));
                    this.refreshDeliveryNotes();
                    const fields = this.form.data.sales_transaction_id ? ['customer_address'] : ['customer_name', 'customer_address', 'item_name', 'quantity_kg', 'price_per_kg'];
                    fields.forEach(key => this.form.data[key] = order?.[key] ?? '');
                    this.form.clearErrors('customer_purchase_order_id');
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-entity-modal');
                },

                get availableSales() {
                    return this.sales.filter(sale => !sale.invoice_id || String(sale.value) === String(this.originalSaleId));
                },

                get invoiceTotal() {
                    const sale = this.sales.find(sale => String(sale.value) === String(this.form.data.sales_transaction_id));
                    return sale ? sale.total_amount : (Number(this.form.data.quantity_kg) || 0) * (Number(this.form.data.price_per_kg) || 0);
                },

                selectSale() {
                    const sale = this.sales.find(sale => String(sale.value) === String(this.form.data.sales_transaction_id));
                    if (!sale) return;
                    ['customer_name', 'customer_address', 'item_name', 'quantity_kg', 'price_per_kg'].forEach(key => this.form.data[key] = sale[key] ?? '');
                    if (sale.customer_purchase_order_id) {
                        this.form.data.customer_purchase_order_id = sale.customer_purchase_order_id;
                        this.$dispatch('set-selected-invoice_po_id', sale.customer_purchase_order_id);
                    }
                    this.refreshDeliveryNotes();
                },

                refreshDeliveryNotes() {
                    const sale = this.sales.find(sale => String(sale.value) === String(this.form.data.sales_transaction_id));
                    const notes = this.orders.find(order => String(order.value) === String(this.form.data.customer_purchase_order_id))?.delivery_notes || [];
                    this.deliveryNotes = sale?.stock_mode === 'delivery_note'
                        ? notes.filter(note => note.status === 'shipped' && String(note.sales_transaction_id) === String(sale.value))
                        : notes;
                },

                async deleteConfirmed() {
                    await this.form.submit('delete', `{{ url('invoice') }}/${this.deleteId}`, {
                        onSuccess: (res) => {
                            toast.success('Success', res.data.message);
                            refreshDataTable('invoiceTable');
                            this.$dispatch('close-modal', 'delete-entity-modal');
                        },
                        onError: (err) => {
                            toast.error('Error', err.response?.data?.message ||
                                'Terjadi kesalahan saat menghapus data');
                        }
                    });
                },

                async submit() {
                    if (this.form.processing) return;
                    const url = this.editMode ?
                        `{{ url('invoice') }}/${this.editId}` :
                        `{{ url('invoice') }}`;
                    const method = this.editMode ? 'put' : 'post';

                    await this.form.submit(method, url, {
                        onSuccess: (res) => {
                            toast.success('Success', res.data.message);
                            refreshDataTable('invoiceTable');
                            this.$dispatch('close-modal', 'entity-modal');
                        },
                        onError: (err) => {
                            toast.error('Error', err.response?.data?.message || 'Terjadi kesalahan sistem');
                        }
                    });
                }
            }
        }
    </script>
@endpush
