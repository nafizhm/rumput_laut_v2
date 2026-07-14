<div x-data="warehouseModals()">
    <x-modal name="receive-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Terima Stok Gudang</h4>
        <form @submit.prevent="submitReceive">
            <div class="space-y-4">
                <x-select label="Gudang / Barang" name="inventory_id" :options="$inventories" x-model="receiveForm.data.inventory_id" 
                    x-on:change="$dispatch('update-receive-field', { field: 'inventory_id', value: $event.detail })"
                    alpine-error="receiveForm.getError('inventory_id')" required />

                <x-input label="Tanggal" name="movement_date" type="date" x-model="receiveForm.data.movement_date" 
                    alpine-error="receiveForm.getError('movement_date')" required />

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Berat (Kg)" name="actual_weight_kg" x-model="receiveForm.data.actual_weight_kg" :isNumeric="true" :numericDecimals="2"
                        alpine-error="receiveForm.getError('actual_weight_kg')" required />
                    <x-input label="Jumlah Karung" name="actual_sacks" type="number" x-model="receiveForm.data.actual_sacks"
                        alpine-error="receiveForm.getError('actual_sacks')" required />
                </div>

                <x-input label="Keterangan" name="description" placeholder="Contoh: Penerimaan manual" x-model="receiveForm.data.description"
                    alpine-error="receiveForm.getError('description')" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'receive-modal')" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Batal</button>
                <button type="submit" :disabled="receiveForm.processing" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!receiveForm.processing">Simpan</span>
                    <span x-show="receiveForm.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal name="transfer-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Transfer Stok Antar Gudang</h4>
        <form @submit.prevent="submitTransfer">
            <div class="space-y-4">
                <x-select label="Dari Gudang" name="from_inventory_id" :options="$inventories" x-model="transferForm.data.from_inventory_id"
                    x-on:change="$dispatch('update-transfer-field', { field: 'from_inventory_id', value: $event.detail })"
                    alpine-error="transferForm.getError('from_inventory_id')" required />
                <div x-show="selectedFromInventory" class="flex items-center gap-2" x-cloak>
                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                        Stok: <span x-text="selectedFromInventory ? formatWeight(selectedFromInventory.current_stock) : '0 Kg'" class="ml-1 font-bold"></span>
                    </span>
                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                        <span x-text="selectedFromInventory ? Number(selectedFromInventory.current_sacks || 0).toLocaleString('id-ID') : 0" class="mr-1 font-bold"></span> Karung
                    </span>
                </div>

                <x-select label="Ke Gudang" name="to_inventory_id" :options="$inventories" x-model="transferForm.data.to_inventory_id"
                    x-on:change="$dispatch('update-transfer-field', { field: 'to_inventory_id', value: $event.detail })"
                    alpine-error="transferForm.getError('to_inventory_id')" required />
                <div x-show="selectedToInventory" class="flex items-center gap-2" x-cloak>
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20">
                        Stok: <span x-text="selectedToInventory ? formatWeight(selectedToInventory.current_stock) : '0 Kg'" class="ml-1 font-bold"></span>
                    </span>
                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                        <span x-text="selectedToInventory ? Number(selectedToInventory.current_sacks || 0).toLocaleString('id-ID') : 0" class="mr-1 font-bold"></span> Karung
                    </span>
                </div>

                <x-input label="Tanggal Transfer" name="transfer_date" type="date" x-model="transferForm.data.transfer_date"
                    alpine-error="transferForm.getError('transfer_date')" required />

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Berat (Kg)" name="weight_kg" x-model="transferForm.data.weight_kg" :isNumeric="true" :numericDecimals="2"
                        alpine-error="transferForm.getError('weight_kg')" required />
                    <x-input label="Jumlah Karung" name="sack_count" type="number" x-model="transferForm.data.sack_count"
                        alpine-error="transferForm.getError('sack_count')" required />
                </div>

                <x-input label="Keterangan" name="description" placeholder="Contoh: Transfer basah ke kering" x-model="transferForm.data.description"
                    alpine-error="transferForm.getError('description')" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'transfer-modal')" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Batal</button>
                <button type="submit" :disabled="transferForm.processing" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!transferForm.processing">Simpan</span>
                    <span x-show="transferForm.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal name="adjustment-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Penyesuaian Stok (Stock Opname)</h4>
        <form @submit.prevent="submitAdjustment">
            <div class="space-y-4">
                <x-select label="Gudang / Barang" name="inventory_id" :options="$inventories" x-model="adjustmentForm.data.inventory_id"
                    x-on:change="$dispatch('update-adjustment-field', { field: 'inventory_id', value: $event.detail })"
                    alpine-error="adjustmentForm.getError('inventory_id')" required />
                <div x-show="selectedAdjustmentInventory" class="flex items-center gap-2" x-cloak>
                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                        Stok: <span x-text="selectedAdjustmentInventory ? formatWeight(selectedAdjustmentInventory.current_stock) : '0 Kg'" class="ml-1 font-bold"></span>
                    </span>
                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                        <span x-text="selectedAdjustmentInventory ? Number(selectedAdjustmentInventory.current_sacks || 0).toLocaleString('id-ID') : 0" class="mr-1 font-bold"></span> Karung
                    </span>
                </div>

                <x-input label="Tanggal" name="movement_date" type="date" x-model="adjustmentForm.data.movement_date"
                    alpine-error="adjustmentForm.getError('movement_date')" required />

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Tipe Penyesuaian<span class="text-error-500">*</span>
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="adjustment_type" value="addition" x-model="adjustmentForm.data.adjustment_type"
                                class="w-4 h-4 text-brand-500 border-gray-300 focus:ring-brand-500 dark:border-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-400">Penambahan (+)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="adjustment_type" value="subtraction" x-model="adjustmentForm.data.adjustment_type"
                                class="w-4 h-4 text-gray-500 border-gray-300 focus:ring-brand-500 dark:border-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-400">Pengurangan (-)</span>
                        </label>
                    </div>
                </div>

                <x-input label="Berat (Kg)" name="weight" x-model="adjustmentForm.data.weight" :isNumeric="true" :numericDecimals="2"
                    placeholder="Masukkan berat"
                    alpine-error="adjustmentForm.getError('adjustment_weight_kg')" required />

                <x-input label="Keterangan / Alasan" name="description" x-model="adjustmentForm.data.description"
                    alpine-error="adjustmentForm.getError('description')" required />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'adjustment-modal')" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Batal</button>
                <button type="submit" :disabled="adjustmentForm.processing" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!adjustmentForm.processing">Simpan</span>
                    <span x-show="adjustmentForm.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>

