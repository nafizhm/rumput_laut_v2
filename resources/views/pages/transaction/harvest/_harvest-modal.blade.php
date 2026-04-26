<div x-data="harvestForm()">
    <x-modal name="harvest-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Transaksi Panen' : 'Tambah Transaksi Panen'"></h4>
        
        <form @submit.prevent="submit">
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select label="Petani" name="farmer_id" :options="$farmers" placeholder="Pilih Petani"
                        x-on:change="$dispatch('update-field', { field: 'farmer_id', value: $event.detail })"
                        alpine-error="form.getError('farmer_id')" required />

                    <x-input label="Tanggal Panen" name="harvest_date" type="date" x-model="form.data.harvest_date"
                        alpine-error="form.getError('harvest_date')" required />
                </div>

                <x-input label="Lokasi" name="location" placeholder="Contoh: Sawah Blok A" x-model="form.data.location"
                    alpine-error="form.getError('location')" required />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-input label="Berat (Kg)" name="wet_weight_kg" type="number" placeholder="0" x-model="form.data.wet_weight_kg"
                        alpine-error="form.getError('wet_weight_kg')" required />

                    <x-input label="Harga / Kg" name="price_per_kg" placeholder="0" x-model="form.data.price_per_kg" :isCurrency="true"
                        alpine-error="form.getError('price_per_kg')" required />

                    <x-input label="Jumlah Karung" name="sack_count" type="number" placeholder="0" x-model="form.data.sack_count"
                        alpine-error="form.getError('sack_count')" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Total Nilai Panen" name="total_harvest_value" x-model="form.data.total_harvest_value" :isCurrency="true" disabled />
                    
                    <x-input label="Bayar Tunai (Cash Payout)" name="cash_payout" placeholder="0" x-model="form.data.cash_payout" :isCurrency="true"
                        alpine-error="form.getError('cash_payout')" />
                </div>

                <x-input label="Potong Hutang (Otomatis)" name="debt_deduction" x-model="form.data.debt_deduction" :isCurrency="true" disabled />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="$dispatch('close-modal', 'harvest-modal')"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    Batal
                </button>

                <button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600"
                    :disabled="form.processing || debtDeduction < 0">
                    <span x-show="!form.processing">Simpan</span>
                    <span x-show="form.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-delete-modal name="delete-harvest-transaction-modal"
        message="Apakah anda yakin ingin menghapus transaksi panen ini? Potongan hutang akan dikembalikan ke saldo hutang petani."
        @confirm-delete-delete-harvest-transaction-modal.window="deleteConfirmed" />
</div>

@push('scripts')
    <script>
        function harvestForm() {
            return {
                editMode: false,
                editId: null,
                deleteId: null,
                form: useForm({
                    farmer_id: '',
                    harvest_date: new Date().toISOString().split('T')[0],
                    location: '',
                    wet_weight_kg: 0,
                    price_per_kg: 0,
                    sack_count: 0,
                    cash_payout: 0,
                    total_harvest_value: 0,
                    debt_deduction: 0,
                }),

                get totalHarvestValue() {
                    const weight = parseFloat(this.form.data.wet_weight_kg) || 0;
                    const price = parseFloat(this.form.data.price_per_kg) || 0;
                    return weight * price;
                },

                get debtDeduction() {
                    const total = this.totalHarvestValue;
                    const cash = parseFloat(this.form.data.cash_payout) || 0;
                    return Math.max(0, total - cash);
                },

                init() {
                    // Sync calculated values to form data
                    this.$watch('totalHarvestValue', (val) => this.form.data.total_harvest_value = val);
                    this.$watch('debtDeduction', (val) => this.form.data.debt_deduction = val);

                    window.addEventListener('update-field', (event) => {
                        const { field, value } = event.detail;
                        this.form.data[field] = value;
                    });

                    window.addEventListener('edit-harvest-transaction', (event) => {
                        this.edit(event.detail);
                    });
                    window.addEventListener('delete-harvest-transaction', (event) => {
                        this.deleteItem(event.detail);
                    });
                    window.addEventListener('create-harvest-transaction', () => {
                        this.create();
                    });

                    // Watch for total harvest value changes to ensure cash_payout doesn't exceed it
                    this.$watch('totalHarvestValue', (value) => {
                        if (parseFloat(this.form.data.cash_payout) > value) {
                            this.form.data.cash_payout = value;
                        }
                    });
                },

                create() {
                    this.form.reset();
                    this.editMode = false;
                    this.editId = null;
                    
                    window.dispatchEvent(new CustomEvent('set-selected-farmer_id', {
                        detail: null
                    }));

                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'harvest-modal');
                },

                edit(item) {
                    this.editMode = true;
                    this.editId = item.id;

                    this.form.data = {
                        farmer_id: item.farmer_id || '',
                        harvest_date: item.harvest_date ? item.harvest_date.split('T')[0] : '',
                        location: item.location || '',
                        wet_weight_kg: item.wet_weight_kg || 0,
                        price_per_kg: item.price_per_kg || 0,
                        sack_count: item.sack_count || 0,
                        cash_payout: item.cash_payout || 0,
                        total_harvest_value: item.total_harvest_value || 0,
                        debt_deduction: item.debt_deduction || 0,
                    };

                    window.dispatchEvent(new CustomEvent('set-selected-farmer_id', {
                        detail: item.farmer_id
                    }));

                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'harvest-modal');
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-harvest-transaction-modal');
                },

                async deleteConfirmed() {
                    try {
                        await this.form.delete(`{{ url('manajemen-panen') }}/${this.deleteId}`, {
                            onSuccess: () => {
                                refreshDataTable('harvestTransactionTable');
                                this.$dispatch('close-modal', 'delete-harvest-transaction-modal');
                                toast.success('Berhasil', 'Transaksi panen berhasil dihapus');
                                this.deleteId = null;
                            },
                            onError: () => {
                                toast.error('Gagal', 'Terjadi kesalahan saat menghapus transaksi');
                            }
                        });
                    } catch (e) {}
                },

                async submit() {
                    try {
                        const url = this.editMode 
                            ? `{{ url('manajemen-panen') }}/${this.editId}`
                            : '{{ route('manajemen-panen.store') }}';
                        
                        const method = this.editMode ? 'put' : 'post';

                        await this.form[method](url, {
                            onSuccess: () => {
                                this.form.reset();
                                this.editMode = false;
                                this.editId = null;
                                this.$dispatch('close-modal', 'harvest-modal');
                                refreshDataTable('harvestTransactionTable');
                                toast.success('Berhasil', `Transaksi panen berhasil ${this.editMode ? 'diperbarui' : 'disimpan'}`);
                            },
                            onError: (e) => {
                                if (!this.form.hasErrors()) {
                                    toast.error('Gagal', `Terjadi kesalahan saat ${this.editMode ? 'memperbarui' : 'menyimpan'} transaksi`);
                                }
                            }
                        });
                    } catch (e) {}
                }
            }
        }
    </script>
@endpush
