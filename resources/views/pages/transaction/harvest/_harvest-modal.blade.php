<div x-data="harvestForm()">
<x-modal name="harvest-modal" maxWidth="max-w-6xl">
<h4 class="mb-5 text-lg font-medium text-gray-800 dark:text-white/90">Tambah Penerimaan Panen</h4>
<form @submit.prevent="submit" class="max-h-[75vh] space-y-4 overflow-y-auto pr-1">
<div class="grid gap-4 md:grid-cols-2">
<label class="text-sm text-gray-700 dark:text-gray-300">Petani
<select x-model="form.data.farmer_id" required class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 bg-white px-3 dark:border-gray-700 dark:bg-gray-900">
<option value="">Pilih petani</option>@foreach($farmers as $farmer)<option value="{{ $farmer['value'] }}">{{ $farmer['label'] }}</option>@endforeach
</select><span class="text-error-500" x-text="form.getError('farmer_id')"></span></label>
<x-input label="Tanggal Panen / Diterima" name="harvest_date" type="date" x-model="form.data.harvest_date" alpine-error="form.getError('harvest_date')" required />
</div>
<x-input label="Lokasi" name="location" x-model="form.data.location" alpine-error="form.getError('location')" required />
<div class="grid gap-4 md:grid-cols-4">
<x-input label="Berat Referensi Pembelian (Kg)" name="total_weight" x-model="form.data.total_weight" :isNumeric="true" :numericDecimals="2" alpine-error="form.getError('total_weight')" required />
<x-input label="Jumlah Karung" name="sack_count" type="number" min="1" x-model="form.data.sack_count" alpine-error="form.getError('sack_count')" required />
<x-input label="Bonus / Karung (Kg)" name="extra_per_sack" x-model="form.data.extra_per_sack" :isNumeric="true" :numericDecimals="2" alpine-error="form.getError('extra_per_sack')" required />
<x-input label="Total Berat Gudang (Kg)" name="warehouse_weight" x-model="warehouseWeight" :isNumeric="true" :numericDecimals="2" readonly />
</div>
<label class="block text-sm text-gray-700 dark:text-gray-300">Catatan<textarea x-model="form.data.notes" class="mt-1.5 w-full rounded-lg border border-gray-300 bg-transparent p-3 dark:border-gray-700"></textarea><span class="text-error-500" x-text="form.getError('notes')"></span></label>
            <section class="mt-5 space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <h5 class="font-semibold text-gray-800 dark:text-white">Penerimaan Barang Panen</h5>
                <x-info-help><p class="text-xs text-gray-500">Penerimaan dan batch dicatat otomatis saat konfirmasi. Total rincian harus sama dengan berat gudang dan jumlah karung di atas.</p></x-info-help>
                <label class="block text-sm text-gray-700 dark:text-gray-300">Gudang Bahan
                    <select x-model="form.data.inventory_id" @change="form.data.lines.forEach(line => line.warehouse_area_id = '')" required class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 bg-white px-3 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Pilih gudang bahan</option>
                        @foreach($inventories as $inventory)<option value="{{ $inventory->id }}">{{ $inventory->item_name }}</option>@endforeach
                    </select>
                </label>
                <p class="text-xs text-error-500" x-text="form.getError('inventory_id')"></p>
                <template x-for="(line, index) in form.data.lines" :key="index">
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-[1fr_1fr_1fr_auto]">
                            <label class="text-sm text-gray-700 dark:text-gray-300">Area
                                <select x-model="line.warehouse_area_id" required class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 bg-white px-3 dark:border-gray-700 dark:bg-gray-900">
                                    <option value="">Pilih area</option>
                                    <template x-for="area in receivingAreas.filter(area => area.inventory_id == form.data.inventory_id)" :key="area.id"><option :value="area.id" x-text="area.name"></option></template>
                                </select>
                            </label>
                            <label class="text-sm text-gray-700 dark:text-gray-300">Berat Aktual (Kg)<input type="number" x-model="line.actual_weight_kg" min="0.01" max="999999999.99" step="0.01" required class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 dark:border-gray-700"></label>
                            <label class="text-sm text-gray-700 dark:text-gray-300">Jumlah Karung<input type="number" x-model="line.sack_count" min="1" max="1000000" step="1" required class="mt-1.5 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 dark:border-gray-700"></label>
                            <button type="button" @click="form.data.lines.splice(index, 1)" :disabled="form.data.lines.length === 1" class="h-11 rounded-lg border border-gray-300 px-3 text-sm text-gray-500 disabled:opacity-40">Hapus</button>
                        </div>
                        <p class="mt-1 text-xs text-error-500" x-text="form.getError('lines.' + index + '.warehouse_area_id') || form.getError('lines.' + index + '.actual_weight_kg') || form.getError('lines.' + index + '.sack_count')"></p>
                    </div>
                </template>
                <button type="button" @click="form.data.lines.push(newReceivingLine())" class="text-sm text-brand-600">+ Tambah Area Barang Masuk</button>
                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="'Total penerimaan: ' + formatWeight(form.data.lines.reduce((sum, line) => sum + Number(line.actual_weight_kg || 0), 0)) + ' / ' + form.data.lines.reduce((sum, line) => sum + Number(line.sack_count || 0), 0) + ' karung'"></p>
                <p class="text-xs text-error-500" x-text="form.getError('lines')"></p>
                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" x-model="form.data.confirm" required class="mt-1">Saya memastikan barang sudah diterima, ditimbang, dan ditempatkan sesuai rincian. Konfirmasi mencatat stok dan batch.</label>
                <p class="text-xs text-error-500" x-text="form.getError('confirm') || form.getError('submission_key')"></p>
            </section>
