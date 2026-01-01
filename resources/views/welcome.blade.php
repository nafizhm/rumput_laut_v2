<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Document</title>
</head>

<body x-data="{ page: 'dataTables', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{ 'dark bg-gray-900': darkMode === true }">
    <div class="flex h-screen overflow-hidden">
        <!-- ===== Sidebar Start ===== -->
        @include('layouts.partials.sidebar')
        <!-- ===== Sidebar End ===== -->

        <!-- ===== Content Area Start ===== -->
        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
            <!-- Small Device Overlay Start -->
            @include('layouts.partials.overlay')
            <!-- Small Device Overlay End -->

            <!-- ===== Header Start ===== -->
            @include('layouts.partials.header')
            <!-- ===== Header End ===== -->

            <!-- ===== Main Content Start ===== -->
            <main>
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                    <!-- Breadcrumb Start -->
                    <div x-data="{ pageName: `Data Tables` }">
                        <include src="./partials/breadcrumb.html" />
                    </div>
                    <!-- Breadcrumb End -->

                    <div class="space-y-5 sm:space-y-6">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="px-5 py-4 sm:px-6 sm:py-5">
                                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                                    Data User
                                </h3>
                            </div>
                            <div class="border-t border-gray-100 p-5 dark:border-gray-800 sm:p-6">
                                <!-- ====== DataTable One Start -->
                                <x-data-table :rows="$data" :columns="$columns">
                                    <x-slot:status>
                                        <div :class="{
                                            'bg-green-500/10 text-green-500': item.status === 'Active',
                                            'bg-red-500/10 text-red-500': item.status === 'Inactive'
                                        }"
                                            class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium">
                                            <span x-text="item.status"></span>
                                        </div>
                                    </x-slot:status>

                                    <x-slot:action>
                                        <div class="flex items-center gap-2">
                                            <button type="submit"
                                                class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-brand-500 hover:bg-brand-600">
                                                Edit
                                            </button>
                                            <button type="submit"
                                                class="flex items-center w-full justify-center gap-2 py-2 px-4 text-sm font-medium text-white transition-colors rounded-lg bg-rose-500 hover:bg-rose-600">
                                                Delete
                                            </button>
                                        </div>
                                    </x-slot:action>
                                </x-data-table>
                                <!-- ====== DataTable One End -->
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <!-- ===== Main Content End ===== -->
        </div>
        <!-- ===== Content Area End ===== -->
    </div>
</body>

</html>
