 <div x-data="menuForm()">
    <x-modal name="menuModal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Menu' : 'Tambah Menu'"></h4>
        <form @submit.prevent="submit">
            <div class="space-y-4">
                <x-input label="Nama Menu" name="name" placeholder="Contoh: Dashboard" x-model="form.data.name"
                    alpine-error="form.hasErrors('name') ? form.getError('name') : null" required />

                <x-input label="Icon" name="icon" placeholder="Contoh: lucide-home" x-model="form.data.icon"
                    alpine-error="form.hasErrors('icon') ? form.getError('icon') : null" />

                <x-input label="Route" name="route" placeholder="Contoh: dashboard.index" x-model="form.data.route"
                    alpine-error="form.hasErrors('route') ? form.getError('route') : null" />

                <x-input label="URL" name="url" placeholder="Contoh: /dashboard" x-model="form.data.url"
                    alpine-error="form.hasErrors('url') ? form.getError('url') : null" />

                <x-select label="Parent" name="parent_id" :options="$parents" placeholder="Pilih parent"
                    x-on:change="$dispatch('update-field', { field: 'parent_id', value: $event.detail })"
                    alpine-error="form.hasErrors('parent_id') ? form.getError('parent_id') : null" />

                <x-input label="Order" name="order" type="number" placeholder="Contoh: 1" x-model="form.data.order"
                    alpine-error="form.hasErrors('order') ? form.getError('order') : null" />

                <x-select label="Status" name="is_active" :options="[['label' => 'Active', 'value' => 1], ['label' => 'Inactive', 'value' => 0]]" placeholder="Pilih status"
                    x-on:change="$dispatch('update-field', { field: 'is_active', value: $event.detail })"
                    alpine-error="form.hasErrors('is_active') ? form.getError('is_active') : null" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="$dispatch('close-modal', 'menuModal')"
                    class="rounded-lg border justify-center border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700">
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

    <x-delete-modal name="delete-menu-modal"
        message="Apakah anda yakin ingin menghapus menu ini? Menu yang dihapus tidak dapat dikembalikan."
        @confirm-delete-delete-menu-modal.window="deleteConfirmed" />
</div>

@push('scripts')
    <script>
        function menuForm() {
            return {
                editMode: false,
                editId: null,
                deleteId: null,
                form: useForm({
                    name: '',
                    icon: '',
                    route: '',
                    url: '',
                    parent_id: '',
                    order: '',
                    is_active: true
                }),

                init() {
                    window.addEventListener('update-field', (event) => {
                        const { field, value } = event.detail;
                        this.form.data[field] = value;
                    });

                    window.addEventListener('edit-menu', (event) => this.edit(event.detail));
                    window.addEventListener('delete-menu', (event) => this.deleteItem(event.detail));
                    window.addEventListener('create-menu', () => this.create());
                },

                create() {
                    this.editMode = false;
                    this.editId = null;
                    this.form.reset();
                    this.form.clearErrors();

                    window.dispatchEvent(new CustomEvent('set-selected-parent_id', { detail: null }));
                    window.dispatchEvent(new CustomEvent('set-selected-is_active', { detail: 1 }));

                    this.$dispatch('open-modal', 'menuModal');
                },

                edit(item) {
                    this.editMode = true;
                    this.editId = item.id;
                    this.form.clearErrors();

                    const isActive = item.is_active ? 1 : 0;

                    this.form.data = {
                        name: item.name || '',
                        icon: item.icon || '',
                        route: item.route || '',
                        url: item.url || '',
                        parent_id: item.parent_id || '',
                        order: item.order || '',
                        is_active: isActive
                    };

                    window.dispatchEvent(new CustomEvent('set-selected-parent_id', { detail: item.parent_id }));
                    window.dispatchEvent(new CustomEvent('set-selected-is_active', { detail: isActive }));

                    this.$dispatch('open-modal', 'menuModal');
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-menu-modal');
                },

                async deleteConfirmed() {
                    await this.form.submit('delete', `{{ url('manajemen-menu') }}/${this.deleteId}`, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('menusTable');
                            this.$dispatch('close-modal', 'delete-menu-modal');
                            this.deleteId = null;
                        },
                        onError: (error) => {
                            toast.error('Gagal', error.response?.data?.message || 'Terjadi kesalahan saat menghapus data');
                        }
                    });
                },

                async submit() {
                    const url = this.editMode 
                        ? `{{ url('manajemen-menu') }}/${this.editId}`
                        : `{{ url('manajemen-menu') }}`;
                    
                    const method = this.editMode ? 'put' : 'post';

                    await this.form.submit(method, url, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('menusTable');
                            this.$dispatch('close-modal', 'menuModal');
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
