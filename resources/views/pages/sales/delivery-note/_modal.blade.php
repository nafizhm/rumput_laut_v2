<div x-data="deliveryNoteForm()">
    <x-modal name="entity-modal" maxWidth="max-w-[820px]">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Surat Jalan' : 'Tambah Surat Jalan'"></h4>

        <form @submit.prevent="submit" class="max-h-[70vh] overflow-y-auto pr-1">
            <x-form-section title="Informasi Surat Jalan" description="Pilih referensi PO, lalu lengkapi nomor dan tanggal pengiriman.">
                <div class="sm:col-span-2" x-show="!legacyMode">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Penjualan yang Dikirim</label>
                    <select x-model="form.data.sales_transaction_id" @change="selectSale()" :required="!legacyMode" :disabled="editMode" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Pilih penjualan melalui Surat Jalan</option>
                        <template x-for="sale in sales" :key="sale.value"><option :value="sale.value" x-text="sale.label"></option></template>
                    </select>
                    <p class="mt-1 text-xs text-error-500" x-text="form.getError('sales_transaction_id')"></p>
                    <x-info-help><p class="mt-1 text-xs text-gray-500">Disimpan sebagai Draft. Stok belum berkurang sampai Konfirmasi Dikirim.</p></x-info-help>
                </div>
                <p x-show="legacyMode" class="sm:col-span-2 text-sm text-gray-500">Data lama: hanya dokumen, tidak dapat melakukan pengeluaran stok baru.</p>
<div class="sm:col-span-2">                <x-select label="Referensi PO Konsumen (opsional)" name="delivery_po_id" :options="$orders" :searchable="true" placeholder="Pilih PO konsumen"
                    x-on:change="selectOrder($event.detail)" alpine-error="form.getError('customer_purchase_order_id')" /></div>
                <x-input label="No. Surat Jalan" name="number" type="text" x-model="form.data.number" x-bind:readonly="!editMode" placeholder="Otomatis saat disimpan sesuai aturan Profile" alpine-error="form.getError('number')" />
                <x-input label="Tanggal" name="delivery_date" type="date" x-model="form.data.delivery_date" alpine-error="form.getError('delivery_date')" required />
            </x-form-section>
            <x-form-section title="Data Customer" description="Penerima barang dan alamat tujuan pengiriman.">
                <x-input label="Kepada" name="recipient_name" type="text" x-model="form.data.recipient_name" alpine-error="form.getError('recipient_name')" required />
                <x-input label="Alamat Tujuan" name="destination_address" type="text" x-model="form.data.destination_address" alpine-error="form.getError('destination_address')" required />
            </x-form-section>
            <x-form-section title="Detail Barang" description="Informasi barang, berat, dan kemasan yang dikirim.">
                <x-input label="Deskripsi Barang" name="item_name" type="text" x-model="form.data.item_name" alpine-error="form.getError('item_name')" required />
                <x-input label="Jumlah (Kg)" name="quantity_kg" type="number" x-model="form.data.quantity_kg" alpine-error="form.getError('quantity_kg')" required min="0.01" max="999999999.99" step="0.01" />
                <div x-show="!legacyMode"><x-input label="Jumlah Karung Keluar" name="sack_count" type="number" min="0" step="1" x-model="form.data.sack_count" alpine-error="form.getError('sack_count')" /></div>
                <x-input label="Kemasan" name="packaging" type="text" x-model="form.data.packaging" alpine-error="form.getError('packaging')" />
                <x-input label="Keterangan Barang" name="remarks" type="text" x-model="form.data.remarks" alpine-error="form.getError('remarks')" />
            </x-form-section>
            <x-form-section title="Pengiriman & Catatan" description="Asal barang, penanggung jawab, dan catatan pengiriman.">
                <x-input label="Alamat Asal (opsional)" name="origin_address" x-model="form.data.origin_address" placeholder="Mengikuti alamat perusahaan jika kosong" alpine-error="form.getError('origin_address')" />
                <x-input label="Telepon / PIC Pengirim" name="sender_contact" x-model="form.data.sender_contact" placeholder="Mengikuti telepon perusahaan jika kosong" alpine-error="form.getError('sender_contact')" />
                <x-input label="Telepon / PIC Penerima" name="recipient_contact" x-model="form.data.recipient_contact" alpine-error="form.getError('recipient_contact')" />
                <x-input label="Asal Barang" name="origin" type="text" x-model="form.data.origin" alpine-error="form.getError('origin')" required />
                <x-input label="Dikirim Oleh" name="sender_name" type="text" x-model="form.data.sender_name" alpine-error="form.getError('sender_name')" />
                <x-input label="Diterima Oleh" name="receiver_name" type="text" x-model="form.data.receiver_name" alpine-error="form.getError('receiver_name')" />
                <x-input label="Catatan pada Surat Jalan" name="notes" type="text" x-model="form.data.notes" alpine-error="form.getError('notes')" />
            </x-form-section>

            <x-form-section title="Kendaraan & Kondisi Barang" description="Isian opsional untuk surat jalan. Netto tetap mengikuti jumlah pengiriman di atas.">
                <x-input label="No. Polisi Kendaraan" name="vehicle_plate" x-model="form.data.vehicle_plate" alpine-error="form.getError('vehicle_plate')" />
                <x-input label="Jenis Kendaraan" name="vehicle_type" x-model="form.data.vehicle_type" alpine-error="form.getError('vehicle_type')" />
                <x-input label="Nama Sopir" name="driver_name" x-model="form.data.driver_name" alpine-error="form.getError('driver_name')" />
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400" for="shipment-condition">Kondisi Barang</label>
                    <select id="shipment-condition" x-model="form.data.condition" class="h-11 w-full rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Belum ditentukan</option><option value="Kering">Kering</option><option value="Basah">Basah</option>
                    </select>
                    <p class="mt-1 text-xs text-error-500" x-text="form.getError('condition')"></p>
                </div>
                <x-input label="Bruto (Kg)" name="gross_weight_kg" type="number" min="0" step="0.01" x-model="form.data.gross_weight_kg" alpine-error="form.getError('gross_weight_kg')" />
                <x-input label="Tara (Kg)" name="tare_weight_kg" type="number" min="0" step="0.01" x-model="form.data.tare_weight_kg" alpine-error="form.getError('tare_weight_kg')" />
                <x-input label="Kadar Air (%)" name="moisture_percent" type="number" min="0" max="100" step="0.01" x-model="form.data.moisture_percent" alpine-error="form.getError('moisture_percent')" />
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
        message="Yakin ingin menghapus surat jalan ini?"
        @confirm-delete-delete-entity-modal.window="deleteConfirmed" />
