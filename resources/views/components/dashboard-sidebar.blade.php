@props(['role' => 'user'])

@php
    $sections = match ($role) {
        'admin' => [
            [
                'title' => __('Utama'),
                'items' => [
                    ['label' => __('Dashboard'), 'route' => 'admin.dashboard', 'icon' => 'ri-dashboard-line'],
                    ['label' => __('Kehadiran'), 'route' => 'admin.kehadiran.index', 'icon' => 'ri-user-follow-line'],
                ],
            ],
            [
                'title' => __('Pemantauan'),
                'items' => [
                    ['label' => __('Debug paparan'), 'route' => 'admin.paparan.index', 'icon' => 'ri-bug-line'],
                    ['label' => __('Analitik Senarai'), 'route' => 'admin.senarai.analytics', 'icon' => 'ri-bar-chart-line'],
                    ['label' => __('Laporan'), 'route' => 'admin.report.index', 'icon' => 'ri-file-pdf-line', 'active' => 'admin.report.*'],
                ],
            ],
            [
                'title' => __('Kawalan'),
                'items' => [
                    [
                        'label' => __('Kawalan'),
                        'icon' => 'ri-settings-3-line',
                        'routePattern' => 'admin.kawalan.*',
                        'children' => [
                            ['label' => __('PTJ'), 'route' => 'admin.kawalan.ptj.index', 'icon' => 'ri-building-line', 'active' => 'admin.kawalan.ptj.*'],
                            ['label' => __('Jawatan'), 'route' => 'admin.kawalan.jawatan.index', 'icon' => 'ri-briefcase-line', 'active' => 'admin.kawalan.jawatan.*'],
                            ['label' => __('Gred'), 'route' => 'admin.kawalan.gred.index', 'icon' => 'ri-star-line', 'active' => 'admin.kawalan.gred.*'],
                            ['label' => __('Bersara'), 'route' => 'admin.kawalan.bersara.index', 'icon' => 'ri-user-heart-line', 'active' => 'admin.kawalan.bersara.*'],
                            ['label' => __('Meja'), 'route' => 'admin.kawalan.meja.index', 'icon' => 'ri-table-line', 'active' => 'admin.kawalan.meja.*'],
                            ['label' => __('Sesi Majlis'), 'route' => 'admin.kawalan.sesi-majlis.index', 'icon' => 'ri-calendar-event-line', 'active' => 'admin.kawalan.sesi-majlis.*'],
                            ['label' => __('Backdrop'), 'route' => 'admin.kawalan.backdrop.index', 'icon' => 'ri-image-2-line', 'active' => 'admin.kawalan.backdrop.*'],
                            ['label' => __('Pengguna'), 'route' => 'admin.kawalan.user.index', 'icon' => 'ri-user-line', 'active' => 'admin.kawalan.user.*'],
                            ['label' => __('Import pangkalan'), 'route' => 'admin.kawalan.database.index', 'icon' => 'ri-database-2-line', 'active' => 'admin.kawalan.database.*'],
                            ['label' => __('Sistem'), 'route' => 'admin.kawalan.system.index', 'icon' => 'ri-restart-line', 'active' => 'admin.kawalan.system.*'],
                        ],
                    ],
                ],
            ],
        ],
        'media' => [
            [
                'title' => __('Utama'),
                'items' => [
                    ['label' => __('Dashboard'), 'route' => 'media.dashboard', 'icon' => 'ri-dashboard-line'],
                ],
            ],
            [
                'title' => __('Presentasi'),
                'items' => [
                    ['label' => __('Layar Utama'), 'route' => 'media.senarai.index', 'icon' => 'ri-slideshow-3-line', 'active' => ['media.senarai.index', 'media.senarai.present', 'media.senarai.progress.*']],
                    ['label' => __('Kawalan Presentasi'), 'route' => 'media.kawalan.presentation.index', 'icon' => 'ri-settings-4-line', 'active' => 'media.kawalan.presentation.*'],
                ],
            ],
            [
                'title' => __('Pemantauan'),
                'items' => [
                    ['label' => __('Debug paparan'), 'route' => 'media.paparan.index', 'icon' => 'ri-bug-line'],
                    ['label' => __('Analitik Senarai'), 'route' => 'media.senarai.analytics', 'icon' => 'ri-bar-chart-line'],
                ],
            ],
        ],
        default => [
            [
                'title' => __('Utama'),
                'items' => [
                    ['label' => __('Dashboard'), 'route' => 'user.dashboard', 'icon' => 'ri-dashboard-line'],
                    ['label' => __('Kehadiran'), 'route' => 'user.kehadiran.index', 'icon' => 'ri-user-follow-line'],
                    ['label' => __('Kiosk'), 'route' => 'user.kiosk.index', 'icon' => 'ri-keyboard-box-line'],
                ],
            ],
        ],
    };

    $itemClass = 'sb-item relative flex items-center gap-3 rounded-lg px-3.5 py-2 text-sm font-medium transition-colors '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring';
    $activeClass = 'bg-primary/10 text-primary font-semibold';
    $inactiveClass = 'text-sidebar-foreground/75 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground';

    $isActive = fn (array $item): bool => request()->routeIs(...(array) ($item['active'] ?? $item['route']));
    $homeRoute = $sections[0]['items'][0]['route'];

    $authUser = auth()->user();
    $initials = $authUser
        ? collect(explode(' ', trim($authUser->name)))->filter()->take(2)->map(fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('')
        : '';
    $roleLabel = match ($authUser?->role) {
        \App\Models\User::ROLE_ADMIN => __('Admin'),
        \App\Models\User::ROLE_MEDIA => __('Media'),
        default => __('Pengguna'),
    };

    $modeLabel = null;
    $activeSesi = null;
    if ($role === 'admin') {
        $modeLabel = \App\Services\EventModeService::options()[$eventMode ?? ''] ?? null;
        $activeSesi = app(\App\Services\Kehadiran\KehadiranCallingService::class)->activeOnAirSesi();
    }
@endphp

<aside
    id="app-sidebar"
    aria-label="{{ __('Bar sisi') }}"
    x-bind:inert="! isDesktop && ! sidebarOpen"
    class="sb-aside fixed inset-y-0 left-0 z-50 grid h-full max-h-[100dvh] -translate-x-full grid-cols-[minmax(0,1fr)] grid-rows-[auto_minmax(0,1fr)_auto] overflow-hidden border-r border-border bg-sidebar text-sidebar-foreground transition-transform duration-200 lg:z-40 lg:translate-x-0"
    x-bind:class="sidebarOpen ? 'translate-x-0' : ''"
>
    <div class="sb-header flex h-16 items-center gap-2 border-b border-sidebar-border px-4">
        <a
            href="{{ route($homeRoute) }}"
            class="sb-brand group flex min-w-0 items-center gap-2 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring"
            aria-label="{{ config('app.name', 'APC') }}"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground transition-transform duration-200 group-hover:scale-105">
                {{ mb_substr(config('app.name', 'APC'), 0, 1) }}
            </span>
            <span class="truncate text-lg font-semibold text-sidebar-foreground">{{ config('app.name', 'APC') }}</span>
        </a>

        <button
            type="button"
            class="sb-collapse-btn ml-auto hidden shrink-0 rounded-lg p-2 text-sidebar-foreground hover:bg-sidebar-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring lg:inline-flex"
            x-bind:data-tooltip="collapsed ? @js(__('Besarkan bar sisi').' ([)') : null"
            x-on:click="toggleCollapsed()"
            x-bind:aria-pressed="collapsed ? 'true' : 'false'"
            aria-keyshortcuts="["
            aria-label="{{ __('Kecilkan / besarkan bar sisi') }}"
        >
            <i class="text-xl" x-bind:class="collapsed ? 'ri-menu-unfold-line' : 'ri-menu-fold-line'"></i>
        </button>

        <button
            type="button"
            id="sb-close"
            class="ml-auto shrink-0 rounded-lg p-2 text-sidebar-foreground hover:bg-sidebar-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring lg:hidden"
            x-on:click="sidebarOpen = false"
            aria-label="{{ __('Tutup menu') }}"
        >
            <i class="ri-close-line text-xl"></i>
        </button>
    </div>

    <nav class="sb-nav flex min-h-0 flex-col gap-1 overflow-y-auto overflow-x-hidden overscroll-y-contain p-3" aria-label="{{ __('Navigasi utama') }}">
        @foreach ($sections as $section)
            @if (!$loop->first)
                <hr class="sb-divider mx-1 my-2 hidden border-sidebar-border" />
            @endif

            <p class="sb-section-title px-3.5 pb-1 pt-3 text-[0.65rem] font-semibold uppercase tracking-wider text-sidebar-foreground/50">
                {{ $section['title'] }}
            </p>

            @foreach ($section['items'] as $item)
                @isset($item['children'])
                    @php
                        $groupActive = request()->routeIs($item['routePattern']);
                        $gid = 'sb-group-'.$loop->parent->index.'-'.$loop->index;
                    @endphp
                    <div
                        x-data="{
                            gid: @js($gid),
                            key: @js('sb-group:'.$item['routePattern']),
                            open: @js($groupActive),
                            flyout: false,
                            left: 0,
                            top: 0,
                            timer: null,
                            init() {
                                if (! this.open) {
                                    try { this.open = localStorage.getItem(this.key) === '1'; } catch (e) {}
                                }
                            },
                            toggle(rail, keyboard) {
                                if (rail) { this.flyout ? this.closeFly(false) : this.openFly(keyboard); return; }
                                this.open = ! this.open;
                                try { localStorage.setItem(this.key, this.open ? '1' : '0'); } catch (e) {}
                            },
                            openFly(focusFirst) {
                                clearTimeout(this.timer);
                                const r = document.getElementById(this.gid + '-btn').getBoundingClientRect();
                                this.left = document.getElementById('app-sidebar').getBoundingClientRect().right + 8;
                                this.top = r.top;
                                this.flyout = true;
                                document.getElementById('sb-tooltip')?.classList.remove('is-visible');
                                this.$nextTick(() => {
                                    const f = document.getElementById(this.gid + '-fly');
                                    if (! f) return;
                                    this.top = Math.max(8, Math.min(r.top, window.innerHeight - f.offsetHeight - 8));
                                    if (focusFirst) f.querySelector('a')?.focus();
                                });
                            },
                            closeFly(refocus) {
                                clearTimeout(this.timer);
                                this.flyout = false;
                                if (refocus) document.getElementById(this.gid + '-btn')?.focus();
                            },
                            hoverOpen(rail) {
                                if (! rail || this.flyout) return;
                                clearTimeout(this.timer);
                                this.timer = setTimeout(() => this.openFly(false), 100);
                            },
                            hoverClose() {
                                clearTimeout(this.timer);
                                this.timer = setTimeout(() => this.closeFly(false), 200);
                            },
                            outside(e) {
                                if (! document.getElementById(this.gid + '-btn').contains(e.target)) this.closeFly(false);
                            },
                        }"
                        x-effect="if (! (collapsed && isDesktop)) flyout = false"
                        x-on:sb-scroll.window="closeFly(false)"
                        class="space-y-1"
                    >
                        <button
                            type="button"
                            id="{{ $gid }}-btn"
                            class="{{ $itemClass }} w-full text-left {{ $groupActive ? $activeClass.' is-group-active' : $inactiveClass }}"
                            x-on:click="toggle(collapsed && isDesktop, $event.detail === 0)"
                            x-on:keydown.arrow-right="if (collapsed && isDesktop) { $event.preventDefault(); openFly(true); }"
                            x-on:mouseenter="hoverOpen(collapsed && isDesktop)"
                            x-on:mouseleave="hoverClose()"
                            x-bind:aria-expanded="(collapsed && isDesktop ? flyout : open) ? 'true' : 'false'"
                            x-bind:aria-haspopup="collapsed && isDesktop ? 'true' : null"
                            aria-controls="{{ $gid }}"
                            aria-label="{{ $item['label'] }}"
                            x-bind:data-tooltip="flyout ? null : @js($item['label'])"
                        >
                            <i class="{{ $item['icon'] }} text-lg shrink-0"></i>
                            <span class="sb-label truncate">{{ $item['label'] }}</span>
                            <i
                                class="sb-chevron ri-arrow-down-s-line ml-auto text-lg transition-transform"
                                x-bind:class="open ? 'rotate-180' : ''"
                            ></i>
                        </button>
                        <div
                            id="{{ $gid }}"
                            x-show="open"
                            x-cloak
                            x-collapse
                            class="sb-children ml-3 space-y-1 border-l border-sidebar-border pl-2"
                        >
                            @foreach ($item['children'] as $child)
                                <x-sidebar-link
                                    :href="route($child['route'])"
                                    :icon="$child['icon']"
                                    :label="$child['label']"
                                    :active="$isActive($child)"
                                    :tooltip="false"
                                />
                            @endforeach
                        </div>

                        <template x-teleport="body">
                            <div
                                id="{{ $gid }}-fly"
                                x-show="flyout"
                                x-cloak
                                x-transition.opacity.duration.100ms
                                x-bind:style="`left:${left}px;top:${top}px`"
                                x-on:keydown.escape.stop="closeFly(true)"
                                x-on:click.outside="outside($event)"
                                x-on:mouseenter="clearTimeout(timer)"
                                x-on:mouseleave="hoverClose()"
                                role="group"
                                aria-label="{{ $item['label'] }}"
                                class="fixed z-[80] max-h-[calc(100dvh-1rem)] w-56 space-y-0.5 overflow-y-auto rounded-xl border border-border bg-popover p-1.5 text-popover-foreground shadow-lg"
                            >
                                <p class="px-3.5 pb-1 pt-1.5 text-[0.65rem] font-semibold uppercase tracking-wider text-muted-foreground">
                                    {{ $item['label'] }}
                                </p>
                                @foreach ($item['children'] as $child)
                                    <x-sidebar-link
                                        :href="route($child['route'])"
                                        :icon="$child['icon']"
                                        :label="$child['label']"
                                        :active="$isActive($child)"
                                        :tooltip="false"
                                    />
                                @endforeach
                            </div>
                        </template>
                    </div>
                @else
                    <x-sidebar-link
                        :href="route($item['route'])"
                        :icon="$item['icon']"
                        :label="$item['label']"
                        :active="$isActive($item)"
                    />
                @endisset
            @endforeach
        @endforeach
    </nav>

    <div class="sb-footer border-t border-sidebar-border bg-sidebar p-3">
        @if ($role === 'admin')
            @php $statusText = ($activeSesi?->sesi ?? __('Tiada sesi aktif')).($modeLabel ? ' · '.$modeLabel : ''); @endphp
            <a
                id="sb-status"
                href="{{ route('admin.kawalan.sesi-majlis.index') }}"
                data-url="{{ route('admin.sidebar-status') }}"
                data-empty="{{ __('Tiada sesi aktif') }}"
                aria-label="{{ $statusText }}"
                data-tooltip="{{ $statusText }}"
                class="sb-item mb-2 flex flex-col items-center justify-center rounded-lg px-3 py-2 text-center text-xs leading-snug text-sidebar-foreground/70 hover:bg-sidebar-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring"
            >
                <span class="flex max-w-full items-center justify-center gap-1.5">
                    <span id="sb-status-dot" class="h-2 w-2 shrink-0 rounded-full {{ $activeSesi ? 'animate-pulse bg-emerald-500' : 'bg-sidebar-foreground/30' }}" aria-hidden="true"></span>
                    <span id="sb-status-sesi" class="sb-status-text min-w-0 truncate text-sm font-semibold text-sidebar-foreground">{{ $activeSesi?->sesi ?? __('Tiada sesi aktif') }}</span>
                </span>
                <span id="sb-status-mode" @class(['sb-status-text text-[0.65rem] text-sidebar-foreground/60', 'hidden' => ! $modeLabel])>{{ $modeLabel }}</span>
            </a>
        @endif

        <div class="sb-footer-row flex items-center gap-2">
            <a
                href="{{ route('profile.edit') }}"
                @if (request()->routeIs('profile.edit')) aria-current="page" @endif
                aria-label="{{ __('Profil') }}: {{ $authUser?->name }}"
                data-tooltip="{{ $authUser?->name }} · {{ $roleLabel }}"
                class="{{ $itemClass }} min-w-0 flex-1 {{ request()->routeIs('profile.edit') ? $activeClass : $inactiveClass }}"
            >
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                    {{ $initials }}
                </span>
                <span class="sb-user-text min-w-0 flex-1 leading-tight">
                    <span class="block truncate text-sm font-medium">{{ $authUser?->name }}</span>
                    <span class="block truncate text-xs text-sidebar-foreground/60">{{ $roleLabel }}</span>
                </span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button
                    type="submit"
                    class="flex h-10 w-10 items-center justify-center rounded-lg transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring {{ $inactiveClass }}"
                    aria-label="{{ __('Log keluar') }}"
                    data-tooltip="{{ __('Log keluar') }}"
                >
                    <i class="ri-logout-box-r-line text-lg"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
