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
                    <x-breadcrumb :pageTitle="'Data Tables'" :links="[['name' => 'Data Tables', 'url' => '#']]" />
                    <!-- Breadcrumb End -->

                    <div class="space-y-5 sm:space-y-6">
                        <x-data-table :rows="$data" :columns="$columns">
                            <x-slot:headerAction>
                                <button type="submit"
                                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-[11px] text-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto hover:bg-gray-50 dark:hover:bg-white/3 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round"
                                        class="lucide lucide-plus-icon lucide-plus">
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>
                                    Tambah User
                                </button>
                            </x-slot:headerAction>

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
            </main>
            <!-- ===== Main Content End ===== -->
        </div>
        <!-- ===== Content Area End ===== -->
    </div>
</body>

</html>
