 <div x-data="roleForm()">
     <x-modal name="roleModal">
         <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
             x-text="editMode ? 'Edit Role' : 'Tambah Role'"></h4>
         <form @submit.prevent="submit">
             <div class="space-y-4">
                 <x-input label="Nama Role" name="name" placeholder="Contoh: User Management" x-model="form.data.name"
                     alpine-error="form.hasErrors('name') ? form.getError('name') : null" required />

                 <x-select label="Permission" name="permissions" :options="$permission" placeholder="Pilih Permission"
                     x-on:change="$dispatch('update-field', { field: 'permissions', value: $event.detail })"
                     alpine-error="form.hasErrors('permissions') ? form.getError('permissions') : null" multiple />
             </div>

             <div class="flex justify-end gap-3 mt-6">
                 <button type="button" @click="$dispatch('close-modal', 'roleModal')"
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

     <x-delete-modal name="delete-role-modal"
         message="Apakah anda yakin ingin menghapus role ini? Semua permission dalam role ini akan ikut terhapus."
         @confirm-delete-delete-role-modal.window="deleteConfirmed" />
 </div>

 @push('scripts')
     <script>
         function roleForm() {
             return {
                 editMode: false,
                 editMode: false,
                 editId: null,
                 deleteId: null,
                 form: useForm({
                     name: '',
                     permissions: []
                 }),

                 init() {
                     this.$wire = null;

                     window.addEventListener('update-field', (event) => {
                         const {
                             field,
                             value
                         } = event.detail;
                         this.form.data[field] = value;
                     });

                     window.addEventListener('edit-role', (event) => {
                         this.edit(event.detail);
                     });
                     window.addEventListener('delete-role', (event) => {
                         this.deleteItem(event.detail);
                     });
                     window.addEventListener('create-role', () => {
                         this.create();
                     });
                 },

                 create() {
                     this.form.reset();
                     this.editMode = false;
                     this.editId = null;

                     window.dispatchEvent(new CustomEvent('set-selected-permissions', {
                         detail: null
                     }));

                     this.$dispatch('open-modal', 'roleModal');
                 },

                 async edit(item) {
                     this.editMode = true;
                     this.editId = item.id;

                     const permissionIds = item.permissions.map(p => p.id);

                     this.form.data = {
                         name: item.name || '',
                         permissions: permissionIds
                     };

                     window.dispatchEvent(new CustomEvent('set-selected-permissions', {
                         detail: permissionIds
                     }));

                     this.form.clearErrors();
                     this.$dispatch('open-modal', 'roleModal');
                 },

                 async deleteItem(id) {
                     this.deleteId = id;
                     this.$dispatch('open-modal', 'delete-role-modal');
                 },

                 async deleteConfirmed() {
                     try {
                         await this.form.delete(`{{ url('manajemen-role') }}/${this.deleteId}`, {
                             onSuccess: () => {
                                 refreshDataTable('rolesTable');
                                 this.$dispatch('close-modal', 'delete-role-modal');
                                 toast.success('Berhasil', 'Role berhasil dihapus');
                                 this.deleteId = null;
                             },
                             onError: () => {
                                 toast.error('Gagal', 'Terjadi kesalahan saat menghapus role');
                             }
                         });
                     } catch (e) {
                         // Error already handled in onError callback
                     }
                 },

                 async submit() {
                     try {
                         const url = this.editMode ?
                             `{{ url('manajemen-role') }}/${this.editId}` :
                             '{{ route('manajemen-role.store') }}';
                         const method = this.editMode ? 'put' : 'post';

                         await this.form[method](url, {
                             onSuccess: () => {
                                 this.form.reset();
                                 this.editMode = false;
                                 this.editId = null;
                                 this.$dispatch('close-modal', 'roleModal');
                                 refreshDataTable('rolesTable');
                                 toast.success('Berhasil', this.editMode ? 'Role berhasil diperbarui' :
                                     'Role berhasil disimpan');
                             },
                             onError: (e) => {
                                 if (!this.form.hasErrors()) {
                                     toast.error('Gagal', 'Terjadi kesalahan saat memproses role');
                                 }
                             }
                         });
                     } catch (e) {
                         // Error already handled in callbacks
                     }
                 }
             }
         }
     </script>
 @endpush
