@props(['title' => ''])

@php
    $initials = collect(explode(' ', trim(auth()->user()->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
@endphp

<header class="nav-fade-in z-30 flex h-16 w-full shrink-0 items-center gap-3 border-b border-border bg-card px-4 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-card/95 sm:px-6">
    <button
        type="button"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground lg:hidden"
        x-on:click="sidebarOpen = !sidebarOpen"
        aria-label="{{ __('Toggle menu') }}"
    >
        <i
            class="ri-menu-line absolute text-xl"
            x-show="!sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 rotate-90"
            x-transition:enter-end="opacity-100 rotate-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 rotate-0"
            x-transition:leave-end="opacity-0 -rotate-90"
        ></i>
        <i
            class="ri-close-line absolute text-xl"
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -rotate-90"
            x-transition:enter-end="opacity-100 rotate-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 rotate-0"
            x-transition:leave-end="opacity-0 rotate-90"
        ></i>
    </button>

    <div class="min-w-0 flex-1">
        <h1 class="truncate text-lg font-semibold text-foreground">{{ $title }}</h1>
    </div>

    @if (auth()->user()?->role === \App\Models\User::ROLE_MEDIA)
        <a
            href="{{ route('media.kawalan.presentation.index') }}"
            class="hidden items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-1.5 text-xs font-semibold text-foreground transition-all duration-150 hover:-translate-y-0.5 hover:bg-muted hover:shadow-sm active:translate-y-0 sm:inline-flex"
        >
            <i class="ri-settings-4-line"></i>
            {{ __('Kawalan Presentasi') }}
        </a>
    @endif

    <x-theme-toggle />

    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
        <button
            type="button"
            class="flex items-center gap-2 rounded-lg py-1 pl-1 pr-2 transition-colors hover:bg-muted"
            x-on:click="open = !open"
            :aria-expanded="open"
            aria-haspopup="true"
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
                {{ $initials }}
            </span>
            <span class="hidden flex-col items-start leading-tight sm:flex">
                <span class="max-w-[9rem] truncate text-sm font-medium text-foreground">{{ auth()->user()->name }}</span>
                <span class="max-w-[9rem] truncate text-xs text-muted-foreground">{{ auth()->user()->username }}</span>
            </span>
            <i
                class="ri-arrow-down-s-line hidden text-base text-muted-foreground transition-transform duration-200 sm:inline-block"
                :class="open ? 'rotate-180' : ''"
            ></i>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
            class="absolute right-0 z-50 mt-2 w-52 origin-top-right rounded-xl border border-border bg-popover p-1.5 text-popover-foreground shadow-lg"
            role="menu"
        >
            <div class="border-b border-border/70 px-2.5 py-2 sm:hidden">
                <p class="truncate text-sm font-medium text-foreground">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ auth()->user()->username }}</p>
            </div>

            <a
                href="{{ route('profile.edit') }}"
                class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-sm text-foreground transition-colors hover:bg-muted"
                role="menuitem"
            >
                <i class="ri-user-settings-line text-base text-muted-foreground"></i>
                {{ __('Profile') }}
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm text-foreground transition-colors hover:bg-muted"
                    role="menuitem"
                >
                    <i class="ri-logout-box-r-line text-base text-muted-foreground"></i>
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</header>
