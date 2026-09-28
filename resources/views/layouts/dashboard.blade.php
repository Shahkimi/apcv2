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
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (theme === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
            if (localStorage.getItem('sidebar-collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>
</head>
<body class="h-[100dvh] max-h-[100dvh] overflow-hidden bg-background text-foreground">
    <div
        class="relative h-full min-h-0 w-full max-w-full overflow-hidden"
        x-data="{
            sidebarOpen: false,
            collapsed: document.documentElement.classList.contains('sidebar-collapsed'),
            toggleCollapsed() {
                this.collapsed = ! this.collapsed;
                document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
                try { localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
            },
        }"
        x-on:keydown.escape.window="sidebarOpen = false"
    >
        <x-dashboard-sidebar :role="$role" />

        <div
            id="sb-tooltip"
            role="tooltip"
            class="rounded-md border border-border bg-popover px-2 py-1 text-xs font-medium text-popover-foreground shadow-md"
        ></div>

        <div
            x-show="sidebarOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-background/80 backdrop-blur-sm lg:hidden"
            style="display: none;"
            x-on:click="sidebarOpen = false"
        ></div>

        {{-- Main column: offset for fixed sidebar on lg (width tracks collapse state via sb-main); only this column scrolls inside <main> --}}
        <div class="sb-main flex h-full min-h-0 min-w-0 flex-col overflow-hidden">
            <x-dashboard-navbar :title="$title" />

            <main
                @class([
                    'min-h-0 flex-1 overflow-x-hidden px-4 py-6 sm:px-6 lg:px-8',
                    'overflow-y-auto' => !$fillHeight,
                    'lg:flex lg:flex-col lg:overflow-y-hidden' => $fillHeight,
                ])
            >
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
    <script>
        (function () {
            var tip = document.getElementById('sb-tooltip');
            var aside = document.getElementById('app-sidebar');
            if (!tip || !aside) {
                return;
            }
            var desktop = window.matchMedia('(min-width: 1024px)');

            function show(el) {
                var text = el.getAttribute('data-tooltip');
                if (!text || !desktop.matches || !document.documentElement.classList.contains('sidebar-collapsed')) {
                    return;
                }
                var rect = el.getBoundingClientRect();
                tip.textContent = text;
                tip.style.left = (rect.right + 10) + 'px';
                tip.style.top = (rect.top + rect.height / 2) + 'px';
                tip.classList.add('is-visible');
            }

            function hide() {
                tip.classList.remove('is-visible');
            }

            aside.addEventListener('mouseover', function (e) {
                var el = e.target.closest('[data-tooltip]');
                el ? show(el) : hide();
            });
            aside.addEventListener('mouseleave', hide);
            aside.addEventListener('focusin', function (e) {
                var el = e.target.closest('[data-tooltip]');
                el ? show(el) : hide();
            });
            aside.addEventListener('focusout', hide);
            aside.addEventListener('click', hide);
            aside.querySelector('nav')?.addEventListener('scroll', hide, { passive: true });
        })();
    </script>
    @stack('scripts')
</body>
</html>
