@props(['title' => ''])

<header class="z-30 flex h-16 w-full shrink-0 items-center gap-3 border-b border-border bg-card px-4 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-card/95 sm:px-6">
    <button
        type="button"
        class="inline-flex rounded-lg p-2 text-muted-foreground hover:bg-muted hover:text-foreground lg:hidden"
        x-on:click="sidebarOpen = true"
        aria-label="{{ __('Open menu') }}"
    >
        <i class="ri-menu-line text-xl"></i>
    </button>

    <div class="min-w-0 flex-1">
        <h1 class="truncate text-lg font-semibold text-foreground">{{ $title }}</h1>
    </div>

    @if (auth()->user()?->role === \App\Models\User::ROLE_MEDIA)
        <a
            href="{{ route('media.kawalan.presentation.index') }}"
            class="hidden items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-xs font-semibold text-foreground transition hover:bg-muted sm:inline-flex"
        >
            <i class="ri-settings-4-line"></i>
            {{ __('Kawalan Presentasi') }}
        </a>
    @endif

    <x-theme-toggle />

    <div class="hidden items-center gap-2 sm:flex">
        <span class="truncate text-sm text-muted-foreground">{{ auth()->user()->name }}</span>
        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
            {{ auth()->user()->username }}
        </span>
    </div>
</header>
