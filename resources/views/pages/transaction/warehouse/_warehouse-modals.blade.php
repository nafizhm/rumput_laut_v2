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
        <h4 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white">Transfer Stok Antar Area</h4>
        <form @submit.prevent="submitTransfer" class="space-y-4">
            <label class="block text-sm text-gray-700 dark:text-gray-300">Jenis Gudang
                <select x-model="transferForm.data.usage" @change="transferSource = ''; transferForm.data.to_area_id = ''" required class="mt-1 w-full rounded-lg border px-3 py-2 dark:border-gray-700 dark:bg-gray-900"><option value="receiving">Barang Masuk</option><option value="finished">Barang Ready</option></select>
            </label>
            <label class="block text-sm text-gray-700 dark:text-gray-300">Area Asal
                <select x-model="transferSource" @change="transferForm.data.to_area_id = ''" required class="mt-1 w-full rounded-lg border px-3 py-2 dark:border-gray-700 dark:bg-gray-900"><option value="">Pilih area asal</option><template x-for="area in transferOrigins" :key="area.key"><option :value="area.key" x-text="area.label"></option></template></select>
            </label>
            <p x-show="transferOrigin" class="rounded-lg bg-green-50 p-3 text-sm text-green-700" x-text="transferOrigin ? 'Stok: ' + formatWeight(transferOrigin.weight) + ' / ' + transferOrigin.count + (transferForm.data.usage === 'receiving' ? ' karung' : ' kemasan') : ''"></p>
            <label class="block text-sm text-gray-700 dark:text-gray-300">Area Tujuan
                <select x-model="transferForm.data.to_area_id" required class="mt-1 w-full rounded-lg border px-3 py-2 dark:border-gray-700 dark:bg-gray-900"><option value="">Pilih area tujuan</option><template x-for="area in transferTargets" :key="area.id"><option :value="area.id" x-text="area.label"></option></template></select>
            </label>
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Kilo (Kg)" name="weight_kg" type="number" min="0.01" step="0.01" x-model="transferForm.data.weight_kg" required />
                <x-input label="Karung / Kemasan" name="sack_count" type="number" min="1" step="1" x-model="transferForm.data.sack_count" required />
            </div>
            <x-input label="Tanggal Transfer" name="transfer_date" type="date" x-model="transferForm.data.transfer_date" required />
            <x-input label="Catatan" name="description" x-model="transferForm.data.description" maxlength="255" />
            <p class="text-xs text-gray-500">Transfer hanya dalam jenis gudang yang sama. Stok lama boleh dipindahkan ke area baru, tetapi tidak sebaliknya.</p>
            <p x-show="transferError" class="text-sm text-red-600" x-text="transferError"></p>
            <div class="flex justify-end gap-3"><button type="button" @click="$dispatch('close-modal', 'transfer-modal')" class="rounded-lg border px-4 py-2 text-sm dark:text-gray-300">Batal</button><button type="submit" :disabled="transferForm.processing || transferLoading" class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white disabled:opacity-50" x-text="transferLoading ? 'Memuat...' : (transferForm.processing ? 'Menyimpan...' : 'Simpan Transfer')"></button></div>
        </form>
    </x-modal>

    <x-modal name="adjustment-modal" maxWidth="max-w-[1100px]">
        <h4 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white">Penyesuaian Stok per Area</h4>
        <p class="mb-5 text-sm text-gray-500">Isi total kilo dan karung/kemasan aktual setiap area. Stok lama ditampilkan di bagian paling bawah.</p>
        <form @submit.prevent="submitAdjustment" class="max-h-[75vh] overflow-y-auto space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input label="Tanggal Penyesuaian" name="movement_date" type="date" x-model="adjustmentForm.data.movement_date" required />
                <x-input label="Alasan / Catatan" name="description" x-model="adjustmentForm.data.description" maxlength="255" required />
            </div>
            <p x-show="adjustmentLoading" class="text-sm text-gray-500">Memuat stok terbaru...</p>
            <p x-show="adjustmentError" class="rounded-lg bg-red-50 p-3 text-sm text-red-600" x-text="adjustmentError"></p>
            <template x-for="stage in ['Barang Masuk', 'Barang Ready', 'Stok Lama']" :key="stage">
                <section class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <h3 class="bg-gray-50 px-4 py-3 font-semibold text-gray-800 dark:bg-gray-800 dark:text-white" x-text="stage"></h3>
                    <template x-for="area in adjustmentAreas.filter(area => area.stage === stage)" :key="area.key">
                        <div class="flex items-center gap-3 border-t px-4 py-2 dark:border-gray-700">
                            <span class="w-40 shrink-0 break-words text-sm font-medium text-gray-800 dark:text-white" x-text="area.label"></span>
                            <label class="flex min-w-0 flex-1 items-center gap-2">
                                <input type="number" min="0" max="9999999999.99" step="0.01" required x-model="area.actual_weight" :aria-label="area.label + ' Kg'" class="min-w-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <span class="shrink-0 text-sm text-gray-500">Kg</span>
                            </label>
                            <label class="flex min-w-0 flex-1 items-center gap-2">
                                <input type="number" min="0" step="1" required x-model="area.actual_count" :aria-label="area.label + (stage === 'Barang Ready' ? ' Kemasan' : ' Karung')" class="min-w-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <span class="shrink-0 text-sm text-gray-500" x-text="stage === 'Barang Ready' ? 'Kemasan' : 'Karung'"></span>
                            </label>
                        </div>
                    </template>
                </section>
            </template>
            <p class="text-xs text-gray-500">Penyesuaian dibagi proporsional ke batch yang ada dan tetap dicatat dalam riwayat. Area tanpa batch belum dapat ditambah melalui penyesuaian.</p>
            <div class="flex justify-end gap-3 border-t pt-4 dark:border-gray-700">
                <button type="button" @click="$dispatch('close-modal', 'adjustment-modal')" class="rounded-lg border px-4 py-2 text-sm dark:border-gray-700 dark:text-gray-300">Batal</button>
                <button type="submit" :disabled="adjustmentForm.processing || adjustmentLoading || !adjustmentAreas.length" class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white disabled:opacity-50" x-text="adjustmentForm.processing ? 'Menyimpan...' : 'Simpan Penyesuaian'"></button>
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

        transferOptions: { areas: [], legacy: [] },
        transferSource: '',
        transferLoading: false,
        transferError: '',
        get transferOrigins() {
            return [...this.transferOptions.areas.filter(area => area.usage === this.transferForm.data.usage).map(area => ({ ...area, key: 'area:' + area.id })),
                ...this.transferOptions.legacy.map(area => ({ ...area, key: 'legacy:' + area.id }))];
        },
        get transferOrigin() { return this.transferOrigins.find(area => area.key === this.transferSource); },
        get transferTargets() { return this.transferOptions.areas.filter(area => area.usage === this.transferForm.data.usage && 'area:' + area.id !== this.transferSource); },
        transferForm: useForm({ usage: 'receiving', source_kind: 'area', from_id: '', to_area_id: '', transfer_date: window.getLocalDateInputValue(), weight_kg: '', sack_count: '', description: '' }),

        adjustmentAreas: [],
        adjustmentLoading: false,
        adjustmentError: '',
        adjustmentForm: useForm({ movement_date: window.getLocalDateInputValue(), description: '', items: [] }),

        init() {
            window.addEventListener('open-receive-modal', () => {
                this.receiveForm.reset();
                this.receiveForm.data.movement_date = window.getLocalDateInputValue();
                this.$dispatch('open-modal', 'receive-modal');
            });
            window.addEventListener('open-adjustment-modal', async () => {
                this.adjustmentForm.reset();
                this.adjustmentForm.data.movement_date = window.getLocalDateInputValue();
                this.adjustmentAreas = [];
                this.adjustmentError = '';
                this.adjustmentLoading = true;
                this.$dispatch('open-modal', 'adjustment-modal');
                try {
                    const response = await fetch('{{ route("manajemen-gudang.adjustment-data") }}', { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Stok area gagal dimuat. Tutup dan buka kembali form.');
                    const result = await response.json();
                    this.adjustmentAreas = result.data.flatMap(area => {
                        if (area.stage === 'Stok Lama') return area.items.map(item => ({ ...area, key: 'legacy-' + item.id, label: item.name, items: [item] }));
                        return [area];
                    }).map(area => {
                        const weight = area.items.reduce((total, item) => total + Number(item.expected_weight), 0);
                        const count = area.items.reduce((total, item) => total + Number(item.expected_count), 0);
                        return { ...area, expected_weight: weight, expected_count: count, actual_weight: Number(weight.toFixed(2)), actual_count: count };
                    });
                } catch (error) {
                    this.adjustmentError = error.message;
                } finally {
                    this.adjustmentLoading = false;
                }
            });

            window.addEventListener('open-transfer-modal', async () => {
                this.transferForm.reset();
                this.transferForm.data.transfer_date = window.getLocalDateInputValue();
                this.transferSource = '';
                this.transferError = '';
                this.transferOptions = { areas: [], legacy: [] };
                this.transferLoading = true;
                this.$dispatch('open-modal', 'transfer-modal');
                try {
                    const response = await fetch('{{ route("manajemen-gudang.transfer-data") }}', { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Gagal memuat stok area.');
                    this.transferOptions = (await response.json()).data;
                } catch (error) { this.transferError = error.message; }
                finally { this.transferLoading = false; }
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
            this.transferError = '';
            const [kind, id] = this.transferSource.split(':');
            this.transferForm.data.source_kind = kind;
            this.transferForm.data.from_id = id;
            await this.transferForm.submit('post', '{{ route("manajemen-gudang.transfer") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    this.$dispatch('close-modal', 'transfer-modal');
                    refreshDataTable('movementTable');
                    this.refreshInventories();
                },
                onError: (err) => {
                    this.transferError = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'Transfer gagal';
                }
            });
        },

        allocate(total, weights) {
            const sum = weights.reduce((value, weight) => value + weight, 0);
            const shares = weights.map((weight, index) => ({ index, exact: sum > 0 ? total * weight / sum : total / weights.length }));
            const values = shares.map(share => Math.floor(share.exact));
            let remaining = total - values.reduce((value, amount) => value + amount, 0);
            shares.sort((first, second) => (second.exact - Math.floor(second.exact)) - (first.exact - Math.floor(first.exact)));
            for (const share of shares) {
                if (remaining <= 0) break;
                values[share.index]++;
                remaining--;
            }
            return values;
        },

        distributeArea(area) {
            const weight = Math.round(Number(area.actual_weight) * 100);
            const count = Number(area.actual_count);
            if (!Number.isFinite(weight) || weight < 0 || !Number.isInteger(count) || count < 0 || ((weight > 0) !== (count > 0))) {
                throw new Error(area.label + ': kilo dan kemasan harus sama-sama nol atau lebih dari nol.');
            }
            if (!area.items.length) {
                if (weight || count) throw new Error(area.label + ': belum ada batch. Tambahkan barang melalui penerimaan atau produksi terlebih dahulu.');
                return [];
            }
            const active = area.items.filter(item => item.expected_count > 0 || item.expected_weight > 0);
            const items = active.length ? active : area.items;
            const counts = this.allocate(count, items.map(item => Number(item.expected_count)));
            const weights = this.allocate(weight, items.map((item, index) => counts[index] > 0 ? (Number(item.expected_weight) || counts[index]) : 0));
            if (weight > 0 && counts.some((amount, index) => amount > 0 && weights[index] === 0)) {
                throw new Error(area.label + ': berat terlalu kecil untuk dibagi ke seluruh batch.');
            }
            return area.items.map(item => {
                const index = items.indexOf(item);
                return { ...item, actual_weight: index < 0 ? 0 : weights[index] / 100, actual_count: index < 0 ? 0 : counts[index] };
            });
        },

        async submitAdjustment() {
            this.adjustmentError = '';
            try {
                this.adjustmentForm.data.items = this.adjustmentAreas.flatMap(area => this.distributeArea(area));
            } catch (error) {
                this.adjustmentError = error.message;
                return;
            }

            await this.adjustmentForm.submit('post', '{{ route("manajemen-gudang.adjustment") }}', {
                onSuccess: (res) => {
                    toast.success('Berhasil', res.data.message);
                    this.$dispatch('close-modal', 'adjustment-modal');
                    refreshDataTable('movementTable');
                    this.refreshInventories();
                },
                onError: (err) => {
                    this.adjustmentError = Object.values(err.response?.data?.errors || {}).flat().join(' ') || err.response?.data?.message || 'Penyesuaian gagal disimpan';
                }
            });
        }
    }
}
</script>
@endpush
