@extends('layouts.app')
@section('title', $invoice ? 'Edit Invoice' : 'Tambah Invoice')
@section('breadcrumb')
    <x-breadcrumb :pageTitle="$invoice ? 'Edit Invoice' : 'Tambah Invoice'" :links="[['name' => 'Invoice', 'url' => route('invoice.index')]]" />
@endsection
@section('content')
<div x-data="invoiceForm()" class="mx-auto max-w-6xl">
    <div class="mb-6 flex items-center justify-between gap-4"><div><h2 class="text-xl font-semibold text-gray-800 dark:text-white">{{ $invoice ? 'Edit Invoice' : 'Tambah Invoice' }}</h2><p class="mt-1 text-sm text-gray-500">Pilih penjualan, periksa barang, lalu tentukan jatuh tempo.</p></div><a href="{{ route('invoice.index') }}" class="rounded-lg border px-4 py-2 text-sm dark:border-gray-700 dark:text-gray-300">Kembali ke Invoice</a></div>
        <form @submit.prevent="submit" class="space-y-6">
            <x-form-section title="Informasi Invoice" description="Pilih satu penjualan. Seluruh barang, konsumen, dan nilai tagihan otomatis mengikuti transaksi tersebut.">
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
                    <x-select label="Nomor PO" name="invoice_po_id" :options="$orders" :selected="$invoice?->customer_purchase_order_id" :searchable="true" placeholder="Pilih nomor PO konsumen" required
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
            <div x-show="selectedSale" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b p-5 dark:border-gray-800"><h3 class="font-semibold text-gray-800 dark:text-white">Barang yang Ditagihkan</h3><p class="mt-1 text-sm text-gray-500">Saat edit, ubah deskripsi, kilo tagihan, dan harga per barang. Penjualan dan sisa tagihan ikut diperbarui. Stok dan surat jalan mengikuti pengiriman fisik.</p></div>
                <div class="overflow-x-auto"><table class="w-full text-left text-sm text-gray-700 dark:text-gray-300"><thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="p-4">Barang</th><th class="p-4 text-right">Jumlah (Kg)</th><th class="p-4 text-right">Harga / Kg</th><th class="p-4 text-right">Subtotal</th><th class="p-4 text-right">Tagihan</th></tr></thead><tbody><template x-for="product in selectedSale?.items || []" :key="product.id"><tr class="border-t dark:border-gray-800"><td class="p-4"><input aria-label="Deskripsi barang" :readonly="!editMode" x-model="product.item_name" @input="syncItems()" required maxlength="255" class="w-full rounded-lg border p-2 dark:bg-gray-900"></td><td class="p-4"><input aria-label="Jumlah kilo tagihan" :readonly="!editMode" type="number" x-model="product.quantity_kg" @input="syncItems()" required min="0.01" max="9999999.99" step="0.01" class="w-32 rounded-lg border p-2 text-right dark:bg-gray-900"></td><td class="p-4"><input aria-label="Harga per kilo" :readonly="!editMode" type="number" x-model="product.price_per_kg" @input="syncItems()" required min="0.01" max="9999999.99" step="0.01" class="w-40 rounded-lg border p-2 text-right dark:bg-gray-900"></td><td class="p-4 text-right" x-text="money(product.subtotal)"></td><td class="p-4 text-right" x-text="money(product.total_amount)"></td></tr></template></tbody></table></div>
                <p class="px-5 text-sm text-error-500" x-text="form.getError('items')"></p><div class="flex flex-wrap justify-end gap-6 bg-gray-50 p-5 dark:bg-gray-800"><div><p class="text-xs text-gray-500">Potongan</p><p x-text="money((selectedSale?.subtotal || 0) - invoiceTotal)"></p></div><div><p class="text-xs text-gray-500">Total Tagihan</p><p class="text-lg font-semibold text-brand-600" x-text="money(invoiceTotal)"></p></div></div>
            </div>
            <fieldset x-show="!form.data.sales_transaction_id" :disabled="!!form.data.sales_transaction_id">
            <x-form-section title="Detail Barang & Harga" description="Periksa jumlah dan harga barang yang ditagihkan.">
                <x-input label="Deskripsi Barang" name="item_name" type="text" x-model="form.data.item_name" x-bind:readonly="!!form.data.sales_transaction_id" alpine-error="form.getError('item_name')" required />
                <x-input label="Jumlah (Kg)" name="quantity_kg" type="number" x-model="form.data.quantity_kg" x-bind:readonly="!!form.data.sales_transaction_id" alpine-error="form.getError('quantity_kg')" required min="0.01" step="0.01" />
                <x-input label="Harga per Kg (Rp)" name="price_per_kg" :isCurrency="true" :currencyDecimals="2" x-model="form.data.price_per_kg" x-bind:readonly="!!form.data.sales_transaction_id" alpine-error="form.getError('price_per_kg')" required min="0.01" step="0.01" />
                <div class="sm:col-span-2 rounded-lg bg-gray-50 p-4 text-sm text-gray-800 dark:bg-gray-800 dark:text-white">
                    Total tagihan setelah potongan: <strong x-text="Number(invoiceTotal).toLocaleString('id-ID', {style: 'currency', currency: 'IDR'})" class="text-right tabular-nums"></strong>
                </div>
            </x-form-section>
            </fieldset>
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
                <button type="button" @click="window.location.href = @js(route('invoice.index'))"
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

