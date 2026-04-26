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
                    alpine-error="form.getError('location')" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Total Berat (Kg)" name="gross_weight_kg" type="number" step="0.01" placeholder="0" x-model="form.data.gross_weight_kg"
                        alpine-error="form.getError('gross_weight_kg')" required />

                    <x-input label="Jumlah Karung" name="sack_count" type="number" placeholder="0" x-model="form.data.sack_count"
                        alpine-error="form.getError('sack_count')" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Potongan / Karung (Kg)" name="sack_deduction_kg" type="number" step="0.01" placeholder="0" x-model="form.data.sack_deduction_kg"
                        alpine-error="form.getError('sack_deduction_kg')" required />

                    <x-input label="Berat Bersih Bayar (Kg)" name="net_weight_kg" type="number" step="0.01" x-model="form.data.net_weight_kg"
                        alpine-error="form.getError('net_weight_kg')" readonly disabled />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Harga / Kg" name="price_per_kg" placeholder="0" x-model="form.data.price_per_kg" :isCurrency="true"
                        alpine-error="form.getError('price_per_kg')" required />

                    <x-input label="Total Nilai Panen" name="total_harvest_value" x-model="form.data.total_harvest_value" :isCurrency="true" readonly disabled />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input label="Bayar Tunai (Cash Payout)" name="cash_payout" placeholder="0" x-model="form.data.cash_payout" :isCurrency="true"
                        alpine-error="form.getError('cash_payout')" />

                    <x-input label="Potong Hutang (Otomatis)" name="debt_deduction" x-model="form.data.debt_deduction" :isCurrency="true" readonly disabled />
                </div>
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
                    gross_weight_kg: 0,
                    sack_count: 0,
                    sack_deduction_kg: 0,
                    net_weight_kg: 0,
                    price_per_kg: 0,
                    total_harvest_value: 0,
                    cash_payout: 0,
                    debt_deduction: 0,
                }),

                get totalDeduction() {
                    const sacks = parseInt(this.form.data.sack_count) || 0;
                    const deductionPerSack = parseFloat(this.form.data.sack_deduction_kg) || 0;
                    return sacks * deductionPerSack;
                },

                get netWeight() {
                    const gross = parseFloat(this.form.data.gross_weight_kg) || 0;
                    return gross - this.totalDeduction;
                },

                get totalHarvestValue() {
                    const weight = this.netWeight;
                    const price = parseFloat(this.form.data.price_per_kg) || 0;
                    return weight * price;
                },

                get debtDeduction() {
                    const total = this.totalHarvestValue;
                    const cash = parseFloat(this.form.data.cash_payout) || 0;
                    return Math.max(0, total - cash);
                },

                init() {
                    this.$watch('netWeight', (val) => this.form.data.net_weight_kg = val.toFixed(2));
                    this.$watch('totalHarvestValue', (val) => this.form.data.total_harvest_value = val);
                    this.$watch('debtDeduction', (val) => this.form.data.debt_deduction = val);

                    window.addEventListener('update-field', (event) => {
                        const { field, value } = event.detail;
                        this.form.data[field] = value;
                    });

                    window.addEventListener('edit-harvest-transaction', (event) => this.edit(event.detail));
                    window.addEventListener('delete-harvest-transaction', (event) => this.deleteItem(event.detail));
                    window.addEventListener('create-harvest-transaction', () => this.create());

                    this.$watch('totalHarvestValue', (value) => {
                        if (parseFloat(this.form.data.cash_payout) > value) {
                            this.form.data.cash_payout = value;
                        }
                    });
                },

                create() {
                    this.editMode = false;
                    this.editId = null;
                    this.form.reset();
                    this.form.clearErrors();
                    
                    window.dispatchEvent(new CustomEvent('set-selected-farmer_id', { detail: null }));

                    this.$dispatch('open-modal', 'harvest-modal');
                },

                edit(item) {
                    this.editMode = true;
                    this.editId = item.id;
                    this.form.clearErrors();

                    this.form.data = {
                        farmer_id: item.farmer_id || '',
                        harvest_date: item.harvest_date ? item.harvest_date.split('T')[0] : '',
                        location: item.location || '',
                        gross_weight_kg: item.gross_weight_kg || 0,
                        sack_count: item.sack_count || 0,
                        sack_deduction_kg: item.sack_deduction_kg || 0,
                        net_weight_kg: item.net_weight_kg || 0,
                        price_per_kg: item.price_per_kg || 0,
                        total_harvest_value: item.total_harvest_value || 0,
                        cash_payout: item.cash_payout || 0,
                        debt_deduction: item.debt_deduction || 0,
                    };

                    window.dispatchEvent(new CustomEvent('set-selected-farmer_id', { detail: item.farmer_id }));

                    this.$dispatch('open-modal', 'harvest-modal');
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-harvest-transaction-modal');
                },

                async deleteConfirmed() {
                    await this.form.submit('delete', `{{ url('manajemen-panen') }}/${this.deleteId}`, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('harvestTransactionTable');
                            this.$dispatch('close-modal', 'delete-harvest-transaction-modal');
                            this.deleteId = null;
                        },
                        onError: (error) => {
                            toast.error('Gagal', error.response?.data?.message || 'Terjadi kesalahan saat menghapus data');
                        }
                    });
                },

                async submit() {
                    const url = this.editMode 
                        ? `{{ url('manajemen-panen') }}/${this.editId}`
                        : `{{ url('manajemen-panen') }}`;
                    
                    const method = this.editMode ? 'put' : 'post';

                    await this.form.submit(method, url, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('harvestTransactionTable');
                            this.$dispatch('close-modal', 'harvest-modal');
                        },
                        onError: (error) => {
                            if (!this.form.hasErrors()) {
                                toast.error('Gagal', error.response?.data?.message || 'Terjadi kesalahan sistem');
                            }
                        }
                    });
                }
            }
        }
    </script>
@endpush
