@props(['rows', 'columns'])

<div x-data="dataTable(@js($rows))"
    class="overflow-hidden rounded-xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/3">
    <div class="mb-4 flex flex-col gap-2 px-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="text-gray-500 dark:text-gray-400"> Show </span>
            <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                <select
                    class="dark:bg-dark-900 h-9 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none py-2 pl-3 pr-8 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                    :class="isOptionSelected && 'text-gray-500 dark:text-gray-400'" @click="isOptionSelected = true"
                    @change="perPage = parseInt($event.target.value)">
                    <option value="5" class="text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                        5
                    </option>
                    <option value="10" class="text-gray-500 dark:bg-gray-900 dark:text-gray-400" selected>
                        10
                    </option>
                    <option value="25" class="text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                        25
                    </option>
                </select>
                <span
                    class="pointer-events-none absolute right-2 top-1/2 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                    <svg class="stroke-current" width="16" height="16" viewBox="0 0 16 16" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M3.8335 5.9165L8.00016 10.0832L12.1668 5.9165" stroke="" stroke-width="1.2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
            </div>
            <span class="text-gray-500 dark:text-gray-400"> entries </span>
        </div>

        <div class="relative">
            <button class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z"
                        fill="" />
                </svg>
            </button>

            <input type="text" x-model="search" placeholder="Search..."
                class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-11 pr-4 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800 xl:w-[300px]" />
        </div>
    </div>

    <div class="max-w-full overflow-x-auto">
        <div class="min-w-[1102px]">
            <!-- table header start -->
            <div class="grid border-t border-gray-200 dark:border-gray-800"
                style="grid-template-columns: 50px repeat({{ count($columns) }}, minmax(0, 1fr));">
                <div class="flex items-center justify-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                    <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                        No
                    </p>
                </div>
                @foreach ($columns as $column)
                    <div class="flex items-center border-r border-gray-200 px-4 py-3 dark:border-gray-800">
                        @if (isset($column['sortable']) && $column['sortable'])
                            <div class="flex w-full cursor-pointer items-center justify-between"
                                @click="sortBy('{{ $column['key'] }}')">
                                <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                                    {{ $column['label'] }}
                                </p>

                                <span class="flex flex-col gap-0.5">
                                    <svg class="fill-gray-300 dark:fill-gray-700" width="8" height="5"
                                        viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z"
                                            fill="" />
                                    </svg>

                                    <svg class="fill-gray-300 dark:fill-gray-700" width="8" height="5"
                                        viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z"
                                            fill="" />
                                    </svg>
                                </span>
                            </div>
                        @else
                            <div class="flex w-full items-center justify-between">
                                <p class="text-theme-xs font-medium text-gray-700 dark:text-gray-400">
                                    {{ $column['label'] }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <!-- table header end -->

            <!-- table body start -->
            <template x-for="(item, index) in paginatedData" :key="item.id">
                <div class="grid border-t border-gray-100 dark:border-gray-800"
                    style="grid-template-columns: 50px repeat({{ count($columns) }}, minmax(0, 1fr));">
                    <div
                        class="flex items-center justify-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                        <p class="text-theme-sm text-gray-700 dark:text-gray-400"
                            x-text="(currentPage - 1) * perPage + index + 1"></p>
                    </div>
                    @foreach ($columns as $column)
                        <div class="flex items-center border-r border-gray-100 px-4 py-3 dark:border-gray-800">
                            @if (isset($column['render']) && isset(${$column['render']}))
                                {{ ${$column['render']} }}
                            @else
                                <p class="text-theme-sm text-gray-700 dark:text-gray-400"
                                    x-text="item.{{ $column['key'] }}"></p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </template>
            <!-- table body end -->
        </div>
    </div>

    <!-- Pagination Controls -->
    <div class="border-t border-gray-100 py-4 pl-[18px] pr-4 dark:border-gray-800">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between">
            <p
                class="border-b border-gray-100 pb-3 text-center text-sm font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400 xl:border-b-0 xl:pb-0 xl:text-left">
                Showing <span x-text="startEntry"></span> to
                <span x-text="endEntry"></span> of
                <span x-text="totalEntries"></span> entries
            </p>

            <div class="flex items-center justify-center gap-0.5 pt-4 xl:justify-end xl:pt-0">
                <button @click="prevPage()"
                    class="mr-2.5 flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
                    :disabled="currentPage === 1">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M2.58301 9.99868C2.58272 10.1909 2.65588 10.3833 2.80249 10.53L7.79915 15.5301C8.09194 15.8231 8.56682 15.8233 8.85981 15.5305C9.15281 15.2377 9.15297 14.7629 8.86018 14.4699L5.14009 10.7472L16.6675 10.7472C17.0817 10.7472 17.4175 10.4114 17.4175 9.99715C17.4175 9.58294 17.0817 9.24715 16.6675 9.24715L5.14554 9.24715L8.86017 5.53016C9.15297 5.23717 9.15282 4.7623 8.85983 4.4695C8.56684 4.1767 8.09197 4.17685 7.79917 4.46984L2.84167 9.43049C2.68321 9.568 2.58301 9.77087 2.58301 9.99715C2.58301 9.99766 2.58301 9.99817 2.58301 9.99868Z"
                            fill="" />
                    </svg>
                </button>

                <button @click="goToPage(1)"
                    :class="currentPage === 1 ? 'bg-blue-500/8 text-brand-500' :
                        'text-gray-700 dark:text-gray-400'"
                    class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/8 hover:text-brand-500 dark:hover:text-brand-500">
                    1
                </button>

                <template x-if="currentPage > 3">
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-lg hover:bg-blue-500/8 hover:text-brand-500 dark:hover:text-brand-500">...</span>
                </template>

                <template x-for="page in pagesAroundCurrent" :key="page">
                    <button @click="goToPage(page)"
                        :class="currentPage === page ? 'bg-blue-500/8 text-brand-500' :
                            'text-gray-700 dark:text-gray-400'"
                        class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/8 hover:text-brand-500 dark:hover:text-brand-500">
                        <span x-text="page"></span>
                    </button>
                </template>

                <template x-if="currentPage < totalPages - 2">
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium text-gray-700 hover:bg-blue-500/8 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-500">...</span>
                </template>

                <button @click="goToPage(totalPages)"
                    :class="currentPage === totalPages ? 'bg-blue-500/8 text-brand-500' :
                        'text-gray-700 dark:text-gray-400'"
                    class="flex h-10 w-10 items-center justify-center rounded-lg text-sm font-medium hover:bg-blue-500/8 hover:text-brand-500 dark:hover:text-brand-500"
                    x-text="totalPages" x-show="totalPages > 1">
                </button>

                <button @click="nextPage()"
                    class="ml-2.5 flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3"
                    :disabled="currentPage === totalPages">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M17.4175 9.9986C17.4178 10.1909 17.3446 10.3832 17.198 10.53L12.2013 15.5301C11.9085 15.8231 11.4337 15.8233 11.1407 15.5305C10.8477 15.2377 10.8475 14.7629 11.1403 14.4699L14.8604 10.7472L3.33301 10.7472C2.91879 10.7472 2.58301 10.4114 2.58301 9.99715C2.58301 9.58294 2.91879 9.24715 3.33301 9.24715L14.8549 9.24715L11.1403 5.53016C10.8475 5.23717 10.8477 4.7623 11.1407 4.4695C11.4336 4.1767 11.9085 4.17685 12.2013 4.46984L17.1588 9.43049C17.3173 9.568 17.4175 9.77087 17.4175 9.99715C17.4175 9.99763 17.4175 9.99812 17.4175 9.9986Z"
                            fill="" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function dataTable(initialData) {
        return {
            search: "",
            sortColumn: "",
            sortDirection: "asc",
            currentPage: 1,
            perPage: 10,
            data: initialData,

            get pagesAroundCurrent() {
                let pages = [];
                const startPage = Math.max(2, this.currentPage - 2);
                const endPage = Math.min(this.totalPages - 1, this.currentPage + 2);

                for (let i = startPage; i <= endPage; i++) {
                    pages.push(i);
                }
                return pages;
            },

            get filteredData() {
                const searchLower = this.search.toLowerCase();
                return this.data
                    .filter(
                        (item) => {
                            return Object.values(item).some(val =>
                                String(val).toLowerCase().includes(searchLower)
                            );
                        }
                    )
                    .sort((a, b) => {
                        if (!this.sortColumn) return 0;
                        let modifier = this.sortDirection === "asc" ? 1 : -1;
                        if (a[this.sortColumn] < b[this.sortColumn]) return -1 * modifier;
                        if (a[this.sortColumn] > b[this.sortColumn]) return 1 * modifier;
                        return 0;
                    });
            },

            get paginatedData() {
                const start = (this.currentPage - 1) * this.perPage;
                const end = start + parseInt(this.perPage);
                return this.filteredData.slice(start, end);
            },

            get totalEntries() {
                return this.filteredData.length;
            },

            get startEntry() {
                if (this.totalEntries === 0) return 0;
                return (this.currentPage - 1) * this.perPage + 1;
            },

            get endEntry() {
                const end = this.currentPage * this.perPage;
                return end > this.totalEntries ? this.totalEntries : end;
            },

            get totalPages() {
                return Math.ceil(this.filteredData.length / this.perPage);
            },

            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                }
            },

            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                }
            },

            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                }
            },

            sortBy(column) {
                if (this.sortColumn === column) {
                    this.sortDirection = this.sortDirection === "asc" ? "desc" : "asc";
                } else {
                    this.sortDirection = "asc";
                    this.sortColumn = column;
                }
            },
        };
    }
</script>
