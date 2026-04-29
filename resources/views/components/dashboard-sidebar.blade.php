@props(['role' => 'user'])

@php
    $links = match ($role) {
        'admin' => [
            ['label' => __('Dashboard'), 'route' => 'admin.dashboard', 'icon' => 'ri-dashboard-line'],
            ['label' => __('Kehadiran'), 'route' => 'admin.kehadiran.index', 'icon' => 'ri-user-follow-line'],
            ['label' => __('Paparan'), 'route' => 'admin.paparan.index', 'icon' => 'ri-tv-2-line'],
            ['label' => __('Analitik Senarai'), 'route' => 'admin.senarai.analytics', 'icon' => 'ri-bar-chart-line'],
            ['label' => __('Laporan'), 'route' => 'admin.report.index', 'icon' => 'ri-file-pdf-line'],
            [
                'label' => __('Kawalan'),
                'icon' => 'ri-settings-3-line',
                'children' => [
                    ['label' => __('PTJ'), 'route' => 'admin.kawalan.ptj.index', 'icon' => 'ri-building-line'],
                    ['label' => __('Jawatan'), 'route' => 'admin.kawalan.jawatan.index', 'icon' => 'ri-briefcase-line'],
                    ['label' => __('Gred'), 'route' => 'admin.kawalan.gred.index', 'icon' => 'ri-star-line'],
                    ['label' => __('Meja'), 'route' => 'admin.kawalan.meja.index', 'icon' => 'ri-table-line'],
                    ['label' => __('Sesi Majlis'), 'route' => 'admin.kawalan.sesi-majlis.index', 'icon' => 'ri-calendar-event-line'],
                    ['label' => __('Backdrop'), 'route' => 'admin.kawalan.backdrop.index', 'icon' => 'ri-image-2-line'],
                    ['label' => __('Pengguna'), 'route' => 'admin.kawalan.user.index', 'icon' => 'ri-user-line'],
                ],
            ],
        ],
        'media' => [
            ['label' => __('Dashboard'), 'route' => 'media.dashboard', 'icon' => 'ri-dashboard-line'],
            ['label' => __('Paparan'), 'route' => 'media.paparan.index', 'icon' => 'ri-tv-2-line'],
            ['label' => __('Senarai kehadiran'), 'route' => 'media.senarai.index', 'icon' => 'ri-list-check-2'],
            ['label' => __('Kawalan Presentasi'), 'route' => 'media.kawalan.presentation.index', 'icon' => 'ri-settings-4-line'],
            ['label' => __('Analitik Senarai'), 'route' => 'media.senarai.analytics', 'icon' => 'ri-bar-chart-line'],
        ],
        default => [
            ['label' => __('Dashboard'), 'route' => 'user.dashboard', 'icon' => 'ri-dashboard-line'],
        ],
    };
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 grid h-full max-h-[100dvh] w-64 -translate-x-full grid-rows-[auto_minmax(0,1fr)_auto] overflow-hidden border-r border-border bg-sidebar text-sidebar-foreground transition-transform duration-200 lg:z-40 lg:translate-x-0"
    x-bind:class="sidebarOpen ? 'translate-x-0' : ''"
>
    <div class="flex h-16 items-center gap-2 border-b border-sidebar-border px-4">
        <span class="text-lg font-semibold text-sidebar-foreground">{{ config('app.name', 'APC') }}</span>
        <button
            type="button"
            class="ml-auto rounded-lg p-2 text-sidebar-foreground hover:bg-sidebar-accent lg:hidden"
            x-on:click="sidebarOpen = false"
        >
            <i class="ri-close-line text-xl"></i>
        </button>
    </div>

    <nav class="flex min-h-0 flex-col gap-1 overflow-y-auto overscroll-y-contain p-3">
        @foreach ($links as $link)
            @isset($link['children'])
                <div
                    x-data="{ open: @json(request()->routeIs('admin.kawalan.*')) }"
                    class="space-y-1"
                >
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-sidebar-foreground/80 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground"
                        x-on:click="open = ! open"
                    >
                        <i class="{{ $link['icon'] }} text-lg"></i>
                        {{ $link['label'] }}
                        <i
                            class="ri-arrow-down-s-line ml-auto text-lg transition-transform"
                            x-bind:class="open ? 'rotate-180' : ''"
                        ></i>
                    </button>
                    <div x-show="open" x-collapse class="space-y-1 pl-2">
                        @foreach ($link['children'] as $child)
                            <a
                                href="{{ route($child['route']) }}"
                                @class([
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    'bg-sidebar-accent text-sidebar-accent-foreground' => request()->routeIs($child['route']),
                                    'text-sidebar-foreground/80 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground' => ! request()->routeIs($child['route']),
                                ])
                            >
                                <i class="{{ $child['icon'] }} text-lg"></i>
                                {{ $child['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a
                    href="{{ route($link['route']) }}"
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        'bg-sidebar-accent text-sidebar-accent-foreground' => request()->routeIs($link['route']),
                        'text-sidebar-foreground/80 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground' => ! request()->routeIs($link['route']),
                    ])
                >
                    <i class="{{ $link['icon'] }} text-lg"></i>
                    {{ $link['label'] }}
                </a>
            @endisset
        @endforeach

        <a
            href="{{ route('profile.edit') }}"
            @class([
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                'bg-sidebar-accent text-sidebar-accent-foreground' => request()->routeIs('profile.edit'),
                'text-sidebar-foreground/80 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground' => ! request()->routeIs('profile.edit'),
            ])
        >
            <i class="ri-user-settings-line text-lg"></i>
            {{ __('Profile') }}
        </a>
    </nav>

    <div class="border-t border-sidebar-border bg-sidebar p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-sidebar-foreground/80 hover:bg-sidebar-accent/80 hover:text-sidebar-accent-foreground"
            >
                <i class="ri-logout-box-r-line text-lg"></i>
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</aside>
