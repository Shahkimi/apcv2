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
                    ['label' => __('Laporan'), 'route' => 'admin.report.index', 'icon' => 'ri-file-pdf-line'],
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
                            ['label' => __('PTJ'), 'route' => 'admin.kawalan.ptj.index', 'icon' => 'ri-building-line'],
                            ['label' => __('Jawatan'), 'route' => 'admin.kawalan.jawatan.index', 'icon' => 'ri-briefcase-line'],
                            ['label' => __('Gred'), 'route' => 'admin.kawalan.gred.index', 'icon' => 'ri-star-line'],
                            ['label' => __('Bersara'), 'route' => 'admin.kawalan.bersara.index', 'icon' => 'ri-user-heart-line'],
                            ['label' => __('Meja'), 'route' => 'admin.kawalan.meja.index', 'icon' => 'ri-table-line'],
                            ['label' => __('Sesi Majlis'), 'route' => 'admin.kawalan.sesi-majlis.index', 'icon' => 'ri-calendar-event-line'],
                            ['label' => __('Backdrop'), 'route' => 'admin.kawalan.backdrop.index', 'icon' => 'ri-image-2-line'],
                            ['label' => __('Pengguna'), 'route' => 'admin.kawalan.user.index', 'icon' => 'ri-user-line'],
                            ['label' => __('Import pangkalan'), 'route' => 'admin.kawalan.database.index', 'icon' => 'ri-database-2-line'],
                            ['label' => __('Sistem'), 'route' => 'admin.kawalan.system.index', 'icon' => 'ri-restart-line'],
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
                    ['label' => __('Layar Utama'), 'route' => 'media.senarai.index', 'icon' => 'ri-slideshow-3-line'],
                    ['label' => __('Kawalan Presentasi'), 'route' => 'media.kawalan.presentation.index', 'icon' => 'ri-settings-4-line'],
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
                ],
            ],
        ],
    };

    $itemClass = 'sb-item relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors '
        . 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring';
    $activeClass = 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold [&>i]:text-primary';
    $inactiveClass = 'text-sidebar-foreground/75 hover:bg-sidebar-accent/70 hover:text-sidebar-accent-foreground';

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
    aria-label="{{ __('Navigasi utama') }}"
    x-bind:aria-modal="sidebarOpen ? 'true' : null"
    class="sb-aside fixed inset-y-0 left-0 z-50 grid h-full max-h-[100dvh] -translate-x-full grid-rows-[auto_minmax(0,1fr)_auto] overflow-hidden border-r border-border bg-sidebar text-sidebar-foreground transition-transform duration-200 lg:z-40 lg:translate-x-0"
    x-bind:class="sidebarOpen ? 'translate-x-0' : ''"
