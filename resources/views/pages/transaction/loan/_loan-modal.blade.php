<div x-data="loanForm()">
    <x-modal name="loan-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Pinjaman' : 'Tambah Pinjaman'"></h4>
        
        <form @submit.prevent="submit">
            <div class="space-y-4">
                <x-select label="Petani" name="farmer_id" :options="$farmers" placeholder="Pilih Petani"
                    x-on:change="$dispatch('update-field', { field: 'farmer_id', value: $event.detail })"
                    alpine-error="form.getError('farmer_id')" required />

                <x-input label="Tanggal" name="loan_date" type="date" x-model="form.data.loan_date"
                    alpine-error="form.getError('loan_date')" required />

                <x-input label="Jumlah" name="amount" placeholder="Contoh: 1.000.000" x-model="form.data.amount" :isCurrency="true"
                    alpine-error="form.getError('amount')" required />

                <x-input label="Keterangan" name="description" placeholder="Contoh: Pinjaman Pupuk" x-model="form.data.description"
                    alpine-error="form.getError('description')" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="$dispatch('close-modal', 'loan-modal')"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    Batal
                </button>

                <button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600"
                    :disabled="form.processing">
                    <span x-show="!form.processing">Simpan</span>
                    <span x-show="form.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-delete-modal name="delete-loan-modal"
        message="Apakah anda yakin ingin menghapus pinjaman ini? Tindakan ini akan menambah catatan kredit pada ledger petani."
        @confirm-delete-delete-loan-modal.window="deleteConfirmed" />
</div>

@push('scripts')
    <script>
        function loanForm() {
            return {
                editMode: false,
                editId: null,
                deleteId: null,
                form: useForm({
                    farmer_id: '',
                    loan_date: new Date().toISOString().split('T')[0],
                    amount: 0,
                    description: ''
                }),

                init() {
                    window.addEventListener('update-field', (event) => {
                        const { field, value } = event.detail;
                        this.form.data[field] = value;
                    });

                    window.addEventListener('edit-loan', (event) => this.edit(event.detail));
                    window.addEventListener('delete-loan', (event) => this.deleteItem(event.detail));
                    window.addEventListener('create-loan', () => this.create());
                },

                create() {
                    this.editMode = false;
                    this.editId = null;
                    this.form.reset();
                    this.form.clearErrors();
                    
                    window.dispatchEvent(new CustomEvent('set-selected-farmer_id', { detail: null }));

                    this.$dispatch('open-modal', 'loan-modal');
                },

                edit(item) {
                    this.editMode = true;
                    this.editId = item.id;
                    this.form.clearErrors();

                    this.form.data = {
                        farmer_id: item.farmer_id || '',
                        loan_date: item.loan_date ? item.loan_date.split('T')[0] : '',
                        amount: item.amount || 0,
                        description: item.description || ''
                    };

                    window.dispatchEvent(new CustomEvent('set-selected-farmer_id', { detail: item.farmer_id }));

                    this.$dispatch('open-modal', 'loan-modal');
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-loan-modal');
                },

                async deleteConfirmed() {
                    await this.form.submit('delete', `{{ url('manajemen-pinjaman') }}/${this.deleteId}`, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('loanTable');
                            this.$dispatch('close-modal', 'delete-loan-modal');
                            this.deleteId = null;
                        },
                        onError: (error) => {
                            toast.error('Gagal', error.response?.data?.message || 'Terjadi kesalahan saat menghapus data');
                        }
                    });
                },

                async submit() {
                    const url = this.editMode 
                        ? `{{ url('manajemen-pinjaman') }}/${this.editId}`
                        : `{{ url('manajemen-pinjaman') }}`;
                    
                    const method = this.editMode ? 'put' : 'post';

                    await this.form.submit(method, url, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('loanTable');
                            this.$dispatch('close-modal', 'loan-modal');
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
