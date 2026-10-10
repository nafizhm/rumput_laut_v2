@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div x-data="dashboard()" x-init="init()" class="space-y-6">
        <!-- Metrics Start -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Ringkasan</h3>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <x-input name="dashboard_start_date" type="date" x-model="filters.startDate" />
                        <p class="text-gray-500 dark:text-gray-400">s/d</p>
                        <x-input name="dashboard_end_date" type="date" x-model="filters.endDate" />
                    </div>
                    <button @click="applyFilters()"
                        class="text-theme-sm shadow-theme-xs inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                        Terapkan
                    </button>
                </div>
            </div>
            <div
                class="grid rounded-2xl border border-gray-200 bg-white sm:grid-cols-2 xl:grid-cols-4 dark:border-gray-800 dark:bg-gray-900">
                <!-- Net Profit -->
                <div class="border-b border-gray-200 px-6 py-5 sm:border-r xl:border-b-0 dark:border-gray-800">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Laba Bersih</span>
                    <div class="mt-2 flex items-end gap-3">
                        <template x-if="loading">
                            <div class="h-8 w-32 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                        </template>
                        <template x-if="!loading">
                            <h4 class="text-right tabular-nums text-title-xs sm:text-title-sm font-bold text-gray-800 dark:text-white/90"
                                x-text="stats.profitReady === false ? 'Belum tersedia' : formatIDR(stats.netProfit)"></h4>
                        </template>
                    </div>
                </div>
                <!-- Total Sales -->
                <div class="border-b border-gray-200 px-6 py-5 xl:border-r xl:border-b-0 dark:border-gray-800">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Total Penjualan</span>
                    <div class="mt-2 flex items-end gap-3">
                        <template x-if="loading">
                            <div class="h-8 w-32 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                        </template>
                        <template x-if="!loading">
                            <h4 class="text-right tabular-nums text-title-xs sm:text-title-sm font-bold text-gray-800 dark:text-white/90"
                                x-text="formatIDR(stats.totalSales)"></h4>
                        </template>
                    </div>
                </div>
                <!-- Farmer Debt -->
                <div class="border-b border-gray-200 px-6 py-5 sm:border-r sm:border-b-0 dark:border-gray-800">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Piutang Petani</span>
                    <div class="mt-2 flex items-end gap-3">
                        <template x-if="loading">
                            <div class="h-8 w-32 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                        </template>
                        <template x-if="!loading">
                            <h4 class="text-right tabular-nums text-title-xs sm:text-title-sm font-bold text-gray-800 dark:text-white/90"
                                x-text="formatIDR(stats.farmerDebt)"></h4>
                        </template>
                    </div>
                </div>
                <!-- Warehouse Stock -->
                <div class="px-6 py-5">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Stok Gudang</span>
                    <div class="mt-2 flex items-end gap-3">
                        <template x-if="loading">
                            <div class="h-8 w-32 animate-pulse rounded-lg bg-gray-200 dark:bg-gray-700"></div>
                        </template>
                        <template x-if="!loading">
                            <h4 class="text-title-xs sm:text-title-sm font-bold text-gray-800 dark:text-white/90"
                                x-text="formatWeight(stats.warehouseStock)"></h4>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <!-- Metrics End -->

        <section class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h3 class="text-lg font-semibold text-gray-800 dark:text-white">Proses Harian / Produksi</h3><p class="mt-1 text-sm text-gray-500">Proses yang dimulai pada periode pilihan. Selesai proses berpindah ke WIP sampai packing selesai.</p></div>
                <a href="{{ route('manajemen-gudang.index') }}" class="text-sm font-semibold text-brand-600">Lihat Dashboard Gudang</a>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 text-sm text-gray-600 dark:text-gray-300">
                <div>Total Proses<p class="mt-1 text-xl font-semibold" x-text="stats.production.count"></p></div>
                <div>Dalam Proses<p class="mt-1 text-xl font-semibold" x-text="stats.production.processing"></p></div>
                <div>WIP / Menunggu Packing<p class="mt-1 text-xl font-semibold" x-text="stats.production.wip"></p></div>
                <div>Selesai Packing<p class="mt-1 text-xl font-semibold" x-text="stats.production.packed"></p></div>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                    <thead><tr><th class="p-3">Nomor / Tanggal</th><th class="p-3">Pekerjaan / Area</th><th class="p-3 text-right">Karung Sumber</th><th class="p-3">Status</th></tr></thead>
                    <tbody><template x-for="run in stats.production.recent" :key="run.number"><tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="p-3"><a :href="run.url" class="font-semibold text-brand-600" x-text="run.number"></a><p class="mt-1 text-xs text-gray-500" x-text="run.date"></p></td>
                        <td class="p-3"><span x-text="run.work_type"></span><p class="mt-1 text-xs text-gray-500" x-text="run.area"></p></td>
                        <td class="p-3 text-right tabular-nums" x-text="Number(run.sacks).toLocaleString('id-ID')"></td>
                        <td class="p-3" x-text="({processing:'Dalam Proses', wip:'WIP / Menunggu Packing', packed:'Selesai Packing'})[run.status] || run.status"></td>
                    </tr></template></tbody>
                </table>
                <p x-show="!loading && stats.production.recent.length === 0" class="p-4 text-center text-sm text-gray-500">Belum ada proses yang dimulai pada periode pilihan.</p>
            </div>
        </section>

        <div class="gap-5 space-y-5 sm:gap-6 sm:space-y-6 xl:grid xl:grid-cols-12 xl:space-y-0">
            <div class="xl:col-span-7 2xl:col-span-8">
                <div class="space-y-5 sm:space-y-6">
                    <!-- Funnel Chart: Income vs Expenses -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="mb-6 flex justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pendapatan vs Pengeluaran
                                </h3>
                                <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">Tren performa keuangan
                                    bulanan</p>
                            </div>
                        </div>
                        <div class="max-w-full overflow-hidden">
                            <div id="financialChart" class="-ml-5"></div>
                        </div>
                        <!-- Stats below chart -->
                        <div class="mt-6 grid grid-cols-2 gap-4 border-t border-gray-100 pt-6 dark:border-gray-800">
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Tingkat Penyusutan</span>
                                <div class="mt-1 flex items-center gap-2">
                                    <h4 class="text-lg font-bold text-gray-800 dark:text-white"
                                        x-text="loading ? '...' : stats.shrinkageRate + '%'"></h4>
                                    <span class="text-xs text-gray-400">(Rata-rata 6 bulan terakhir)</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Selisih Logistik</span>
                                <div class="mt-1 flex items-center gap-2">
                                    <h4 class="text-lg font-bold text-gray-800 dark:text-white"
                                        x-text="loading ? '...' : formatWeight(stats.logisticsDiscrepancy)"></h4>
                                    <span class="text-xs text-gray-400">(Total kerugian/keuntungan)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Funnel Chart End -->

                    <!-- Invoice Table (existing) -->
                    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Invoice Terbaru</h3>
                        </div>
                        <div class="custom-scrollbar overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-900">
                                        <th
                                            class="px-6 py-4 text-left text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            No. Seri:</th>
                                        <th
                                            class="px-6 py-4 text-left text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            Tanggal</th>
                                        <th
                                            class="px-6 py-4 text-left text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            Pabrik</th>
                                        <th
                                            class="px-6 py-4 text-left text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            Jumlah</th>
                                        <th
                                            class="px-6 py-4 text-left text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                    <!-- Example rows (keep as needed) -->
                                    <template x-for="invoice in stats.recentInvoices" :key="invoice.invoice_number">
                                        <tr>
                                            <td class="px-6 py-4 text-left text-sm whitespace-nowrap text-gray-700 dark:text-gray-400"
                                                x-text="invoice.invoice_number"></td>
                                            <td class="px-6 py-4 text-left text-sm whitespace-nowrap text-gray-700 dark:text-gray-400"
                                                x-text="invoice.sale_date"></td>
                                            <td class="px-6 py-4 text-left text-sm whitespace-nowrap text-gray-700 dark:text-gray-400"
                                                x-text="invoice.factory_name"></td>
                                            <td class="text-right tabular-nums px-6 py-4  text-sm whitespace-nowrap text-gray-700 dark:text-gray-400"
                                                x-text="formatCurrency(invoice.total_sale_value)"></td>
                                            <td class="px-6 py-4 text-left">
                                                <span
                                                    :class="invoice.payment_status === 'paid' ?
                                                        'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' :
                                                        'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400'"
                                                    class="text-theme-xs rounded-full px-2 py-0.5 font-medium"
                                                    x-text="invoice.status"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-6 xl:col-span-5 2xl:col-span-4">
                <!-- Product Performance (existing, simplified) -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Performa Mingguan</h3>
                        </div>
                    </div>
                    <div x-data="{ selected: 'harvest' }">
                        <div class="flex w-full items-center gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-gray-900">
                            <button @click="selected = 'harvest'"
                                :class="selected === 'harvest' ?
                                    'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' :
                                    'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm w-full rounded-md px-3 py-2 font-medium">Panen</button>
                            <button @click="selected = 'expense'"
                                :class="selected === 'expense' ?
                                    'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' :
                                    'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm w-full rounded-md px-3 py-2 font-medium">Pengeluaran</button>
                            <button @click="selected = 'sales'"
                                :class="selected === 'sales' ?
                                    'shadow-theme-xs text-gray-900 dark:text-white bg-white dark:bg-gray-800' :
                                    'text-gray-500 dark:text-gray-400'"
                                class="text-theme-sm w-full rounded-md px-3 py-2 font-medium">Penjualan</button>
                        </div>
                        <div class="mt-4">
                            <!-- Harvest Panel -->
                            <div x-show="selected === 'harvest'" class="space-y-4">
                                <div
                                    class="grid grid-cols-2 justify-between gap-10 divide-x divide-gray-100 rounded-xl border border-gray-100 bg-white py-4 dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-800/[0.03]">
                                    <div class="px-5"><span class="block text-sm text-gray-500 dark:text-gray-400">Total
                                            Panen</span>
                                        <div class="mt-1 flex items-center gap-2">
                                            <h4 class="text-xl font-semibold text-gray-800 dark:text-white/90"
                                                x-text="stats.weeklyHarvest.count"></h4>
                                        </div>
                                    </div>
                                    <div class="px-5"><span class="block text-sm text-gray-500 dark:text-gray-400">Total
                                            Nilai</span>
                                        <div class="mt-1 flex items-center gap-2">
                                            <h4 class="text-right tabular-nums text-xl font-semibold text-gray-800 dark:text-white/90"
                                                x-text="formatIDRShort(stats.weeklyHarvest.value)"></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-gray-400">Data Terakhir</p>
                                    <template x-for="item in stats.weeklyHarvest.recent">
                                        <div
                                            class="flex items-center justify-between text-theme-sm border-b border-gray-50 pb-2 last:border-0 dark:border-gray-800">
                                            <div>
                                                <p class="font-medium text-gray-800 dark:text-white/90"
                                                    x-text="item.name"></p>
                                                <p class="text-xs text-gray-500"
                                                    x-text="item.date + ' • ' + formatWeight(item.weight)"></p>
                                            </div>
                                            <p class="text-right tabular-nums font-semibold text-gray-700 dark:text-gray-300"
                                                x-text="formatIDRShort(item.value)"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <!-- Expense Panel -->
                            <div x-show="selected === 'expense'" class="space-y-4">
                                <div
                                    class="grid grid-cols-2 justify-between gap-10 divide-x divide-gray-100 rounded-xl border border-gray-100 bg-white py-4 dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-800/[0.03]">
                                    <div class="px-5"><span class="block text-sm text-gray-500 dark:text-gray-400">Total
                                            Item</span>
                                        <div class="mt-1 flex items-center gap-2">
                                            <h4 class="text-xl font-semibold text-gray-800 dark:text-white/90"
                                                x-text="stats.weeklyExpense.count"></h4>
                                        </div>
                                    </div>
                                    <div class="px-5"><span class="block text-sm text-gray-500 dark:text-gray-400">Total
                                            Pengeluaran</span>
                                        <div class="mt-1 flex items-center gap-2">
                                            <h4 class="text-right tabular-nums text-xl font-semibold text-gray-800 dark:text-white/90"
                                                x-text="formatIDRShort(stats.weeklyExpense.value)"></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-gray-400">Data Terakhir</p>
                                    <template x-for="item in stats.weeklyExpense.recent">
                                        <div
                                            class="flex items-center justify-between text-theme-sm border-b border-gray-50 pb-2 last:border-0 dark:border-gray-800">
                                            <div>
                                                <p class="font-medium text-gray-800 dark:text-white/90"
                                                    x-text="item.name"></p>
                                                <p class="text-xs text-gray-500" x-text="item.date"></p>
                                            </div>
                                            <p class="text-right tabular-nums font-semibold text-error-600" x-text="formatIDRShort(item.amount)">
                                            </p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <!-- Sales Panel -->
                            <div x-show="selected === 'sales'" class="space-y-4">
                                <div
                                    class="grid grid-cols-2 justify-between gap-10 divide-x divide-gray-100 rounded-xl border border-gray-100 bg-white py-4 dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-800/[0.03]">
                                    <div class="px-5"><span class="block text-sm text-gray-500 dark:text-gray-400">Total
                                            Penjualan</span>
                                        <div class="mt-1 flex items-center gap-2">
                                            <h4 class="text-xl font-semibold text-gray-800 dark:text-white/90"
                                                x-text="stats.weeklySales.count"></h4>
                                        </div>
                                    </div>
                                    <div class="px-5"><span class="block text-sm text-gray-500 dark:text-gray-400">Total
                                            Nilai</span>
                                        <div class="mt-1 flex items-center gap-2">
                                            <h4 class="text-right tabular-nums text-xl font-semibold text-gray-800 dark:text-white/90"
                                                x-text="formatIDRShort(stats.weeklySales.value)"></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-gray-400">Data Terakhir</p>
                                    <template x-for="item in stats.weeklySales.recent">
                                        <div
                                            class="flex items-center justify-between text-theme-sm border-b border-gray-50 pb-2 last:border-0 dark:border-gray-800">
                                            <div>
                                                <p class="font-medium text-gray-800 dark:text-white/90"
                                                    x-text="item.name"></p>
                                                <p class="text-xs text-gray-500"
                                                    x-text="item.date + ' • ' + formatWeight(item.weight)"></p>
                                            </div>
                                            <p class="text-right tabular-nums font-semibold text-success-600" x-text="formatIDRShort(item.value)">
                                            </p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Loans Card -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="mb-6 flex justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pinjaman Terakhir</h3>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <template x-if="stats.recentLoans.length === 0">
                            <p class="text-sm text-gray-500 text-center py-4">Belum ada data pinjaman</p>
                        </template>
                        <template x-for="loan in stats.recentLoans" :key="loan.farmer_name + loan.date">
                            <div
                                class="flex items-center justify-between border-b border-gray-50 pb-4 last:border-0 last:pb-0 dark:border-gray-800">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-800 dark:text-white/90"
                                            x-text="loan.farmer_name"></h4>
                                        <p class="text-xs text-gray-500" x-text="loan.date"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-right tabular-nums text-sm font-bold text-gray-800 dark:text-white/90"
                                        x-text="formatIDRShort(loan.amount)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function dashboard() {
            return {
                loading: true,
                stats: {
                    netProfit: 0,
                    totalSales: 0,
                    farmerDebt: 0,
                    warehouseStock: 0,
                    shrinkageRate: 0,
                    logisticsDiscrepancy: 0,
                    recentInvoices: [],
                    production: {count: 0, processing: 0, wip: 0, packed: 0, sacks: 0, recent: []},
                    weeklyHarvest: {
                        count: 0,
                        value: 0,
                        recent: []
                    },
                    weeklyExpense: {
                        count: 0,
                        value: 0,
                        recent: []
                    },
                    weeklySales: {
                        count: 0,
                        value: 0,
                        recent: []
                    },
                    recentLoans: []
                },
                financialChart: null,
                filters: {
                    startDate: window.get3MonthsAgoStartDateInputValue(),
                    endDate: window.getLocalDateInputValue(),
                },

                async init() {
                    this.filters.startDate = window.get3MonthsAgoStartDateInputValue();
                    this.filters.endDate = window.getLocalDateInputValue();
                    await Promise.all([this.fetchStats(), this.initChart()]);
                },

                buildQuery() {
                    const params = new URLSearchParams({
                        start_date: this.filters.startDate,
                        end_date: this.filters.endDate,
                    });
                    return params.toString();
                },

                async applyFilters() {
                    await Promise.all([this.fetchStats(), this.initChart()]);
                },

                async fetchStats() {
                    this.loading = true;
                    try {
                        const response = await fetch(`/dashboard/stats?${this.buildQuery()}`);
                        if (!response.ok) throw new Error('Stats API error');
                        const data = await response.json();
                        this.stats = {
                            ...this.stats,
                            ...data
                        };
                    } catch (error) {
                        console.error('Error fetching stats:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async initChart() {
                    try {
                        const response = await fetch(`/dashboard/chart-data?${this.buildQuery()}`);
                        if (!response.ok) throw new Error('Chart API error');
                        const data = await response.json();

                        if (this.financialChart) this.financialChart.destroy();

                        const options = {
                            series: [{
                                    name: 'Pendapatan',
                                    data: data.income
                                },
                                {
                                    name: 'Pengeluaran',
                                    data: data.expenses
                                }
                            ],
                            chart: {
                                type: 'area',
                                height: 350,
                                toolbar: {
                                    show: false
                                },
                                zoom: {
                                    enabled: false
                                },
                                background: 'transparent'
                            },
                            colors: ['#4F46E5', '#EF4444'],
                            dataLabels: {
                                enabled: false
                            },
                            stroke: {
                                curve: 'smooth',
                                width: 2
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.45,
                                    opacityTo: 0.05,
                                    stops: [20, 100]
                                }
                            },
                            xaxis: {
                                categories: data.months,
                                axisBorder: {
                                    show: false
                                },
                                axisTicks: {
                                    show: false
                                }
                            },
                            yaxis: {
                                labels: {
                                    formatter: (value) => this.formatIDRShort(value)
                                }
                            },
                            grid: {
                                borderColor: '#E2E8F0',
                                strokeDashArray: 4,
                                padding: {
                                    left: 0,
                                    right: 0
                                }
                            },
                            tooltip: {
                                y: {
                                    formatter: (val) => this.formatCurrency(val)
                                }
                            }
                        };

                        this.financialChart = new ApexCharts(document.querySelector("#financialChart"), options);
                        this.financialChart.render();

                        // Update shrinkage & discrepancy if provided
                        if (data.shrinkageRate !== undefined) this.stats.shrinkageRate = data.shrinkageRate;
                        if (data.logisticsDiscrepancy !== undefined) this.stats.logisticsDiscrepancy = data
                            .logisticsDiscrepancy;

                    } catch (error) {
                        console.error('Error loading chart:', error);
                        const chartContainer = document.querySelector("#financialChart");
                        if (chartContainer) chartContainer.innerHTML =
                            '<div class="text-center text-red-500 py-10">Failed to load chart</div>';
                    }
                },

                formatIDR(value) {
                    return formatCurrency(value);
                },

                formatIDRShort(value) {
                    if (value >= 1_000_000_000) return (value / 1_000_000_000).toFixed(1) + 'M';
                    if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'JT';
                    if (value >= 1_000) return (value / 1_000).toFixed(0) + 'RB';
                    return value.toString();
                },

                formatWeight(value) {
                    return window.formatWeight(value);
                },

                formatCurrency(value) {
                    return window.formatCurrency(value);
                }
            }
        }
    </script>
@endpush
