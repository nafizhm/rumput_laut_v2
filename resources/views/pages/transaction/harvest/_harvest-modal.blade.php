<div x-data="harvestForm()">
    <x-modal name="harvest-modal" maxWidth="max-w-6xl">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Transaksi Panen' : 'Tambah Transaksi Panen'"></h4>

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                <div class="space-y-4 xl:col-span-8">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-select label="Petani" name="farmer_id" :options="$farmers" placeholder="Pilih Petani"
                            x-on:change="$dispatch('update-field', { field: 'farmer_id', value: $event.detail })"
                            alpine-error="form.getError('farmer_id')" required />

                        <x-input label="Tanggal Panen" name="harvest_date" type="date"
                            x-model="form.data.harvest_date" alpine-error="form.getError('harvest_date')" required />
                    </div>

                    <x-input label="Lokasi" name="location" placeholder="Contoh: Sawah Blok A"
                        x-model="form.data.location" alpine-error="form.getError('location')" required />

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <x-input label="Total Berat Bayar (Kg)" name="total_weight" placeholder="0"
                            x-model="form.data.total_weight" :isNumeric="true" :numericDecimals="2"
                            alpine-error="form.getError('total_weight')" required />

                        <x-input label="Jumlah Karung" name="sack_count" type="number" placeholder="0"
                            x-model="form.data.sack_count" alpine-error="form.getError('sack_count')" required />

                        <x-input label="Bonus / Karung (Kg)" name="extra_per_sack" x-model="form.data.extra_per_sack"
                            :isNumeric="true" :numericDecimals="2" alpine-error="form.getError('extra_per_sack')"
                            required />

                        <x-input label="Total Berat Gudang (Kg)" name="warehouse_weight"
                            x-model="form.data.warehouse_weight" :isNumeric="true" :numericDecimals="2"
                            alpine-error="form.getError('warehouse_weight')" readonly />
                    </div>

                    <div
                        class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800/70">
                        <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Rumus Bonus Karung</span>
                            <span class="font-semibold text-gray-900 dark:text-white/90"
                                x-text="`${Number(form.data.sack_count || 0).toLocaleString('id-ID')} x ${formatNumberValue(form.data.extra_per_sack)} Kg = ${formatWeight(totalBonusWeight)}`"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <x-input label="Harga / Kg" name="price_per_kg" placeholder="0" x-model="form.data.price_per_kg"
                            :isCurrency="true" alpine-error="form.getError('price_per_kg')" required />

                        <x-input label="Total Berat Timbang (Kg)" name="warehouse_weight_preview"
                            x-model="form.data.warehouse_weight" :isNumeric="true" :numericDecimals="2" readonly />

                        <x-select label="Jenis Potongan" name="sale_deduction_type" :options="[
                            ['label' => 'Tidak Ada', 'value' => 'none'],
                            ['label' => 'Persentase', 'value' => 'percentage'],
                            ['label' => 'Nominal', 'value' => 'nominal'],
                            ['label' => 'Berat (Kg)', 'value' => 'weight'],
                        ]"
                            x-model="form.data.sale_deduction_type"
                            x-on:change="$dispatch('update-field', { field: 'sale_deduction_type', value: $event.detail })"
                            alpine-error="form.getError('sale_deduction_type')" required />

                        <div>
                            <template x-if="form.data.sale_deduction_type !== 'none'">
                                <div>
                                    <x-input label="Nilai Potongan" name="sale_deduction_value"
                                        x-model="form.data.sale_deduction_value" :isNumeric="true" :numericDecimals="2"
                                        alpine-error="form.getError('sale_deduction_value')" />
                                </div>
                            </template>
                            <template x-if="form.data.sale_deduction_type === 'none'">
                                <div>
                                    <label
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nilai
                                        Potongan</label>
                                    <div
                                        class="flex h-11 items-center rounded-lg border border-gray-200 bg-gray-50 px-4 text-sm text-gray-400 dark:border-gray-700 dark:bg-gray-800/50">
                                        -</div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Potongan
                                Penjualan</label>
                            <div class="text-right tabular-nums flex h-11 items-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                x-text="formatCurrency(saleDeductionAmount)"></div>
                        </div>

                        <x-input label="Total Nilai Panen" name="total_harvest_value"
                            x-model="form.data.total_harvest_value" :isCurrency="true" readonly />

                        <x-input label="Potong Hutang" name="debt_deduction" placeholder="0"
                            x-model="form.data.debt_deduction" :isCurrency="true"
                            alpine-error="form.getError('debt_deduction')" />

                        <x-input label="Bayar Tunai (Otomatis)" name="cash_payout"
                            x-model="form.data.cash_payout" :isCurrency="true" readonly />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Catatan</label>
                        <textarea x-model="form.data.notes" placeholder="Tambahkan catatan di sini..."
                            class="block min-h-[96px] w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                    </div>
                </div>

                <div class="space-y-4 xl:col-span-4 mt-6">
                    <div
                        class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/70">
                        <h5 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Ringkasan Hutang</h5>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Sisa Hutang Saat Ini</span>
                                <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90"
                                    x-text="formatCurrency(farmer_current_debt)"></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Sisa Hutang Setelah Potongan</span>
                                <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90"
                                    x-text="formatCurrency(remainingDebtAfterCut)"></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/70">
                        <h5 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Ringkasan Timbangan</h5>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Total Berat Dibayar</span>
                                <span class="font-semibold text-gray-900 dark:text-white/90"
                                    x-text="formatWeight(form.data.total_weight)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Bonus Karung</span>
                                <span class="font-semibold text-gray-900 dark:text-white/90"
                                    x-text="formatWeight(totalBonusWeight)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Total Berat Timbang</span>
                                <span class="font-semibold text-gray-900 dark:text-white/90"
                                    x-text="formatWeight(form.data.warehouse_weight)"></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/70">
                        <h5 class="mb-3 text-sm font-semibold text-gray-800 dark:text-white/90">Ringkasan Pembayaran
                        </h5>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Nilai Kotor</span>
                                <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90"
                                    x-text="formatCurrency(baseHarvestValue)"></span>
                            </div>
                            <template x-if="form.data.sale_deduction_type !== 'none'">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400"
                                        x-text="deductionTypeLabel"></span>
                                    <span class="font-semibold text-gray-900 dark:text-white/90"
                                        x-text="deductionTypeValue"></span>
                                </div>
                            </template>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Potongan Penjualan</span>
                                <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90"
                                    x-text="formatCurrency(saleDeductionAmount)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Bayar Tunai</span>
                                <span class="text-right tabular-nums font-semibold text-gray-900 dark:text-white/90"
                                    x-text="formatCurrency(form.data.cash_payout)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'harvest-modal')"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    Batal
                </button>

                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600"
                    :disabled="form.processing || form.data.debt_deduction < 0">
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
                farmer_current_debt: 0,
                form: useForm({
                    farmer_id: '',
                    harvest_date: window.getLocalDateInputValue(),
                    location: '',
                    total_weight: 0,
                    sack_count: 0,
                    extra_per_sack: 3,
                    warehouse_weight: 0,
                    price_per_kg: 0,
                    sale_deduction_type: 'none',
                    sale_deduction_value: 0,
                    total_harvest_value: 0,
                    cash_payout: 0,
                    debt_deduction: 0,
                    notes: '',
                }),

                get totalBonusWeight() {
                    return (parseInt(this.form.data.sack_count) || 0) * (parseFloat(this.form.data.extra_per_sack) ||
                        0);
                },

                get baseHarvestValue() {
                    return (parseFloat(this.form.data.total_weight) || 0) * (parseFloat(this.form.data.price_per_kg) ||
                        0);
                },

                get saleDeductionAmount() {
                    const deductionValue = parseFloat(this.form.data.sale_deduction_value) || 0;
                    if (this.form.data.sale_deduction_type === 'percentage') {
                        return this.baseHarvestValue * (deductionValue / 100);
                    }
                    if (this.form.data.sale_deduction_type === 'nominal') {
                        return deductionValue;
                    }
                    if (this.form.data.sale_deduction_type === 'weight') {
                        return deductionValue * (parseFloat(this.form.data.price_per_kg) || 0);
                    }
                    return 0;
                },

                get netHarvestValue() {
                    return Math.max(0, this.baseHarvestValue - this.saleDeductionAmount);
                },

                get remainingDebtAfterCut() {
                    return Math.max(0, this.farmer_current_debt - (parseFloat(this.form.data.debt_deduction) || 0));
                },

                get deductionTypeLabel() {
                    const type = this.form.data.sale_deduction_type;
                    if (type === 'weight') return 'Potongan Berat';
                    if (type === 'percentage') return 'Potongan Persentase';
                    if (type === 'nominal') return 'Potongan Nominal';
                    return '';
                },

                get deductionTypeValue() {
                    const type = this.form.data.sale_deduction_type;
                    const value = parseFloat(this.form.data.sale_deduction_value) || 0;
                    if (type === 'weight') return formatWeight(value);
                    if (type === 'percentage') return value + '%';
                    if (type === 'nominal') return formatCurrency(value);
                    return '';
                },

                formatNumberValue(value) {
                    return Number(value || 0).toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },

                init() {
                    this.$watch('form.data.farmer_id', (val) => this.fetchFarmerDebt(val));
                    this.$watch('form.data.total_weight', () => this.calculateValues());
                    this.$watch('form.data.sack_count', () => this.calculateValues());
                    this.$watch('form.data.extra_per_sack', () => this.calculateValues());
                    this.$watch('form.data.price_per_kg', () => this.calculateValues());
                    this.$watch('form.data.sale_deduction_type', () => this.calculateValues());
                    this.$watch('form.data.sale_deduction_value', () => this.calculateValues());
                    this.$watch('form.data.debt_deduction', () => this.calculateCashFromDeduction());

                    window.addEventListener('update-field', (event) => {
                        const {
                            field,
                            value
                        } = event.detail;
                        this.form.data[field] = value;
                    });

                    window.addEventListener('edit-harvest-transaction', (event) => this.edit(event.detail));
                    window.addEventListener('delete-harvest-transaction', (event) => this.deleteItem(event.detail));
                    window.addEventListener('create-harvest-transaction', () => this.create());
                },

                calculateValues() {
                    const totalWeight = parseFloat(this.form.data.total_weight) || 0;
                    const sacks = parseInt(this.form.data.sack_count) || 0;
                    const extra = parseFloat(this.form.data.extra_per_sack) || 0;

                    this.form.data.warehouse_weight = (totalWeight + (sacks * extra)).toFixed(2);
                    this.form.data.total_harvest_value = Math.round(this.netHarvestValue);

                    this.calculateCashFromDeduction();
                },

                calculateCashFromDeduction() {
                    const deduction = parseFloat(this.form.data.debt_deduction) || 0;
                    const total = parseFloat(this.form.data.total_harvest_value) || 0;

                    let capped = deduction;
                    if (capped > total) {
                        capped = Math.round(total);
                        this.form.data.debt_deduction = capped;
                    }

                    this.form.data.cash_payout = Math.round(total - capped);
                },

                async fetchFarmerDebt(farmerId) {
                    if (!farmerId) {
                        this.farmer_current_debt = 0;
                        this.calculateValues();
                        return;
                    }
                    try {
                        const response = await axios.get(`/manajemen-petani/${farmerId}`);
                        this.farmer_current_debt = response.data.data.current_debt || 0;
                        this.calculateValues();
                    } catch (error) {
                        console.error('Failed to fetch farmer debt', error);
                        toast.error('Gagal', 'Gagal mengambil data hutang petani');
                    }
                },

                create() {
                    this.editMode = false;
                    this.editId = null;
                    this.form.reset();
                    this.form.clearErrors();
                    this.form.data.harvest_date = window.getLocalDateInputValue();
                    this.form.data.extra_per_sack = 3;
                    this.farmer_current_debt = 0;
                    window.dispatchEvent(new CustomEvent('set-selected-farmer_id', {
                        detail: null
                    }));
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
                        total_weight: item.total_weight || 0,
                        sack_count: item.sack_count || 0,
                        extra_per_sack: item.extra_per_sack || 1,
                        warehouse_weight: item.warehouse_weight || 0,
                        price_per_kg: item.price_per_kg || 0,
                        sale_deduction_type: item.sale_deduction_type || 'none',
                        sale_deduction_value: item.sale_deduction_value || 0,
                        total_harvest_value: item.total_harvest_value || 0,
                        cash_payout: item.cash_payout || 0,
                        debt_deduction: item.debt_deduction || 0,
                        notes: item.notes || '',
                    };

                    this.farmer_current_debt = (item.farmer?.current_debt || 0) + (item.debt_deduction || 0);
                    window.dispatchEvent(new CustomEvent('set-selected-farmer_id', {
                        detail: item.farmer_id
                    }));
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
                            toast.error('Gagal', error.response?.data?.message ||
                                'Terjadi kesalahan saat menghapus data');
                        }
                    });
                },

                async submit() {
                    const url = this.editMode ?
                        `{{ url('manajemen-panen') }}/${this.editId}` :
                        `{{ url('manajemen-panen') }}`;

                    const method = this.editMode ? 'put' : 'post';

                    await this.form.submit(method, url, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('harvestTransactionTable');
                            this.$dispatch('close-modal', 'harvest-modal');
                        },
                        onError: (error) => {
                            if (!this.form.hasErrors()) {
                                toast.error('Gagal', error.response?.data?.message ||
                                    'Terjadi kesalahan sistem');
                            }
                        }
                    });
                }
            }
        }
    </script>
@endpush
