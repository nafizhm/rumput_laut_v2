<div x-data="salesFormComponent()">
    <x-modal name="sales-modal">
        <h4 class="mb-6 text-lg font-medium text-gray-800 dark:text-white/90">Tambah Data Penjualan</h4>

        <form @submit.prevent="submit">
            <div class="space-y-4">
                <x-select label="Pabrik" name="factory_id" :options="$factories" x-model="form.data.factory_id"
                    x-on:change="$dispatch('update-sales-field', { field: 'factory_id', value: $event.detail })"
                    alpine-error="form.getError('factory_id')" required />

                <x-select label="Barang / Stok" name="inventory_id" :options="$inventories" x-model="form.data.inventory_id"
                    x-on:change="$dispatch('update-sales-field', { field: 'inventory_id', value: $event.detail })"
                    alpine-error="form.getError('inventory_id')" required />

                <x-input label="Tanggal Penjualan" name="sale_date" type="date" x-model="form.data.sale_date"
                    alpine-error="form.getError('sale_date')" required />

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Berat Terjual (Kg)" name="sold_weight_kg" type="number" step="0.01" x-model="form.data.sold_weight_kg"
                        alpine-error="form.getError('sold_weight_kg')" required />
                    <x-input label="Jumlah Karung (Opsional)" name="sold_sacks" type="number" x-model="form.data.sold_sacks"
                        alpine-error="form.getError('sold_sacks')" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-input label="Harga / Kg" name="price_per_kg" x-model="form.data.price_per_kg" :isCurrency="true"
                        alpine-error="form.getError('price_per_kg')" required />
                    <x-input label="Total Nilai Jual" x-model="totalSaleValue" :isCurrency="true" disabled />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="$dispatch('close-modal', 'sales-modal')" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Batal</button>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <span x-show="!form.processing">Simpan</span>
                    <span x-show="form.processing">Menyimpan...</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>

@push('scripts')
<script>
function salesFormComponent() {
    return {
        form: useForm({
            factory_id: '',
            inventory_id: '',
            sale_date: new Date().toISOString().split('T')[0],
            sold_weight_kg: 0,
            price_per_kg: 0,
            sold_sacks: 0
        }),

        get totalSaleValue() {
            return (parseFloat(this.form.data.sold_weight_kg) || 0) * (parseFloat(this.form.data.price_per_kg) || 0);
        },

        init() {
            window.addEventListener('create-entity', () => {
                this.form.reset();
                this.$dispatch('open-modal', 'sales-modal');
            });

            window.addEventListener('update-sales-field', (e) => this.form.data[e.detail.field] = e.detail.value);
        },

        async submit() {
            await this.form.submit('post', '{{ route("manajemen-penjualan.store") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    refreshDataTable('salesTable');
                    this.$dispatch('close-modal', 'sales-modal');
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
