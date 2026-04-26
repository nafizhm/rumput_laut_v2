<div x-data="farmerForm()">
    <x-modal name="farmer-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Data' : 'Tambah Data'"></h4>
        
        <form @submit.prevent="submit">
            <div class="space-y-4">
                <x-input label="Nama" name="name" placeholder="Contoh: Budi" x-model="form.data.name"
                    alpine-error="form.getError('name')" required />

                <x-input label="No. Telepon" name="phone" placeholder="Contoh: 08123456789" x-model="form.data.phone"
                    alpine-error="form.getError('phone')" />

                <x-input label="Alamat" name="address" placeholder="Contoh: Jl. Tani No. 1" x-model="form.data.address"
                    alpine-error="form.getError('address')" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="$dispatch('close-modal', 'farmer-modal')"
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

    <x-delete-modal name="delete-farmer-modal"
        message="Apakah anda yakin ingin menghapus data ini? Data yang dihapus tidak dapat dikembalikan."
        @confirm-delete-delete-farmer-modal.window="deleteConfirmed" />
</div>

@push('scripts')
    <script>
        function farmerForm() {
            return {
                editMode: false,
                editId: null,
                deleteId: null,
                form: useForm({
                    name: '',
                    phone: '',
                    address: ''
                }),

                init() {
                    window.addEventListener('edit-farmer', (event) => this.edit(event.detail));
                    window.addEventListener('delete-farmer', (event) => this.deleteItem(event.detail));
                    window.addEventListener('create-farmer', () => this.create());
                },

                create() {
                    this.editMode = false;
                    this.editId = null;
                    this.form.reset();
                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'farmer-modal');
                },

                edit(item) {
                    this.editMode = true;
                    this.editId = item.id;
                    this.form.clearErrors();

                    this.form.data = {
                        name: item.name || '',
                        phone: item.phone || '',
                        address: item.address || ''
                    };

                    this.$dispatch('open-modal', 'farmer-modal');
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-farmer-modal');
                },

                async deleteConfirmed() {
                    await this.form.submit('delete', `{{ url('manajemen-petani') }}/${this.deleteId}`, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('farmerTable');
                            this.$dispatch('close-modal', 'delete-farmer-modal');
                            this.deleteId = null;
                        },
                        onError: (error) => {
                            toast.error('Gagal', error.response?.data?.message || 'Terjadi kesalahan saat menghapus data');
                        }
                    });
                },

                async submit() {
                    const url = this.editMode 
                        ? `{{ url('manajemen-petani') }}/${this.editId}`
                        : `{{ url('manajemen-petani') }}`;
                    
                    const method = this.editMode ? 'put' : 'post';

                    await this.form.submit(method, url, {
                        onSuccess: (response) => {
                            toast.success('Berhasil', response.data.message);
                            refreshDataTable('farmerTable');
                            this.$dispatch('close-modal', 'farmer-modal');
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