@push('scripts')
<script>
function warehouseModals() {
    return {
        inventories: @json($inventories),

        get selectedFromInventory() {
            return this.inventories.find(i => i.value == this.transferForm.data.from_inventory_id);
        },

        get selectedToInventory() {
            return this.inventories.find(i => i.value == this.transferForm.data.to_inventory_id);
        },

        get selectedAdjustmentInventory() {
            return this.inventories.find(i => i.value == this.adjustmentForm.data.inventory_id);
        },

        receiveForm: useForm({
            inventory_id: '',
            movement_date: window.getLocalDateInputValue(),
            actual_weight_kg: 0,
            actual_sacks: 0,
            description: ''
        }),

        transferForm: useForm({
            from_inventory_id: '',
            to_inventory_id: '',
            transfer_date: window.getLocalDateInputValue(),
            weight_kg: 0,
            sack_count: 0,
            description: ''
        }),

        adjustmentForm: useForm({
            inventory_id: '',
            movement_date: window.getLocalDateInputValue(),
            adjustment_type: 'addition',
            weight: 0,
            description: ''
        }),

        init() {
            window.addEventListener('open-receive-modal', () => {
                this.receiveForm.reset();
                this.receiveForm.data.movement_date = window.getLocalDateInputValue();
                this.$dispatch('open-modal', 'receive-modal');
            });
            window.addEventListener('open-adjustment-modal', () => {
                this.adjustmentForm.reset();
                this.adjustmentForm.data.movement_date = window.getLocalDateInputValue();
                this.$dispatch('open-modal', 'adjustment-modal');
            });

            window.addEventListener('open-transfer-modal', () => {
                this.transferForm.reset();
                this.transferForm.data.transfer_date = window.getLocalDateInputValue();
                this.$dispatch('open-modal', 'transfer-modal');
            });

            window.addEventListener('update-receive-field', (e) => this.receiveForm.data[e.detail.field] = e.detail.value);
            window.addEventListener('update-transfer-field', (e) => this.transferForm.data[e.detail.field] = e.detail.value);
            window.addEventListener('update-adjustment-field', (e) => this.adjustmentForm.data[e.detail.field] = e.detail.value);
        },

        async refreshInventories() {
            try {
                const res = await fetch('/warehouse-stock');
                const result = await res.json();
                if (result.success && result.data) {
                    this.inventories = result.data;
                }
            } catch (e) {
                console.error('Gagal refresh stok:', e);
            }
        },

        async submitReceive() {
            await this.receiveForm.submit('post', '{{ route("manajemen-gudang.receive") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    this.$dispatch('close-modal', 'receive-modal');
                    refreshDataTable('movementTable');
                    this.refreshInventories();
                },
                onError: (err) => {
                    if (err.response?.status !== 422) {
                        toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem');
                    }
                }
            });
        },

        async submitTransfer() {
            await this.transferForm.submit('post', '{{ route("manajemen-gudang.transfer") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    this.$dispatch('close-modal', 'transfer-modal');
                    refreshDataTable('movementTable');
                    this.refreshInventories();
                },
                onError: (err) => {
                    if (err.response?.status !== 422) {
                        toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem');
                    }
                }
            });
        },

        async submitAdjustment() {
            const signedWeight = this.adjustmentForm.data.adjustment_type === 'subtraction'
                ? -Math.abs(this.adjustmentForm.data.weight || 0)
                : Math.abs(this.adjustmentForm.data.weight || 0);

            this.adjustmentForm.data.adjustment_weight_kg = signedWeight;

            await this.adjustmentForm.submit('post', '{{ route("manajemen-gudang.adjustment") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    this.$dispatch('close-modal', 'adjustment-modal');
                    refreshDataTable('movementTable');
                    this.refreshInventories();
                },
                onError: (err) => {
                    if (err.response?.status !== 422) {
                        toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem');
                    }
                }
            });
        }
    }
}
</script>
@endpush
