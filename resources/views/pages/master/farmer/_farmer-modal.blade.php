<div x-data="farmerForm()">
    <x-modal name="farmer-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Data' : 'Add Data'"></h4>
        
        <form @submit.prevent="submit">
            <div class="space-y-4">
                <x-input label="Nama" name="name" placeholder="Contoh: Budi" x-model="form.data.name"
                    alpine-error="form.getError('name')" required />

                <x-input label="No. Telepon" name="phone" placeholder="Contoh: 08123456789" x-model="form.data.phone"
                    alpine-error="form.getError('phone')" />

                <x-input label="Alamat" name="address" placeholder="Contoh: Jl. Tani No. 1" x-model="form.data.address"
                    alpine-error="form.getError('address')" />

                <x-input label="Hutang Saat Ini" name="current_debt" type="number" placeholder="Contoh: 100000" x-model="form.data.current_debt" :isCurrency="true"
                    alpine-error="form.getError('current_debt')" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="$dispatch('close-modal', 'farmer-modal')"
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    Cancel
                </button>

                <button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600"
                    :disabled="form.processing">
                    <span x-show="!form.processing">Save</span>
                    <span x-show="form.processing">Saving...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-delete-modal name="delete-farmer-modal"
        message="Are you sure you want to delete this data? This action cannot be undone."
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
                    address: '',
                    current_debt: 0
                }),

                init() {
                    window.addEventListener('edit-farmer', (event) => {
                        this.edit(event.detail);
                    });
                    window.addEventListener('delete-farmer', (event) => {
                        this.deleteItem(event.detail);
                    });
                    window.addEventListener('create-farmer', () => {
                        this.create();
                    });
                },

                create() {
                    this.form.reset();
                    this.editMode = false;
                    this.editId = null;
                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'farmer-modal');
                },

                edit(item) {
                    this.editMode = true;
                    this.editId = item.id;

                    this.form.data = {
                        name: item.name || '',
                        phone: item.phone || '',
                        address: item.address || '',
                        current_debt: item.current_debt || 0
                    };

                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'farmer-modal');
                },

                deleteItem(id) {
                    this.deleteId = id;
                    this.$dispatch('open-modal', 'delete-farmer-modal');
                },

                async deleteConfirmed() {
                    try {
                        await this.form.delete(`{{ url('manajemen-petani') }}/${this.deleteId}`, {
                            onSuccess: () => {
                                refreshDataTable('farmerTable');
                                this.$dispatch('close-modal', 'delete-farmer-modal');
                                toast.success('Success', 'Farmer deleted successfully');
                                this.deleteId = null;
                            },
                            onError: () => {
                                toast.error('Error', 'Failed to delete farmer');
                            }
                        });
                    } catch (e) {}
                },

                async submit() {
                    try {
                        const url = this.editMode 
                            ? `{{ url('manajemen-petani') }}/${this.editId}`
                            : '{{ route('manajemen-petani.store') }}';
                        
                        const method = this.editMode ? 'put' : 'post';

                        await this.form[method](url, {
                            onSuccess: () => {
                                this.form.reset();
                                this.editMode = false;
                                this.editId = null;
                                this.$dispatch('close-modal', 'farmer-modal');
                                refreshDataTable('farmerTable');
                                toast.success('Success', `Farmer ${this.editMode ? 'updated' : 'created'} successfully`);
                            },
                            onError: (e) => {
                                if (!this.form.hasErrors()) {
                                    toast.error('Error', `Failed to ${this.editMode ? 'update' : 'create'} farmer`);
                                }
                            }
                        });
                    } catch (e) {}
                }
            }
        }
    </script>
@endpush
