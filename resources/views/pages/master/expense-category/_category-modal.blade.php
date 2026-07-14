<div x-data="expenseCategoryForm()">
    <x-modal name="entity-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Data' : 'Tambah Data'"></h4>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <x-input 
                    label="Nama Kategori"
                    name="name"
                    x-model="form.data.name"
                    placeholder="Contoh: Operasional"
                    :required="true"
                    alpine-error="form.getError('name')"
                />

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Tipe<span class="text-error-500">*</span>
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

                <x-select 
                    label="Status" 
                    name="is_active" 
                    :options="[['label' => 'Aktif', 'value' => 1], ['label' => 'Non-Aktif', 'value' => 0]]" 
                    placeholder="Pilih status"
                    :required="true"
                    x-on:change="$dispatch('update-field', { field: 'is_active', value: $event.detail })"
                    alpine-error="form.hasErrors('is_active') ? form.getError('is_active') : null" 
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
        message="Apakah anda yakin ingin menghapus kategori ini? Data yang dihapus tidak dapat dikembalikan."
        @confirm-delete-delete-entity-modal.window="deleteConfirmed"
    />
</div>

<script>
    function expenseCategoryForm() {
        return {
            editMode: false,
            editId: null,
            deleteId: null,

            form: useForm({
                name: '',
                type: 'expense',
                is_active: 1,
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

                window.dispatchEvent(new CustomEvent('set-selected-is_active', { detail: 1 }));

                this.$dispatch('open-modal', 'entity-modal');
            },

            edit(item) {
                this.editMode = true;
                this.editId = item.id;
                this.form.clearErrors();
                
                const isActive = item.is_active ? 1 : 0;

                this.form.data = {
                    name: item.name,
                    type: item.type || 'expense',
                    is_active: isActive,
                };

                window.dispatchEvent(new CustomEvent('set-selected-is_active', { detail: isActive }));

                this.$dispatch('open-modal', 'entity-modal');
            },

            deleteItem(id) {
                this.deleteId = id;
                this.$dispatch('open-modal', 'delete-entity-modal');
            },

            async deleteConfirmed() {
                await this.form.submit('delete', `{{ url('manajemen-kategori-pengeluaran') }}/${this.deleteId}`, {
                    onSuccess: (response) => {
                        toast.success('Berhasil', response.data.message);
                        refreshDataTable('expenseCategoryTable');
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
                    ? `{{ url('manajemen-kategori-pengeluaran') }}/${this.editId}`
                    : `{{ url('manajemen-kategori-pengeluaran') }}`;
                
                const method = this.editMode ? 'put' : 'post';

                await this.form.submit(method, url, {
                    onSuccess: (response) => {
                        toast.success('Berhasil', response.data.message);
                        refreshDataTable('expenseCategoryTable');
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
