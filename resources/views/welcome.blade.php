<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>@yield('title', 'App')</title>
</head>

<body x-data="{ page: 'maintenance', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))" :class="{ 'dark bg-gray-900': darkMode === true }">
    <x-toast-container />
    <!-- ===== Preloader Start ===== -->
    <include src="./partials/preloader.html"></include>
    <!-- ===== Preloader End ===== -->

    <!-- ===== Page Wrapper Start ===== -->
    <div class="relative flex flex-col items-center justify-center min-h-screen p-6 overflow-hidden z-1">
        <!-- ===== Common Grid Shape Start ===== -->
        <div class="absolute right-0 top-0 -z-1 w-full max-w-[250px] xl:max-w-[450px]">
            <img src="/assets/shape/grid-01.svg" alt="grid" />
        </div>
        <div class="absolute bottom-0 left-0 -z-1 w-full max-w-[250px] rotate-180 xl:max-w-[450px]">
            <img src="/assets/shape/grid-01.svg" alt="grid" />
        </div>

        <!-- ===== Common Grid Shape End ===== -->

        <div>
            <div class="mx-auto w-full max-w-[274px] text-center sm:max-w-[555px]">
                <div class="mx-auto mb-10 w-full max-w-[155px] text-center sm:max-w-[204px]">
                    <img src="/assets/error/maintenance.svg" alt="maintenance" class="dark:hidden" />
                    <img src="/assets/error/maintenance-dark.svg" alt="maintenance" class="hidden dark:block" />
                </div>

                <h1 class="mb-2 font-bold text-gray-800 text-title-md dark:text-white/90 xl:text-title-2xl">
                    UNDER DEVELOPMENT
                </h1>

                <p class="mt-6 mb-6 text-base text-gray-700 dark:text-gray-400 sm:text-lg">
                    This site is still under development.
                    We’re making improvements and will be live soon.
                    Thanks for your understanding!
                </p>

                <a href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                    Go To login page
                </a>
            </div>
            <!-- Footer -->
            <p class="absolute text-sm text-center text-gray-500 -translate-x-1/2 bottom-6 left-1/2 dark:text-gray-400">
                &copy; <span id="year"></span> - TailAdmin
            </p>
        </div>
    </div>
    <!-- ===== Page Wrapper End ===== -->
    <script>
        document.addEventListener('alpine:init', () => {
            @if (session('success'))
                window.toast.success("Success", "{{ session('success') }}");
            @endif
            @if (session('error'))
                window.toast.error("Error", "{{ session('error') }}");
            @endif
        });
    </script>
</body>

</html>
