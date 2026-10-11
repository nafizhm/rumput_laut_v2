<div x-data="customerPoForm()">
    <x-modal name="entity-modal" maxWidth="max-w-[960px]">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="viewMode ? 'Detail PO konsumen' : (editMode ? 'Edit PO konsumen' : 'Tambah PO konsumen')"></h4>

        <form @submit.prevent="submit" class="max-h-[70vh] overflow-y-auto pr-1">
<fieldset :disabled="viewMode || form.processing">
            <x-form-section title="Informasi PO" description="Nomor dan tanggal dokumen pesanan.">
                <x-input label="Nomor PO" name="po_number" type="text" x-model="form.data.po_number" alpine-error="form.getError('po_number')" required />
                <x-input label="Tanggal PO" name="po_date" type="date" x-model="form.data.po_date" alpine-error="form.getError('po_date')" required />
            </x-form-section>
            <x-form-section title="Data Customer" description="Identitas dan kontak konsumen dari data pabrik.">
                <div>
                    <div x-show="!viewMode">
                        <x-select label="Nama Konsumen (Pabrik)" name="po_factory_id" :options="$factories" :searchable="true"
                            placeholder="Pilih pabrik" x-on:change="selectFactory($event.detail)"
                            alpine-error="form.getError('factory_id')" required />
                    </div>
                    <div x-show="viewMode">
                        <x-input label="Nama Konsumen" name="customer_name" x-model="form.data.customer_name" readonly />
                    </div>
                    <p x-show="!viewMode && editMode && !form.data.factory_id" class="mt-1 text-xs text-gray-500">Pilih pabrik untuk PO lama ini.</p>
                </div>
                <x-input label="Telepon Konsumen" name="customer_phone" type="text" readonly x-model="form.data.customer_phone" alpine-error="form.getError('customer_phone')" />
                <x-input label="Alamat Konsumen" name="customer_address" type="text" readonly x-model="form.data.customer_address" alpine-error="form.getError('customer_address')" />
            </x-form-section>
            <x-form-section title="Detail Barang & Harga" description="Tambahkan barang, jumlah, dan harga per kg untuk setiap barang pesanan.">
                <div class="sm:col-span-2 space-y-3">
                    <template x-for="(item, index) in form.data.items" :key="index">
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-[2fr_1fr_1fr_1fr_auto]">
                                <p class="text-sm">Barang: Rumput Laut</p>
                                <label class="text-sm">Jumlah (Kg)<input type="number" x-model="item.quantity_kg" required min="0.01" max="999999999.99" step="0.01" class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 px-3 dark:border-gray-700 dark:bg-gray-900"></label>
                                <label class="text-sm">Harga / Kg (Rp)<input type="number" x-model="item.price_per_kg" required min="0" max="999999999.99" step="0.01" class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 px-3 dark:border-gray-700 dark:bg-gray-900"></label>
                                <div class="text-sm">Subtotal<div class="mt-1.5 flex h-11 items-center justify-end text-right tabular-nums" x-text="formatCurrency(Number(item.quantity_kg || 0) * Number(item.price_per_kg || 0))"></div></div>
                                <button type="button" x-show="!viewMode && form.data.items.length > 1" @click="form.data.items.splice(index, 1)" :aria-label="'Hapus barang ' + (index + 1)" class="h-11 rounded-lg border border-red-200 px-3 text-sm text-red-600">Hapus</button>
                            </div>
                            <p class="mt-1 text-xs text-error-500" x-text="form.getError('items.' + index + '.quality_grade_id') || form.getError('items.' + index + '.quantity_kg') || form.getError('items.' + index + '.price_per_kg') || form.getError('items.' + index) || form.getError('items.' + index + '.id')"></p>
                        </div>
                    </template>
                    <button type="button" x-show="!viewMode" @click="form.data.items.push(newItem())" class="rounded-lg border border-brand-300 px-4 py-2 text-sm text-brand-600">+ Tambah Barang</button>
                    <p class="text-xs text-error-500" x-text="form.getError('items')"></p>
                    <div class="rounded-lg bg-gray-50 p-4 text-sm font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">Total PO: <span x-text="formatCurrency(poTotal)" class="tabular-nums"></span></div>
                </div>
            </x-form-section>
            <x-form-section title="Pengiriman & Catatan" description="Jadwal pengiriman dan informasi tambahan pesanan.">
                <x-input label="Tanggal Pengiriman" name="delivery_date" type="date" x-model="form.data.delivery_date" alpine-error="form.getError('delivery_date')" />
                <x-input label="Catatan" name="notes" type="text" x-model="form.data.notes" alpine-error="form.getError('notes')" />
            </x-form-section>
            </fieldset>
            <x-form-section title="Lampiran PO" description="Dokumen pendukung pesanan konsumen.">
            <div class="sm:col-span-2 space-y-3">
                <div x-show="!viewMode">
                    <label for="po-file" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">File PO</label>
                    <input id="po-file" type="file" x-ref="poFile" accept=".pdf,.jpg,.jpeg,.png" @change="selectFile($event)" :disabled="form.processing"
                        class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <x-info-help label="Format file PO">PDF, JPG, atau PNG. Maksimal 4 MB.</x-info-help>
                    <p x-show="form.getError('po_file')" x-text="form.getError('po_file')" class="mt-1 text-xs text-error-500"></p>
                </div>
                <template x-if="previewUrl">
                    <div class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-2">
                            <a :href="previewUrl" target="_blank" rel="noopener" class="break-all text-sm text-brand-500" x-text="fileName"></a>
                            <button type="button" x-show="!viewMode" @click="removeFile()" :disabled="form.processing" class="rounded-lg bg-rose-500 px-3 py-2 text-sm text-white">Hapus file</button>
                        </div>
                        <template x-if="fileMime.startsWith('image/')">
                            <img :src="previewUrl" alt="Preview file PO" class="max-h-64 w-full rounded-lg object-contain" />
                        </template>
                        <template x-if="fileMime === 'application/pdf'">
                            <iframe :src="previewUrl" title="Preview file PO" class="h-64 w-full rounded-lg"></iframe>
                        </template>
                    </div>
                </template>
                <p x-show="form.data.remove_po_file" class="text-xs text-gray-500">File akan dihapus setelah klik Simpan.</p>
                <p x-show="viewMode && !previewUrl" class="text-sm text-gray-500">Belum ada file PO.</p>
            </div>
            </x-form-section>

            <div class="sticky bottom-0 z-10 mt-6 flex justify-end gap-3 border-t border-gray-200 bg-white py-4 dark:border-gray-700 dark:bg-gray-900">
                <button type="button" @click="$dispatch('close-modal', 'entity-modal')"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    Tutup
                </button>

                <button type="submit" x-show="!viewMode" :disabled="form.processing"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!form.processing">Simpan</span>
                    <span x-show="form.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-delete-modal name="delete-entity-modal"
        message="Yakin ingin menghapus PO konsumen ini? Data yang dihapus tidak dapat dikembalikan."
        @confirm-delete-delete-entity-modal.window="deleteConfirmed" />