</div>
@endsection
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
                    const saved = @js($invoice);
                    if (saved) this.edit(saved); else this.create();
                    this.$nextTick(() => this.$dispatch('set-selected-invoice_po_id', this.form.data.customer_purchase_order_id || null));
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
                    this.syncItems();
                    this.refreshDeliveryNotes();
                    this.$dispatch('set-selected-invoice_po_id', item.customer_purchase_order_id ?? null);

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

                syncItems() {
                    const sale = this.selectedSale;
                    if (!sale || !this.editMode) return;
                    for (const product of sale.items) {
                        product.subtotal = Number(product.quantity_kg) * Number(product.price_per_kg);
                        const base = (Number(product.quantity_kg) - Number(product.bonus_weight || 0)) * Number(product.price_per_kg);
                        const value = Number(product.deduction_value || 0);
                        const deduction = product.deduction_type === 'percentage' ? Math.round(base * value / 100)
                            : product.deduction_type === 'weight' ? Math.round(value * Number(product.price_per_kg))
                            : product.deduction_type === 'nominal' ? Math.round(value) : 0;
                        product.total_amount = Math.max(0, Math.round(base) - deduction);
                    }
                    sale.subtotal = sale.items.reduce((sum, product) => sum + product.subtotal, 0);
                    sale.total_amount = sale.items.reduce((sum, product) => sum + product.total_amount, 0);
                    this.form.data.items = sale.items.map(product => ({id: product.id, item_name: product.item_name,
                        quantity_kg: product.quantity_kg, price_per_kg: product.price_per_kg}));
                },

                get availableSales() {
                    return this.sales.filter(sale => !sale.invoice_id || String(sale.value) === String(this.originalSaleId));
                },

                get invoiceTotal() {
                    const sale = this.sales.find(sale => String(sale.value) === String(this.form.data.sales_transaction_id));
                    return sale ? sale.total_amount : (Number(this.form.data.quantity_kg) || 0) * (Number(this.form.data.price_per_kg) || 0);
                },

                get selectedSale() { return this.sales.find(sale => String(sale.value) === String(this.form.data.sales_transaction_id)); },
                money(amount) { return Number(amount || 0).toLocaleString('id-ID', { style: 'currency', currency: 'IDR' }); },
                selectSale() {
                    const sale = this.sales.find(sale => String(sale.value) === String(this.form.data.sales_transaction_id));
                    if (!sale) { ['customer_name', 'customer_address', 'item_name', 'quantity_kg', 'price_per_kg'].forEach(key => this.form.data[key] = ''); this.form.data.customer_purchase_order_id = ''; this.$dispatch('set-selected-invoice_po_id', null); this.refreshDeliveryNotes(); return; }
                    ['customer_name', 'customer_address', 'item_name', 'quantity_kg', 'price_per_kg'].forEach(key => this.form.data[key] = sale[key] ?? '');
                    this.form.data.customer_purchase_order_id = sale.customer_purchase_order_id || '';
                    this.$dispatch('set-selected-invoice_po_id', sale.customer_purchase_order_id || null);
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
                        ? notes.filter(note => note.status === 'shipped' && (note.sales_transaction_ids || []).some(id => sale.sales_transaction_ids.map(String).includes(String(id))))
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
                            window.location.href = @js(route('invoice.index'));
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
