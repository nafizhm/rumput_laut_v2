<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>@yield('title', 'App')</title>
</head>

<body x-data="{
    page: '',
    loaded: true,
    darkMode: false,
    stickyMenu: false,
    sidebarToggle: false,
    scrollTop: false
}" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value =>
    localStorage.setItem('darkMode', JSON.stringify(value))
)" :class="{ 'dark bg-gray-900': darkMode }">
    <div class="flex h-screen overflow-hidden">

        @include('layouts.partials.sidebar')

        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">

            @include('layouts.partials.overlay')
            @include('layouts.partials.header')

            <main>
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">

                    @yield('breadcrumb')

                    @yield('content')

                </div>
            </main>

        </div>
    </div>

    @stack('scripts')
</body>

</html>
