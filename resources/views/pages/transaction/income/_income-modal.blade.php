<div x-data="incomeForm()">
    <x-modal name="entity-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Data' : 'Tambah Data'"></h4>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <x-input 
                    label="Tanggal"
                    name="income_date"
                    type="date"
                    x-model="form.data.income_date"
                    alpine-error="form.getError('income_date')"
                />

                <x-input 
                    label="Jumlah (Amount)"
                    name="amount"
                    x-model="form.data.amount"
                    :isCurrency="true"
                    alpine-error="form.getError('amount')"
                />

                <x-input 
                    label="Keterangan"
                    name="description"
                    x-model="form.data.description"
                    placeholder="Contoh: Hasil investasi"
                    alpine-error="form.getError('description')"
                />

                <x-input 
                    label="Catatan"
                    name="notes"
                    x-model="form.data.notes"
                    placeholder="Catatan tambahan (opsional)"
                    alpine-error="form.getError('notes')"
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
        message="Apakah anda yakin ingin menghapus pemasukan ini? Data yang dihapus tidak dapat dikembalikan."
        @confirm-delete-delete-entity-modal.window="deleteConfirmed"
    />
</div>

<script>
    function incomeForm() {
        return {
            editMode: false,
            editId: null,
            deleteId: null,

            form: useForm({
                income_date: window.getLocalDateInputValue(),
                amount: 0,
                description: '',
                notes: '',
            }),

            init() {
                window.addEventListener('create-entity', () => this.create());
                window.addEventListener('edit-entity', (event) => this.edit(event.detail));
                window.addEventListener('delete-entity', (event) => this.deleteItem(event.detail));
            },

            create() {
                this.editMode = false;
                this.editId = null;
                this.form.reset();
                this.form.clearErrors();
                this.form.data.income_date = window.getLocalDateInputValue();

                this.$dispatch('open-modal', 'entity-modal');
            },

            edit(item) {
                this.editMode = true;
                this.editId = item.id;
                this.form.clearErrors();
                
                this.form.data = {
                    income_date: item.income_date ? item.income_date.split('T')[0] : '',
                    amount: item.amount,
                    description: item.description || '',
                    notes: item.notes || '',
                };

                this.$dispatch('open-modal', 'entity-modal');
            },

            deleteItem(id) {
                this.deleteId = id;
                this.$dispatch('open-modal', 'delete-entity-modal');
            },

            async deleteConfirmed() {
                await this.form.submit('delete', `{{ url('manajemen-pemasukan') }}/${this.deleteId}`, {
                    onSuccess: (response) => {
                        toast.success('Berhasil', response.data.message);
                        refreshDataTable('incomeTable');
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
                    ? `{{ url('manajemen-pemasukan') }}/${this.editId}`
                    : `{{ url('manajemen-pemasukan') }}`;
                
                const method = this.editMode ? 'put' : 'post';

                await this.form.submit(method, url, {
                    onSuccess: (response) => {
                        toast.success('Berhasil', response.data.message);
                        refreshDataTable('incomeTable');
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