</div>

@push('scripts')
    <script>
        function deliveryNoteForm() {
            return {
                orders: @js($orders),
                sales: @js($sales),
                legacyMode: false,
                editMode: false,
                editId: null,
                deleteId: null,

                form: useForm({
                    sales_transaction_id: '',
                    vehicle_plate: '', vehicle_type: '', driver_name: '', condition: '',
                    origin_address: '', sender_contact: '', recipient_contact: '',
                    gross_weight_kg: '', tare_weight_kg: '', moisture_percent: '',
                    sack_count: 0,
                    customer_purchase_order_id: '',
                    number: '',
                    delivery_date: '',
                    recipient_name: '',
                    destination_address: '',
                    origin: '',
                    item_name: '',
                    quantity_kg: '',
                    packaging: '',
                    remarks: '',
                    notes: '',
                    sender_name: '',
                    receiver_name: ''
                }),

                init() {
                    window.addEventListener('create-entity', () => this.create());
                    window.addEventListener('edit-entity', (event) => this.edit(event.detail));
                    window.addEventListener('delete-entity', (event) => this.deleteItem(event.detail));
                },

                create() {
                    this.legacyMode = false;
                    this.editMode = false;
                    this.editId = null;
                    this.form.reset();
                    this.form.data.delivery_date = window.getLocalDateInputValue();
                    this.$dispatch('set-selected-delivery_po_id', null);
                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'entity-modal');
                },

                edit(item) {
                    this.legacyMode = item.status === 'legacy';
                    this.editMode = true;
                    this.editId = item.id;
                    this.form.clearErrors();
                    this.form.data = {
                        sales_transaction_id: item.sales_transaction_id ?? '',
                        vehicle_plate: item.vehicle_plate ?? '', vehicle_type: item.vehicle_type ?? '',
                        driver_name: item.driver_name ?? '', condition: item.condition ?? '',
                        origin_address: item.origin_address ?? '', sender_contact: item.sender_contact ?? '',
                        recipient_contact: item.recipient_contact ?? '', gross_weight_kg: item.gross_weight_kg ?? '',
                        tare_weight_kg: item.tare_weight_kg ?? '', moisture_percent: item.moisture_percent ?? '',
                        sack_count: item.sack_count ?? 0,
                        customer_purchase_order_id: item.customer_purchase_order_id ?? '',
                        number: item.number ?? '',
                        delivery_date: item.delivery_date ?? '',
                        recipient_name: item.recipient_name ?? '',
                        destination_address: item.destination_address ?? '',
                        origin: item.origin ?? '',
                        item_name: item.item_name ?? '',
                        quantity_kg: item.quantity_kg ?? '',
                        packaging: item.packaging ?? '',
                        remarks: item.remarks ?? '',
                        notes: item.notes ?? '',
                        sender_name: item.sender_name ?? '',
                        receiver_name: item.receiver_name ?? ''
                    };
                    this.$dispatch('set-selected-delivery_po_id', item.customer_purchase_order_id ?? null);
                    this.$dispatch('open-modal', 'entity-modal');
                },

                selectOrder(id) {
                    if (String(this.form.data.customer_purchase_order_id || '') === String(id || '')) return;
                    this.form.data.customer_purchase_order_id = id || '';
                    const order = this.orders.find(order => String(order.value) === String(id));
                    if (order) {
                        const fields = this.form.data.sales_transaction_id ? ['destination_address'] : ['recipient_name', 'destination_address', 'item_name', 'quantity_kg'];
                        fields.forEach(key => this.form.data[key] = order[key] || '');
                    }
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-entity-modal');
                },

                selectSale() {
                    const sale = this.sales.find(sale => String(sale.value) === String(this.form.data.sales_transaction_id));
                    if (sale) ['recipient_name', 'destination_address', 'item_name', 'quantity_kg', 'sack_count', 'origin'].forEach(key => this.form.data[key] = sale[key] ?? '');
                    this.form.data.customer_purchase_order_id = sale?.customer_purchase_order_id || '';
                    this.$dispatch('set-selected-delivery_po_id', this.form.data.customer_purchase_order_id || null);
                },

                async deleteConfirmed() {
                    await this.form.submit('delete', `{{ url('surat-jalan') }}/${this.deleteId}`, {
                        onSuccess: (res) => {
                            toast.success('Success', res.data.message);
                            refreshDataTable('deliveryNoteTable');
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
                        `{{ url('surat-jalan') }}/${this.editId}` :
                        `{{ url('surat-jalan') }}`;
                    const method = this.editMode ? 'put' : 'post';

                    await this.form.submit(method, url, {
                        onSuccess: (res) => {
                            toast.success('Success', res.data.message);
                            refreshDataTable('deliveryNoteTable');
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
