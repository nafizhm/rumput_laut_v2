<div x-data="transactionForm()">
    <x-modal name="entity-modal" maxWidth="max-w-[720px]">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Data' : 'Tambah Data'"></h4>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Jenis<span class="text-error-500">*</span>
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="type" value="income" x-model="form.data.type"
                                class="w-4 h-4 text-brand-500 border-gray-300 focus:ring-brand-500 dark:border-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-400">Pemasukan</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="type" value="expense" x-model="form.data.type"
                                class="w-4 h-4 text-brand-500 border-gray-300 focus:ring-brand-500 dark:border-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-400">Pengeluaran</span>
                        </label>
                    </div>
                    <template x-if="form.getError('type')">
                        <p class="text-theme-xs text-error-500 mt-1.5" x-text="form.getError('type')"></p>
                    </template>
                </div>

                <x-input 
                    label="Tanggal"
                    name="date"
                    type="date"
                    x-model="form.data.date"
                    :required="true"
                    alpine-error="form.getError('date')"
                />

                <x-select label="Akun COA Transaksi" name="chart_of_account_id" :options="$accounts" :searchable="true"
                    placeholder="Pilih akun COA" required
                    x-on:change="$dispatch('update-field', { field: 'chart_of_account_id', value: $event.detail })"
                    alpine-error="form.getError('chart_of_account_id')" />
                <x-select label="Akun Kas / Bank" name="cash_account_id" :options="$cashAccounts" :searchable="true"
                    placeholder="Pilih kas atau rekening bank" required
                    x-on:change="$dispatch('update-field', { field: 'cash_account_id', value: $event.detail })"
                    alpine-error="form.getError('cash_account_id')" />
                <div class="rounded-lg bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <p x-text="form.data.type === 'income' ? 'Debit: akun kas/bank. Kredit: akun COA transaksi.' : 'Debit: akun COA transaksi. Kredit: akun kas/bank.'"></p>
                    <x-info-help><p class="mt-1 text-xs text-gray-500">Jurnal seimbang dibuat otomatis saat disimpan. Akun transaksi dan kas/bank harus berbeda.</p></x-info-help>
                    <p class="mt-1 text-xs text-error-500" x-text="form.getError('accounting') || form.getError('date')"></p>
                </div>

                <x-input 
                    label="Jumlah"
                    name="amount"
                    x-model="form.data.amount"
                    :isCurrency="true"
                    :required="true"
                    alpine-error="form.getError('amount')"
                />

                <x-input 
                    label="Keterangan"
                    name="description"
                    x-model="form.data.description"
                    placeholder="Contoh: Hasil investasi"
                    alpine-error="form.getError('description')"
                />


            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button 
                    type="button" 
                    @click="$dispatch('close-modal', 'entity-modal')" 
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                    Batal
                </button>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600"
                >
                    <span x-show="!form.processing">Simpan</span>
                    <span x-show="form.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-delete-modal
        name="delete-entity-modal"
        message="Apakah anda yakin ingin menghapus transaksi ini? Data yang dihapus tidak dapat dikembalikan."
        @confirm-delete-delete-entity-modal.window="deleteConfirmed"
    />
</div>

<script>
    function transactionForm() {
        return {
            editMode: false,
            editId: null,
            deleteId: null,

            form: useForm({
                type: 'income',
                date: window.getLocalDateInputValue(),
                amount: 0,
                description: '',
                chart_of_account_id: '',
                cash_account_id: '',
            }),

            init() {
                window.addEventListener('update-field', (event) => {
                    const { field, value } = event.detail;
                    this.form.data[field] = value;
                });

                window.addEventListener('create-entity', () => this.create());
                window.addEventListener('edit-entity', (event) => this.edit(event.detail));
                window.addEventListener('delete-entity', (event) => this.deleteItem(event.detail));
            },

            create() {
                this.editMode = false;
                this.editId = null;
                this.form.reset();
                this.form.clearErrors();
                this.form.data.type = 'income';
                this.form.data.date = window.getLocalDateInputValue();

                window.dispatchEvent(new CustomEvent('set-selected-chart_of_account_id', { detail: null }));
                window.dispatchEvent(new CustomEvent('set-selected-cash_account_id', { detail: null }));

                this.$dispatch('open-modal', 'entity-modal');
            },

            edit(item) {
                this.editMode = true;
                this.editId = item.id;
                this.form.clearErrors();
                
                this.form.data = {
                    type: item.type,
                    date: item.date ? item.date.split('T')[0] : '',
                    amount: item.amount,
                    description: item.description || '',
                    chart_of_account_id: item.chart_of_account_id || '',
                    cash_account_id: item.cash_account_id || '',
                };

                window.dispatchEvent(new CustomEvent('set-selected-chart_of_account_id', { detail: item.chart_of_account_id }));
                window.dispatchEvent(new CustomEvent('set-selected-cash_account_id', { detail: item.cash_account_id }));

                this.$dispatch('open-modal', 'entity-modal');
            },

            deleteItem(id) {
                this.deleteId = id;
                this.$dispatch('open-modal', 'delete-entity-modal');
            },

            async deleteConfirmed() {
                await this.form.submit('delete', `{{ url('manajemen-pemasukan-pengeluaran') }}/${this.deleteId}`, {
                    onSuccess: (response) => {
                        toast.success('Berhasil', response.data.message);
                        refreshDataTable('transactionTable');
                        this.$dispatch('close-modal', 'delete-entity-modal');
                        this.deleteId = null;
                    },
                    onError: (error) => {
                        toast.error('Gagal', error.response?.data?.message || 'Terjadi kesalahan saat menghapus data');
                    }
                });
            },

            async submit() {
                const url = this.editMode 
                    ? `{{ url('manajemen-pemasukan-pengeluaran') }}/${this.editId}`
                    : `{{ url('manajemen-pemasukan-pengeluaran') }}`;
                
                const method = this.editMode ? 'put' : 'post';

                await this.form.submit(method, url, {
                    onSuccess: (response) => {
                        toast.success('Berhasil', response.data.message);
                        refreshDataTable('transactionTable');
                        this.$dispatch('close-modal', 'entity-modal');
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
