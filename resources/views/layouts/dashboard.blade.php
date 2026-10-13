<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
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
            isDesktop: window.matchMedia('(min-width: 1024px)').matches,
            collapsed: document.documentElement.classList.contains('sidebar-collapsed'),
            init() {
                const mq = window.matchMedia('(min-width: 1024px)');
                mq.addEventListener('change', (e) => {
                    this.isDesktop = e.matches;
                    if (e.matches) this.sidebarOpen = false;
                });
                this.$watch('sidebarOpen', (open) => {
                    if (this.isDesktop) return;
                    this.$nextTick(() => document.getElementById(open ? 'sb-close' : 'sb-toggle')?.focus());
                });
            },
            onKey(e) {
                if (e.key !== '[' || e.ctrlKey || e.metaKey || e.altKey || ! this.isDesktop) return;
                if (e.target.closest && e.target.closest('input, textarea, select, [contenteditable]')) return;
                this.toggleCollapsed();
            },
            toggleCollapsed() {
                this.collapsed = ! this.collapsed;
                document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
                try { localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0'); } catch (e) {}
            },
        }"
        x-on:keydown.escape.window="sidebarOpen = false"
        x-on:keydown.window="onKey($event)"
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
        <div class="sb-main flex h-full min-h-0 min-w-0 flex-col overflow-hidden" x-bind:inert="! isDesktop && sidebarOpen">
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

            var nav = aside.querySelector('nav');

            function fade() {
                if (!nav) {
                    return;
                }
                nav.toggleAttribute('data-fade-top', nav.scrollTop > 4);
                nav.toggleAttribute('data-fade-bottom', nav.scrollTop + nav.clientHeight < nav.scrollHeight - 4);
            }

            nav?.addEventListener('scroll', function () {
                hide();
                fade();
                window.dispatchEvent(new Event('sb-scroll'));
            }, { passive: true });
            nav?.addEventListener('click', function () { setTimeout(fade, 300); });
            window.addEventListener('resize', fade);
            window.addEventListener('load', function () {
                var active = nav?.querySelector('[aria-current="page"]');
                if (active) {
                    // Vertical only: scrollIntoView would also shift the overflow-x:hidden nav sideways
                    var a = active.getBoundingClientRect();
                    var n = nav.getBoundingClientRect();
                    if (a.bottom > n.bottom) {
                        nav.scrollTop += a.bottom - n.bottom + 16;
                    } else if (a.top < n.top) {
                        nav.scrollTop -= n.top - a.top + 16;
                    }
                }
                nav.scrollLeft = 0;
                fade();
            });
            fade();

            // Live session / event-mode badge (admin) without page refresh
            var status = document.getElementById('sb-status');
            if (status) {
                var busy = false;
                var refreshStatus = function () {
                    if (busy || document.hidden) {
                        return;
                    }
                    busy = true;
                    fetch(status.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store' })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (d) {
                            if (!d) {
                                return;
                            }
                            var name = d.sesi || status.dataset.empty;
                            var mode = d.mode || '';
                            var text = name + (mode ? ' · ' + mode : '');
                            var dot = document.getElementById('sb-status-dot');
                            var modeEl = document.getElementById('sb-status-mode');
                            document.getElementById('sb-status-sesi').textContent = name;
                            modeEl.textContent = mode;
                            modeEl.classList.toggle('hidden', !mode);
                            dot.classList.toggle('animate-pulse', !!d.sesi);
                            dot.classList.toggle('bg-emerald-500', !!d.sesi);
                            dot.classList.toggle('bg-sidebar-foreground/30', !d.sesi);
                            status.setAttribute('aria-label', text);
                            status.setAttribute('data-tooltip', text);
                        })
                        .catch(function () {})
                        .finally(function () { busy = false; });
                };
                setInterval(refreshStatus, 10000);
                document.addEventListener('visibilitychange', refreshStatus);
                window.refreshSidebarStatus = refreshStatus;
                // Refresh right after any session / event-mode change made through jQuery AJAX
                if (window.jQuery) {
                    window.jQuery(document).ajaxSuccess(function (e, xhr, settings) {
                        if (settings.type && settings.type.toUpperCase() !== 'GET' && /sesi-majlis/.test(settings.url || '')) {
                            refreshStatus();
                        }
                    });
                }
            }

            // Instant feedback while the next full page loads
            document.addEventListener('click', function (e) {
                var a = e.target.closest ? e.target.closest('a[data-sb-link]') : null;
                if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || a.target === '_blank') {
                    return;
                }
                a.classList.add('is-pending');
                a.setAttribute('aria-busy', 'true');
            });
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) {
                    return;
                }
                document.querySelectorAll('[data-sb-link].is-pending').forEach(function (el) {
                    el.classList.remove('is-pending');
                    el.removeAttribute('aria-busy');
                });
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
