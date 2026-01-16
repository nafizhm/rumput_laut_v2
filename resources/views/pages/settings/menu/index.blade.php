@extends('layouts.app')

@section('title', 'Manajemen Menu')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Manajemen Menu" :links="[['name' => 'Menu', 'url' => route('manajemen-menu.index')]]" />
@endsection

@section('content')
    <div class="mb-4 flex justify-end">
        <button onclick="refreshDataTable('usersTable');"
            class="flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="ml-2">Refresh All Tables</span>
        </button>
    </div>

    <x-data-table id="usersTable" :rows="$data" :columns="$columns" :url="route('menu-data.index')">
        <x-slot:headerAction>
            <button type="button" @click="$dispatch('create-menu')"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto hover:bg-gray-50 dark:hover:bg-white/3 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-plus-icon lucide-plus">
                    <path d="M5 12h14" />
                    <path d="M12 5v14" />
                </svg>
                Tambah Menu
            </button>
        </x-slot:headerAction>

        <x-slot:status>
            <div
                :class="{
                    'bg-success-50 text-theme-xs text-success-700 dark:bg-success-500/15 dark:text-success-500 inline-block rounded-full px-2 py-0.5 font-medium': item
                        .is_active,
                    'bg-error-50 text-theme-xs text-error-700 dark:bg-error-500/15 dark:text-error-500 inline-block rounded-full px-2 py-0.5 font-medium':
                        !item.is_active
                }">
                <span x-text="item.is_active ? 'Active' : 'Inactive'"></span>
            </div>
        </x-slot:status>
        <x-slot:parent_name>
            <span x-text="item.parent?.name || '-'"></span>
        </x-slot:parent_name>
        <x-slot:action>
            <div class="flex items-center gap-2">
                <button type="button" @click="$dispatch('edit-menu', item)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600">
                    Edit
                </button>
                <button type="button" @click="$dispatch('delete-menu', item.id)"
                    class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-rose-500 hover:bg-rose-600">
                    Delete
                </button>
            </div>
        </x-slot:action>
    </x-data-table>

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

                    <x-input label="Permission" name="permission" placeholder="Contoh: menu.view"
                        x-model="form.data.permission"
                        alpine-error="form.hasErrors('permission') ? form.getError('permission') : null" />
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="$dispatch('close-modal', 'menuModal')"
                        class="px-4 py-2 border rounded-lg">
                        Batal
                    </button>

                    <button type="submit" class="px-4 py-2 bg-brand-500 text-white rounded-lg" :disabled="form.processing">
                        <span x-show="!form.processing">Simpan</span>
                        <span x-show="form.processing">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </x-modal>
    </div>
@endsection

@push('scripts')
    <script>
        function menuForm() {
            return {
                parents: @json($parents),
                editMode: false,
                editId: null,
                form: useForm({
                    name: '',
                    icon: '',
                    route: '',
                    url: '',
                    parent_id: '',
                    order: '',
                    is_active: true,
                    permission: ''
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

                    window.addEventListener('edit-menu', (event) => {
                        this.edit(event.detail);
                    });
                    window.addEventListener('delete-menu', (event) => {
                        this.deleteItem(event.detail);
                    });
                    window.addEventListener('create-menu', () => {
                        this.create();
                    });
                },

                create() {
                    this.form.reset();
                    this.editMode = false;
                    this.editId = null;

                    window.dispatchEvent(new CustomEvent('set-selected-parent_id', {
                        detail: null
                    }));
                    window.dispatchEvent(new CustomEvent('set-selected-is_active', {
                        detail: 1
                    }));

                    this.$dispatch('open-modal', 'menuModal');
                },

                edit(item) {
                    this.editMode = true;
                    this.editId = item.id;

                    const isActive = item.is_active ? 1 : 0;

                    this.form.data = {
                        name: item.name || '',
                        icon: item.icon || '',
                        route: item.route || '',
                        url: item.url || '',
                        parent_id: item.parent_id || '',
                        order: item.order || '',
                        is_active: isActive,
                        permission: item.permission || ''
                    };

                    window.dispatchEvent(new CustomEvent('set-selected-parent_id', {
                        detail: item.parent_id
                    }));
                    window.dispatchEvent(new CustomEvent('set-selected-is_active', {
                        detail: isActive
                    }));

                    this.form.clearErrors();
                    this.$dispatch('open-modal', 'menuModal');
                },

                async deleteItem(id) {
                    if (!confirm('Apakah anda yakin ingin menghapus menu ini?')) return;

                    try {
                        await this.form.delete(`{{ url('manajemen-menu') }}/${id}`, {
                            onSuccess: () => {
                                refreshDataTable('usersTable');
                                toast.success('Berhasil', 'Menu berhasil dihapus');
                            },
                            onError: () => {
                                toast.error('Gagal', 'Terjadi kesalahan saat menghapus menu');
                            }
                        });
                    } catch (e) {
                        // Error already handled in onError callback
                    }
                },

                async submit() {
                    try {
                        if (this.editMode) {
                            await this.form.put(`{{ url('manajemen-menu') }}/${this.editId}`, {
                                onSuccess: () => {
                                    this.form.reset();
                                    this.editMode = false;
                                    this.editId = null;
                                    this.$dispatch('close-modal', 'menuModal');
                                    refreshDataTable('usersTable');
                                    toast.success('Berhasil', 'Menu berhasil diperbarui');
                                },
                                onError: (e) => {
                                    if (!this.form.hasErrors()) {
                                        toast.error('Gagal', 'Terjadi kesalahan saat memperbarui menu');
                                    }
                                }
                            });
                        } else {
                            await this.form.post('{{ route('manajemen-menu.store') }}', {
                                onSuccess: () => {
                                    this.form.reset();
                                    this.editMode = false;
                                    this.editId = null;
                                    this.$dispatch('close-modal', 'menuModal');
                                    refreshDataTable('usersTable');
                                    toast.success('Berhasil', 'Menu berhasil disimpan');
                                },
                                onError: (e) => {
                                    if (!this.form.hasErrors()) {
                                        toast.error('Gagal', 'Terjadi kesalahan saat menyimpan menu');
                                    }
                                }
                            });
                        }
                    } catch (e) {
                        // Error already handled in callbacks
                    }
                }
            }
        }
    </script>
@endpush
