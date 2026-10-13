<div x-data="cashFlowDetail()">
    <x-modal name="cash-flow-detail-modal" maxWidth="max-w-7xl">
        <div class="flex flex-col max-h-[80vh] -m-6 lg:-m-10" @set-cash-flow-detail.window="open($event.detail)">
            <template x-if="item">
                <div class="flex flex-col overflow-hidden">
                    <!-- Header -->
                    <div class="px-6 lg:px-10 pt-6 lg:pt-10 pb-0 mb-4">
                        <h4 class="text-lg font-medium text-gray-800 dark:text-white/90"
                            x-text="'Rincian ' + item.category"></h4>
                        <x-info-help>Daftar transaksi kas untuk periode terpilih.</x-info-help>
                        <div x-show="item.transactions_formatted" class="mt-3 text-sm space-y-1">
                            <p>Transaksi: <span x-text="item.transactions_formatted"></span></p>
                            <p>Koreksi / pembatalan: <span x-text="item.corrections_formatted"></span></p>
                            <p class="font-semibold">Total bersih: <span x-text="item.amount_formatted"></span></p>
                            <p class="text-gray-500">Koreksi mengurangi catatan sebelumnya. Transaksi asal dapat berada di luar periode yang dipilih.</p>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto px-6 lg:px-10 pb-6 lg:pb-10">
                        <!-- Empty State Container -->
                        <template x-if="!item.details || item.details.length === 0">
                            <div
                                class="flex flex-col items-center justify-center py-20 bg-gray-50/50 dark:bg-white/1 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h5 class="text-base font-semibold text-gray-800 dark:text-white/90">Tidak ada transaksi
                                </h5>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Belum ada rincian data kas
                                    untuk periode ini.</p>
                            </div>
                        </template>

                        <!-- Data Table Container -->
                        <template x-if="item.details && item.details.length > 0">
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                                <x-data-table id="cashFlowDetailTable" :rows="[]" :columns="[
                                    ['key' => 'date', 'label' => 'Tanggal', 'width' => '150px'],
                                    ['key' => 'description', 'label' => 'Keterangan', 'render' => 'detailDescription'],
                                    [
                                        'key' => 'amount',
                                        'label' => 'Nominal',
                                        'render' => 'detailAmount',
                                        'width' => '180px',
                                    ],
                                ]"
                                    :url="null" perPage="5">
                                    <x-slot:detailDescription>
                                        <p x-text="item.description"></p>
                                        <p class="text-sm font-semibold" x-text="item.activity || ''"></p>
                                        <p class="text-sm text-gray-500" x-text="item.explanation || ''"></p>
                                        <p class="text-xs text-gray-500" x-text="item.actor ? item.actor + ' · ' + item.recorded_at : ''"></p>
                                        @can('manajemen-pemasukan-pengeluaran.read')
                                            <a x-show="item.history_url" :href="item.history_url" class="text-brand-500 text-sm">Lihat riwayat transaksi</a>
                                        @endcan
                                    </x-slot:detailDescription>
                                    <x-slot:detailAmount>
                                        <div class="text-right text-sm font-semibold text-gray-800 dark:text-white/90"
                                            x-text="item.amount"></div>
                                    </x-slot:detailAmount>
                                </x-data-table>
                            </div>
                        </template>

                        <div class="mt-4 flex justify-end">
                            <button type="button" @click="$dispatch('close-modal', 'cash-flow-detail-modal')"
                                class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </x-modal>

    @push('scripts')
        <script>
            function cashFlowDetail() {
                return {
                    item: null,

                    open(detail) {
                        this.item = detail;
                        this.$dispatch('open-modal', 'cash-flow-detail-modal');

                        // Small delay to ensure table component is initialized in DOM
                        this.$nextTick(() => {
                            setTimeout(() => {
                                const table = window.getDataTableInstances()['cashFlowDetailTable'];
                                if (table) {
                                    table.data = [...detail.details] || [];
                                    table.currentPage = 1;
                                    table.searchQuery = ''; // Reset search
                                }
                            }, 50);
                        });
                    }
                }
            }
        </script>
    @endpush
</div>
