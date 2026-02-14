 <div x-data="permissionForm()">
     <x-modal name="permissionModal">
         <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90"
             x-text="editMode ? 'Edit Permission' : 'Tambah Permission'"></h4>
         <form @submit.prevent="submit">
             <div class="space-y-4">
                 <x-select label="Link ke Menu" name="menu_id" :options="$menus" placeholder="Pilih Menu"
                     x-on:change="$dispatch('update-field', { field: 'menu_id', value: $event.detail })"
                     alpine-error="form.hasErrors('menu_id') ? form.getError('menu_id') : null" />

                 <x-input label="Module Name" name="module_name" placeholder="Contoh: User Management"
                     x-model="form.data.module_name"
                     alpine-error="form.hasErrors('module_name') ? form.getError('module_name') : null" required />

                 <div>
                     <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                         Akses CRUD
                         <span class="text-error-500">*</span>
                     </label>

                     <div class="grid grid-cols-2 gap-x-12 gap-y-4">
                         <x-checkbox name="access_read" label="Read" model="form.data.access" value="read" />
                         <x-checkbox name="access_create" label="Create" model="form.data.access" value="create" />
                         <x-checkbox name="access_update" label="Update" model="form.data.access" value="update" />
                         <x-checkbox name="access_delete" label="Delete" model="form.data.access" value="delete" />
                     </div>
                     <template x-if="form.hasErrors('access')">
                         <p class="mt-1 text-xs text-error-500" x-text="form.getError('access')"></p>
                     </template>
                 </div>

                 <div>
                     <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                         Akses Tambahan
                     </label>

                     <div class="grid grid-cols-2 gap-x-12 gap-y-4">
                         <x-checkbox name="access_export" label="Export" model="form.data.access" value="export" />
                         <x-checkbox name="access_import" label="Import" model="form.data.access" value="import" />
                         <x-checkbox name="access_approve" label="Approve" model="form.data.access" value="approve" />
                         <x-checkbox name="access_report" label="Report" model="form.data.access" value="report" />
                     </div>
                 </div>
             </div>

             <div class="flex justify-end gap-3 mt-6">
                 <button type="button" @click="$dispatch('close-modal', 'permissionModal')"
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

     <x-delete-modal name="delete-permission-modal"
         message="Apakah anda yakin ingin menghapus permission ini? Semua aksi dalam modul ini akan ikut terhapus."
         @confirm-delete-delete-permission-modal.window="deleteConfirmed" />
 </div>

 @push('scripts')
     <script>
         function permissionForm() {
             return {
                 editMode: false,
                 editId: null,
                 deleteId: null,
                 form: useForm({
                     menu_id: '',
                     module_name: '',
                     access: []
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

                     window.addEventListener('edit-permission', (event) => {
                         this.edit(event.detail);
                     });
                     window.addEventListener('delete-permission', (event) => {
                         this.deleteItem(event.detail);
                     });
                     window.addEventListener('create-permission', () => {
                         this.create();
                     });
                 },

                 create() {
                     this.form.reset();
                     this.editMode = false;
                     this.editId = null;

                     window.dispatchEvent(new CustomEvent('set-selected-menu_id', {
                         detail: null
                     }));

                     this.$dispatch('open-modal', 'permissionModal');
                 },

                 async edit(item) {
                     this.editMode = true;
                     this.editId = item.id;

                     this.form.data = {
                         menu_id: item.menu_id || '',
                         module_name: item.module_name,
                         access: [...item.access]
                     };

                     window.dispatchEvent(new CustomEvent('set-selected-menu_id', {
                         detail: item.menu_id
                     }));

                     this.form.clearErrors();
                     this.$dispatch('open-modal', 'permissionModal');
                 },

                 async deleteItem(id) {
                     this.deleteId = id;
                     this.$dispatch('open-modal', 'delete-permission-modal');
                 },

                 async deleteConfirmed() {
                     try {
                         await this.form.delete(`{{ url('manajemen-permission') }}/${this.deleteId}`, {
                             onSuccess: () => {
                                 refreshDataTable('permissionsTable');
                                 this.$dispatch('close-modal', 'delete-permission-modal');
                                 toast.success('Berhasil', 'Permission berhasil dihapus');
                                 this.deleteId = null;
                             },
                             onError: () => {
                                 toast.error('Gagal', 'Terjadi kesalahan saat menghapus permission');
                             }
                         });
                     } catch (e) {
                         // Error already handled in onError callback
                     }
                 },

                 async submit() {
                     try {
                         const url = this.editMode ?
                             `{{ url('manajemen-permission') }}/${this.editId}` :
                             '{{ route('manajemen-permission.store') }}';
                         const method = this.editMode ? 'put' : 'post';

                         await this.form[method](url, {
                             onSuccess: () => {
                                 this.form.reset();
                                 this.editMode = false;
                                 this.editId = null;
                                 this.$dispatch('close-modal', 'permissionModal');
                                 refreshDataTable('permissionsTable');
                                 toast.success('Berhasil', this.editMode ? 'Permission berhasil diperbarui' :
                                     'Permission berhasil disimpan');
                             },
                             onError: (e) => {
                                 if (!this.form.hasErrors()) {
                                     toast.error('Gagal', 'Terjadi kesalahan saat memproses permission');
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
