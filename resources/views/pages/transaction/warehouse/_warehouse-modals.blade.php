<div x-data="warehouseModals()">
    <!-- Modal Terima Stok -->
    <x-modal name="receive-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Terima Stok dari Panen</h4>
        <form @submit.prevent="submitReceive">
            <div class="space-y-4">
                <x-select label="Gudang / Barang" name="inventory_id" :options="$inventories" x-model="receiveForm.data.inventory_id" 
                    x-on:change="$dispatch('update-receive-field', { field: 'inventory_id', value: $event.detail })"
                    alpine-error="receiveForm.getError('inventory_id')" required />

                <x-select label="Transaksi Panen" name="harvest_transaction_id" :options="$harvests" x-model="receiveForm.data.harvest_transaction_id"
                    x-on:change="onHarvestSelected($event.detail)"
                    alpine-error="receiveForm.getError('harvest_transaction_id')" required />

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Berat Nota (Kg)" x-model="receiveForm.data.note_weight_kg" disabled />
                    <x-input label="Jumlah Karung Nota" x-model="receiveForm.data.note_sacks" disabled />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Berat Aktual (Kg)" name="actual_weight_kg" type="number" step="0.01" x-model="receiveForm.data.actual_weight_kg"
                        alpine-error="receiveForm.getError('actual_weight_kg')" required />
                    <x-input label="Jumlah Karung Aktual" name="actual_sacks" type="number" x-model="receiveForm.data.actual_sacks"
                        alpine-error="receiveForm.getError('actual_sacks')" required />
                </div>

                <x-input label="Selisih (Kg)" x-model="receiveDiscrepancy" disabled />
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

    <!-- Modal Penyusutan -->
    <x-modal name="shrinkage-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Pencatatan Penyusutan Stok</h4>
        <form @submit.prevent="submitShrinkage">
            <div class="space-y-4">
                <x-select label="Gudang / Barang" name="inventory_id" :options="$inventories" x-model="shrinkageForm.data.inventory_id"
                    x-on:change="$dispatch('update-shrinkage-field', { field: 'inventory_id', value: $event.detail })"
                    alpine-error="shrinkageForm.getError('inventory_id')" required />

                <x-input label="Berat Penyusutan (Kg)" name="lost_weight_kg" type="number" step="0.01" x-model="shrinkageForm.data.lost_weight_kg"
                    alpine-error="shrinkageForm.getError('lost_weight_kg')" required />

                <x-input label="Keterangan" name="description" x-model="shrinkageForm.data.description"
                    alpine-error="shrinkageForm.getError('description')" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'shrinkage-modal')" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Batal</button>
                <button type="submit" :disabled="shrinkageForm.processing" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!shrinkageForm.processing">Simpan</span>
                    <span x-show="shrinkageForm.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Penyesuaian -->
    <x-modal name="adjustment-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Penyesuaian Stok (Stock Opname)</h4>
        <form @submit.prevent="submitAdjustment">
            <div class="space-y-4">
                <x-select label="Gudang / Barang" name="inventory_id" :options="$inventories" x-model="adjustmentForm.data.inventory_id"
                    x-on:change="$dispatch('update-adjustment-field', { field: 'inventory_id', value: $event.detail })"
                    alpine-error="adjustmentForm.getError('inventory_id')" required />

                <x-input label="Berat Penyesuaian (Kg)" name="adjustment_weight_kg" type="number" step="0.01" x-model="adjustmentForm.data.adjustment_weight_kg"
                    placeholder="Gunakan tanda (-) untuk mengurangi stok"
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
        harvests: @json($harvests),
        
        receiveForm: useForm({
            inventory_id: '',
            harvest_transaction_id: '',
            actual_weight_kg: 0,
            actual_sacks: 0,
            note_weight_kg: 0,
            note_sacks: 0
        }),

        shrinkageForm: useForm({
            inventory_id: '',
            lost_weight_kg: 0,
            description: ''
        }),

        adjustmentForm: useForm({
            inventory_id: '',
            adjustment_weight_kg: 0,
            description: ''
        }),

        get receiveDiscrepancy() {
            return (parseFloat(this.receiveForm.data.note_weight_kg) || 0) - (parseFloat(this.receiveForm.data.actual_weight_kg) || 0);
        },

        init() {
            window.addEventListener('open-receive-modal', () => {
                this.receiveForm.reset();
                this.$dispatch('open-modal', 'receive-modal');
            });
            window.addEventListener('open-shrinkage-modal', () => {
                this.shrinkageForm.reset();
                this.$dispatch('open-modal', 'shrinkage-modal');
            });
            window.addEventListener('open-adjustment-modal', () => {
                this.adjustmentForm.reset();
                this.$dispatch('open-modal', 'adjustment-modal');
            });

            window.addEventListener('update-receive-field', (e) => this.receiveForm.data[e.detail.field] = e.detail.value);
            window.addEventListener('update-shrinkage-field', (e) => this.shrinkageForm.data[e.detail.field] = e.detail.value);
            window.addEventListener('update-adjustment-field', (e) => this.adjustmentForm.data[e.detail.field] = e.detail.value);
        },

        onHarvestSelected(id) {
            const harvest = this.harvests.find(h => h.value == id);
            if (harvest) {
                this.receiveForm.data.harvest_transaction_id = id;
                this.receiveForm.data.note_weight_kg = harvest.net_weight;
                this.receiveForm.data.note_sacks = harvest.sack_count;
                this.receiveForm.data.actual_weight_kg = harvest.net_weight;
                this.receiveForm.data.actual_sacks = harvest.sack_count;
            }
        },

        async submitReceive() {
            await this.receiveForm.submit('post', '{{ route("manajemen-gudang.receive") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    this.$dispatch('close-modal', 'receive-modal');
                    setTimeout(() => window.location.reload(), 1000);
                },
                onError: (err) => {
                    if (err.response?.status !== 422) {
                        toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem');
                    }
                }
            });
        },

        async submitShrinkage() {
            await this.shrinkageForm.submit('post', '{{ route("manajemen-gudang.shrinkage") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    this.$dispatch('close-modal', 'shrinkage-modal');
                    setTimeout(() => window.location.reload(), 1000);
                },
                onError: (err) => {
                    if (err.response?.status !== 422) {
                        toast.error('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem');
                    }
                }
            });
        },

        async submitAdjustment() {
            await this.adjustmentForm.submit('post', '{{ route("manajemen-gudang.adjustment") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    this.$dispatch('close-modal', 'adjustment-modal');
                    setTimeout(() => window.location.reload(), 1000);
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
