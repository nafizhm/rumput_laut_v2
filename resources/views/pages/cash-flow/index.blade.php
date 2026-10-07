@extends('layouts.app')

@section('title', 'Laporan Arus Kas')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Laporan Arus Kas" :links="[['name' => 'Keuangan', 'url' => '#'], ['name' => 'Arus Kas', 'url' => route('cash-flow.index')]]" />
@endsection

@section('content')
    <div x-data="cashFlowPage()" x-init="init()" class="space-y-6">
        <x-data-table id="cashFlowTable" :rows="[]" :url="route('cash-flow.data')" :columns="[
            ['key' => 'category', 'label' => 'Kategori', 'sortable' => true],
            ['key' => 'type', 'label' => 'Tipe', 'sortable' => true, 'render' => 'typeBadge'],
            ['key' => 'amount_formatted', 'label' => 'Total Nominal', 'sortable' => true, 'align' => 'right'],
            ['key' => 'id', 'label' => 'Aksi', 'render' => 'action', 'width' => '120px'],
        ]">
            <x-slot:headerAction>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <x-input name="cashflow_start_date" type="date" x-model="filters.startDate"
                            @change="updateTable()" />
                        <p class="text-gray-500 dark:text-gray-400">s/d</p>
                        <x-input name="cashflow_end_date" type="date" x-model="filters.endDate"
                            @change="updateTable()" />
                    </div>

                    <!-- Transaction Type Filter -->
                    <div class="relative">
                        <select x-model="filters.type" @change="updateTable()"
                            class="h-11 w-full appearance-none rounded-lg border border-gray-300 bg-white pl-4 pr-10 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors focus:border-brand-500 focus:ring focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                            <option value="all">Semua Transaksi</option>
                            <option value="income">Hanya Masuk</option>
                            <option value="expense">Hanya Keluar</option>
                        </select>
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="m19 9-7 7-7-7" />
                            </svg>
                        </span>
                    </div>

                    <!-- Export Buttons -->
                    {{-- <div class="flex items-center gap-2">
                        <button type="button"
                            class="flex h-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            Export PDF
                        </button>
                        <button type="button"
                            class="flex h-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                <line x1="3" y1="9" x2="21" y2="9" />
                                <line x1="9" y1="21" x2="9" y2="9" />
                            </svg>
                            Excel
                        </button>
                    </div> --}}
                </div>
            </x-slot:headerAction>

            <x-slot:typeBadge>
                <span
                    :class="item.type === 'Cash In' ?
                        'inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20' :
                        'inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20'"
                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium" x-text="item.type"></span>
            </x-slot:typeBadge>
            <x-slot:action>
                <div class="flex items-center gap-2">
                    <button type="button" @click="$dispatch('set-cash-flow-detail', item)"
                        class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-gray-700 bg-gray-100 transition-colors rounded-lg hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                        Detail
                    </button>
                </div>
            </x-slot:action>
        </x-data-table>

        @include('pages.cash-flow._detail-modal')
    </div>

    @push('scripts')
        <script>
            function cashFlowPage() {
                return {
                    filters: {
                        startDate: window.getMonthStartDateInputValue(),
                        endDate: window.getLocalDateInputValue(),
                        type: 'all'
                    },
                    tableData: [],

                    init() {
                        setInterval(() => {
                            const table = window.getDataTableInstances()['cashFlowTable'];
                            if (table && table.data) {
                                this.tableData = table.data;
                            }
                        }, 500);
                    },

                    updateTable() {
                        const table = window.getDataTableInstances()['cashFlowTable'];
                        if (table) {
                            table.url =
                                `/laporan-arus-kas/data?start_date=${this.filters.startDate}&end_date=${this.filters.endDate}&type=${this.filters.type}`;
                            table.refreshData();
                        }
                    },

                    get totalBalance() {
                        return this.tableData.reduce((acc, item) => {
                            return item.type === 'Cash In' ? acc + item.amount : acc - item.amount;
                        }, 0);
                    },

                    formatCurrency(value) {
                        return new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(value);
                    }
                }
            }
        </script>
    @endpush
@endsection
