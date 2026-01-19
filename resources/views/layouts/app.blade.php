<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>@yield('title', 'App')</title>
</head>

<body x-data="{
    page: '',
    loaded: true,
    darkMode: localStorage.getItem('darkMode') === null ?
        window.matchMedia('(prefers-color-scheme: dark)').matches : localStorage.getItem('darkMode') === 'true',
    stickyMenu: false,
    sidebarToggle: false,
    scrollTop: false
}" x-init="$watch('darkMode', value => {
    localStorage.setItem('darkMode', JSON.stringify(value));
    if (value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
})" class="bg-gray-50 dark:bg-gray-900">
    <div class="flex h-screen overflow-hidden">

        @include('layouts.partials.sidebar')

        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">

            @include('layouts.partials.overlay')
            @include('layouts.partials.header')
            <x-toast-container />

            <main>
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                    @yield('breadcrumb')

                    @yield('content')

                </div>
            </main>

        </div>
    </div>

    @stack('scripts')
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
