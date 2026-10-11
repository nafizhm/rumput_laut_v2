<div x-data="inventoryFormComponent()">
    <x-modal name="entity-modal">
        <h4 
            class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
            x-text="editMode ? 'Edit Data' : 'Add Data'"
        ></h4>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <x-input 
                    label="Nama Barang / Gudang"
                    name="item_name"
                    x-model="form.data.item_name"
                    placeholder="Contoh: Rumput Laut Kering Grade A"
                    alpine-error="form.getError('item_name')"
                    required
                />
                
                <template x-if="!editMode">
                    <x-info-help><p class="text-xs text-gray-500 italic">Catatan: Stok awal akan diset menjadi 0 Kg. Anda bisa menambah stok melalui menu Manajemen Gudang > Manajemen Gudang.</p></x-info-help>
                </template>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button 
                    type="button" 
                    @click="$dispatch('close-modal', 'entity-modal')" 
                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                    Cancel
                </button>

                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600"
                >
                    <span x-show="!form.processing">Save</span>
                    <span x-show="form.processing">Saving...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-delete-modal
        name="delete-entity-modal"
        message="Are you sure you want to delete this item? This action cannot be undone."
        @confirm-delete-delete-entity-modal.window="deleteConfirmed"
    />
</div>

@push('scripts')
<script>
function inventoryFormComponent() {
    return {
        editMode: false,
        editId: null,
        deleteId: null,

        form: useForm({
            item_name: ''
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
            this.$dispatch('open-modal', 'entity-modal');
        },

        edit(item) {
            this.editMode = true;
            this.editId = item.id;
            this.form.clearErrors();
            this.form.data = {
                item_name: item.item_name
            };
            this.$dispatch('open-modal', 'entity-modal');
        },

        deleteItem(id) {
            this.deleteId = id;
            this.$dispatch('open-modal', 'delete-entity-modal');
        },

        async deleteConfirmed() {
            await this.form.submit('delete', `{{ url('manajemen-inventori') }}/${this.deleteId}`, {
                onSuccess: (res) => {
                    toast.success('Success', res.data.message);
                    refreshDataTable('inventoryTable');
                    this.$dispatch('close-modal', 'delete-entity-modal');
                },
                onError: (err) => {
                    toast.error('Error', err.response.data.message || 'Something went wrong');
                }
            });
        },

        async submit() {
            const url = this.editMode 
                ? `{{ url('manajemen-inventori') }}/${this.editId}` 
                : `{{ url('manajemen-inventori') }}`;
            const method = this.editMode ? 'put' : 'post';

            await this.form.submit(method, url, {
                onSuccess: (res) => {
                    toast.success('Success', res.data.message);
                    refreshDataTable('inventoryTable');
                    this.$dispatch('close-modal', 'entity-modal');
                },
                onError: (err) => {
                    if (err.response?.status !== 422) {
                        toast.error('Error', err.response?.data?.message || 'Something went wrong');
                    }
                }
            });
        }
    }
}
</script>
@endpush