>
    <div class="sb-header flex h-16 items-center gap-2 border-b border-sidebar-border px-4">
        <span class="sb-brand flex min-w-0 items-center gap-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground">
                {{ mb_substr(config('app.name', 'APC'), 0, 1) }}
            </span>
            <span class="truncate text-lg font-semibold text-sidebar-foreground">{{ config('app.name', 'APC') }}</span>
        </span>

        <button
            type="button"
            class="sb-collapse-btn ml-auto hidden shrink-0 rounded-lg p-2 text-sidebar-foreground hover:bg-sidebar-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring lg:inline-flex"
            x-bind:data-tooltip="collapsed ? @js(__('Besarkan bar sisi')) : null"
            x-on:click="toggleCollapsed()"
            x-bind:aria-pressed="collapsed ? 'true' : 'false'"
            aria-label="{{ __('Kecilkan / besarkan bar sisi') }}"
        >
            <i class="text-xl" x-bind:class="collapsed ? 'ri-menu-unfold-line' : 'ri-menu-fold-line'"></i>
        </button>

        <button
            type="button"
            class="ml-auto shrink-0 rounded-lg p-2 text-sidebar-foreground hover:bg-sidebar-accent lg:hidden"
            x-on:click="sidebarOpen = false"
            aria-label="{{ __('Tutup menu') }}"
        >
            <i class="ri-close-line text-xl"></i>
        </button>
    </div>

    <nav class="flex min-h-0 flex-col gap-1 overflow-y-auto overflow-x-hidden overscroll-y-contain p-3" aria-label="{{ __('Navigasi utama') }}">
        @foreach ($sections as $section)
            @if (!$loop->first)
                <hr class="sb-divider mx-1 my-2 hidden border-sidebar-border" />
            @endif

            <p class="sb-section-title px-3 pb-1 pt-2 text-[0.65rem] font-semibold uppercase tracking-wider text-sidebar-foreground/50">
                {{ $section['title'] }}
            </p>

            @foreach ($section['items'] as $item)
                @isset($item['children'])
                    @php $groupActive = request()->routeIs($item['routePattern']); @endphp
                    <div x-data="{ open: @json($groupActive) }" class="space-y-1">
                        <button
                            type="button"
                            class="{{ $itemClass }} w-full text-left {{ $groupActive ? $activeClass : $inactiveClass }}"
                            x-on:click="if (collapsed) { toggleCollapsed(); open = true; } else { open = ! open; }"
                            x-bind:aria-expanded="open ? 'true' : 'false'"
                            aria-controls="sb-group-{{ $loop->parent->index }}-{{ $loop->index }}"
                            aria-label="{{ $item['label'] }}"
                            data-tooltip="{{ $item['label'] }}"
                        >
                            <i class="{{ $item['icon'] }} text-lg shrink-0"></i>
                            <span class="sb-label truncate">{{ $item['label'] }}</span>
                            <i
                                class="sb-chevron ri-arrow-down-s-line ml-auto text-lg transition-transform"
                                x-bind:class="open ? 'rotate-180' : ''"
                            ></i>
                        </button>
                        <div
                            id="sb-group-{{ $loop->parent->index }}-{{ $loop->index }}"
                            x-show="open"
                            x-collapse
                            class="sb-children ml-3 space-y-1 border-l border-sidebar-border pl-2"
                        >
                            @foreach ($item['children'] as $child)
                                @php $childActive = request()->routeIs($child['route']); @endphp
                                <a
                                    href="{{ route($child['route']) }}"
                                    @if ($childActive) aria-current="page" @endif
                                    class="{{ $itemClass }} {{ $childActive ? $activeClass : $inactiveClass }}"
                                >
                                    <i class="{{ $child['icon'] }} text-lg shrink-0"></i>
                                    <span class="sb-label truncate">{{ $child['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @php $itemActive = request()->routeIs($item['route']); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        @if ($itemActive) aria-current="page" @endif
                        aria-label="{{ $item['label'] }}"
                        data-tooltip="{{ $item['label'] }}"
                        class="{{ $itemClass }} {{ $itemActive ? $activeClass : $inactiveClass }}"
                    >
                        <i class="{{ $item['icon'] }} text-lg shrink-0"></i>
                        <span class="sb-label truncate">{{ $item['label'] }}</span>
                    </a>
                @endisset
            @endforeach
        @endforeach
    </nav>

    <div class="border-t border-sidebar-border bg-sidebar p-3">
        @if ($role === 'admin')
            @php $statusText = ($activeSesi?->sesi ?? __('Tiada sesi aktif')).($modeLabel ? ' · '.$modeLabel : ''); @endphp
            <a
                href="{{ route('admin.kawalan.sesi-majlis.index') }}"
                aria-label="{{ $statusText }}"
                data-tooltip="{{ $statusText }}"
                class="sb-item mb-2 flex items-center gap-2 rounded-lg px-3 py-2 text-xs text-sidebar-foreground/70 hover:bg-sidebar-accent/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring"
            >
                <span class="h-2 w-2 shrink-0 rounded-full {{ $activeSesi ? 'animate-pulse bg-emerald-500' : 'bg-sidebar-foreground/30' }}" aria-hidden="true"></span>
                <span class="sb-status-text min-w-0 flex-1 truncate">
                    {{ $activeSesi?->sesi ?? __('Tiada sesi aktif') }}
                    @if ($modeLabel)
                        <span class="text-sidebar-foreground/50">· {{ $modeLabel }}</span>
                    @endif
                </span>
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
