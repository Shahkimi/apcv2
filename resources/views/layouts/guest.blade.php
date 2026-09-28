<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (theme === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-background lg:grid lg:grid-cols-2">
        {{-- Brand panel --}}
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-500 px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between dark:from-indigo-500 dark:via-violet-500 dark:to-fuchsia-500">
            <div
                class="pointer-events-none absolute inset-0"
                style="background: radial-gradient(60% 50% at 20% 15%, rgba(255,255,255,0.16), transparent 70%);"
                aria-hidden="true"
            ></div>
            <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-10 h-56 w-56 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>

            <a href="/" class="relative flex items-center gap-2">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15 text-sm font-bold">
                    {{ mb_substr(config('app.name', 'A'), 0, 1) }}
                </span>
                <span class="text-lg font-semibold">{{ config('app.name', 'Laravel') }}</span>
            </a>

            <div class="relative max-w-sm">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ __('Selamat datang ke :app', ['app' => config('app.name', 'Laravel')]) }}
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-white/85">
                    {{ __('Urus kehadiran, giliran panggilan, dan paparan pentas majlis dalam satu tempat.') }}
                </p>

                <ul class="mt-6 space-y-3 text-sm">
                    @foreach ([
                        __('Kehadiran pantas'),
                        __('Giliran automatik'),
                        __('Paparan pentas masa nyata'),
                    ] as $point)
                        <li class="flex items-center gap-2">
                            <i class="ri-checkbox-circle-fill text-lg" aria-hidden="true"></i>
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-white/70">&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}</p>
        </div>

        {{-- Form panel --}}
        <div class="flex min-h-screen flex-col bg-gradient-to-b from-indigo-50/50 to-background px-4 py-6 sm:px-6 lg:min-h-0 lg:justify-center lg:px-12 lg:py-10 dark:from-indigo-950/20">
            <div class="mb-8 flex items-center justify-between lg:mb-10">
                <a href="/" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                    {{ __('Laman utama') }}
                </a>
                <x-theme-toggle />
            </div>

            <a href="/" class="mb-6 flex items-center gap-2 lg:hidden">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-500 text-sm font-bold text-white">
                    {{ mb_substr(config('app.name', 'A'), 0, 1) }}
                </span>
                <span class="text-lg font-semibold text-foreground">{{ config('app.name', 'Laravel') }}</span>
            </a>

            <div class="flex flex-1 items-center justify-center lg:flex-none">
                <div class="w-full max-w-md overflow-hidden rounded-2xl border border-indigo-500/10 bg-card shadow-xl shadow-indigo-500/5">
                    <div class="h-1 bg-gradient-to-r from-indigo-500 to-violet-500"></div>
                    <div class="p-6 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