<div class="flex justify-end gap-3"><button type="button" @click="$dispatch('close-modal', 'harvest-modal')" class="rounded-lg border px-5 py-2.5 text-gray-600 dark:text-gray-300">Batal</button><button type="submit" :disabled="form.processing" class="rounded-lg bg-brand-500 px-5 py-2.5 text-white"><span x-text="form.processing ? 'Menyimpan...' : 'Konfirmasi Panen & Terima Barang'"></span></button></div>
</form></x-modal></div>
@push('scripts')
<script>
function harvestForm() {
    return {
        receivingAreas: @js($areas),
        warehouseWeight: 0,
        form: useForm({farmer_id: '', harvest_date: '', location: '', total_weight: 0, sack_count: 0, extra_per_sack: 3, inventory_id: '', notes: '', submission_key: '', confirm: false, lines: []}),
        init() {
            ['total_weight', 'sack_count', 'extra_per_sack'].forEach(field => this.$watch('form.data.' + field, () => this.calculate()));
            window.addEventListener('create-harvest-transaction', () => this.create());
        },
        newReceivingLine() { return {warehouse_area_id: '', actual_weight_kg: 0, sack_count: 0}; },
        calculate() {
            this.warehouseWeight = (Number(this.form.data.total_weight || 0) + Number(this.form.data.sack_count || 0) * Number(this.form.data.extra_per_sack || 0)).toFixed(2);
            if (this.form.data.lines.length === 1) {
                this.form.data.lines[0].actual_weight_kg = this.warehouseWeight;
                this.form.data.lines[0].sack_count = this.form.data.sack_count;
            }
        },
        create() {
            this.form.reset();
            this.form.clearErrors();
            this.form.data.submission_key = crypto.randomUUID();
            this.form.data.harvest_date = window.getLocalDateInputValue();
            this.form.data.inventory_id = @js($inventories->count() === 1 ? (string) $inventories->first()->id : '');
            this.form.data.lines = [this.newReceivingLine()];
            this.calculate();
            this.$dispatch('open-modal', 'harvest-modal');
        },
        async submit() {
            await this.form.submit('post', @js(route('manajemen-panen.store')), {
                onSuccess: response => { toast.success('Berhasil', response.data.message); refreshDataTable('harvestTransactionTable'); this.$dispatch('close-modal', 'harvest-modal'); },
                onError: error => { if (!this.form.hasErrors()) toast.error('Gagal', error.response?.data?.message || 'Terjadi kesalahan saat menyimpan.'); }
            });
        }
    };
}
</script>
@endpush