</div>

@push('scripts')
    <script>
        function customerPoForm() {
            return {
                factories: @js($factories),
                previewUrl: '',
                fileName: '',
                fileMime: '',
                viewMode: false,
                editMode: false,
                editId: null,
                deleteId: null,

                form: useForm({
                    po_number: '',
                    po_date: window.getLocalDateInputValue(),
                    factory_id: '',
                    po_file: null,
                    remove_po_file: false,
                    customer_name: '',
                    customer_phone: '',
                    customer_address: '',
                    items: [{id: null, quality_grade_id: '', quantity_kg: '', price_per_kg: ''}],
                    delivery_date: '',
                    notes: ''
                }),

                init() {
                    window.addEventListener('view-entity', (event) => { this.edit(event.detail); this.viewMode = true; });
                    window.addEventListener('create-entity', () => this.create());
                    window.addEventListener('edit-entity', (event) => this.edit(event.detail));
                    window.addEventListener('delete-entity', (event) => this.deleteItem(event.detail));
                },

                create() {
                    this.viewMode = false;
                    this.editMode = false;
                    this.editId = null;
                    this.resetFile();
                    this.form.reset();
                    this.form.data.items = [this.newItem()];
                    this.form.data.po_date = window.getLocalDateInputValue();
                    this.$dispatch('set-selected-po_factory_id', null);
                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'entity-modal');
                },

                edit(item) {
                    this.viewMode = false;
                    this.editMode = true;
                    this.editId = item.id;
                    this.resetFile();
                    this.form.clearErrors();
                    this.form.data = {
                        factory_id: item.factory_id ?? '',
                        po_file: null,
                        remove_po_file: false,
                        po_number: item.po_number ?? '',
                        po_date: item.po_date ?? '',
                        customer_name: item.customer_name ?? '',
                        customer_phone: item.customer_phone ?? '',
                        customer_address: item.customer_address ?? '',
                        items: item.items?.length ? item.items.map(row => ({id: row.id, quality_grade_id: row.quality_grade_id || '', quantity_kg: row.quantity_kg, price_per_kg: row.price_per_kg})) : [{id: null, quality_grade_id: '', quantity_kg: item.quantity_kg, price_per_kg: item.price_per_kg}],
                        delivery_date: item.delivery_date ?? '',
                        notes: item.notes ?? ''
                    };
                    this.previewUrl = item.po_file_url || '';
                    this.fileName = item.po_file_name || '';
                    this.fileMime = item.po_file_mime || '';
                    this.$dispatch('set-selected-po_factory_id', item.factory_id ?? null);
                    this.$dispatch('open-modal', 'entity-modal');
                },

                newItem() { return {id: null, quality_grade_id: '', quantity_kg: '', price_per_kg: ''}; },
                get poTotal() { return this.form.data.items.reduce((sum, item) => sum + Number(item.quantity_kg || 0) * Number(item.price_per_kg || 0), 0); },

                selectFactory(id) {
                    if (this.viewMode || String(this.form.data.factory_id || '') === String(id || '')) return;
                    this.form.data.factory_id = id || '';
                    const factory = this.factories.find(item => String(item.value) === String(id));
                    this.form.data.customer_name = factory?.label || '';
                    this.form.data.customer_phone = factory?.phone || '';
                    this.form.data.customer_address = factory?.address || '';
                },

                resetFile() {
                    if (this.previewUrl.startsWith('blob:')) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = '';
                    this.fileName = '';
                    this.fileMime = '';
                    if (this.$refs.poFile) this.$refs.poFile.value = '';
                },

                selectFile(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.form.clearErrors('po_file');
                    if (!['application/pdf', 'image/jpeg', 'image/png'].includes(file.type) || file.size > 4 * 1024 * 1024) {
                        this.form.setError('po_file', 'Pilih PDF, JPG, atau PNG maksimal 4 MB.');
                        event.target.value = '';
                        return;
                    }
                    this.resetFile();
                    this.form.data.po_file = file;
                    this.form.data.remove_po_file = false;
                    this.previewUrl = URL.createObjectURL(file);
                    this.fileName = file.name;
                    this.fileMime = file.type;
                },

                removeFile() {
                    this.resetFile();
                    this.form.data.po_file = null;
                    this.form.data.remove_po_file = true;
                    this.form.clearErrors('po_file');
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-entity-modal');
                },

                async deleteConfirmed() {
                    await this.form.submit('delete', `{{ url('po-konsumen') }}/${this.deleteId}`, {
                        onSuccess: (res) => {
                            toast.success('Success', res.data.message);
                            refreshDataTable('customerPoTable');
                            this.$dispatch('close-modal', 'delete-entity-modal');
                        },
                        onError: (err) => {
                            toast.error('Error', err.response?.data?.message ||
                                'Terjadi kesalahan saat menghapus data');
                        }
                    });
                },

                async submit() {
                    if (this.viewMode || this.form.processing) return;
                    const url = this.editMode ?
                        `{{ url('po-konsumen') }}/${this.editId}` :
                        `{{ url('po-konsumen') }}`;
                    const method = 'post';

                    await this.form.submit(method, url, {
                        transform: (data) => {
                            const body = new FormData();
                            Object.entries(data).forEach(([key, value]) => {
                                if (key === 'items') {
                                    value.forEach((item, index) => Object.entries(item).forEach(([field, entry]) => {
                                        if (entry !== null && entry !== undefined) body.append('items[' + index + '][' + field + ']', entry);
                                    }));
                                } else if (value !== null && value !== undefined) body.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
                            });
                            if (this.editMode) body.append('_method', 'PUT');
                            return body;
                        },
                        onSuccess: (res) => {
                            toast.success('Success', res.data.message);
                            refreshDataTable('customerPoTable');
                            this.$dispatch('close-modal', 'entity-modal');
                        },
                        onError: (err) => {
                            if (err.response?.status !== 422) {
                                toast.error('Error', err.response?.data?.message ||
                                    'Terjadi kesalahan sistem');
                            }
                        }
                    });
                }
            }
        }
    </script>
@endpush
