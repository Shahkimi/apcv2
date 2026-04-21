<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title !== '' ? $title.' — ' : '' }}{{ config('app.name', 'Laravel') }}</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="h-[100dvh] max-h-[100dvh] overflow-hidden bg-background text-foreground">
    <div
        class="relative h-full min-h-0 w-full max-w-full overflow-hidden"
        x-data="{ sidebarOpen: false }"
    >
        <x-dashboard-sidebar :role="$role" />

        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-background/80 backdrop-blur-sm lg:hidden"
            style="display: none;"
            x-on:click="sidebarOpen = false"
        ></div>

        {{-- Main column: offset for fixed sidebar on lg; only this column scrolls inside <main> --}}
        <div class="flex h-full min-h-0 min-w-0 flex-col overflow-hidden lg:ml-64">
            <x-dashboard-navbar :title="$title" />

            <main class="min-h-0 flex-1 overflow-x-hidden overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        (function () {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token || !window.jQuery) {
                return;
            }
            window.jQuery.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token.getAttribute('content'),
                },
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
