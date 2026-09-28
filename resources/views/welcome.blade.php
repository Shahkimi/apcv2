<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
<body class="home-page bg-background text-foreground antialiased">
    {{-- Header --}}
    <header data-home-header class="sticky top-0 z-30 border-b border-border/60 bg-background/80 backdrop-blur transition-shadow duration-300">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="/" class="flex min-w-0 items-center gap-2">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-500 text-sm font-bold text-white dark:from-indigo-500 dark:via-violet-500 dark:to-fuchsia-500">
                    {{ mb_substr(config('app.name', 'A'), 0, 1) }}
                </span>
                <span class="truncate text-lg font-semibold text-foreground">{{ config('app.name', 'Laravel') }}</span>
            </a>

            <div class="flex items-center gap-2">
                <x-theme-toggle />
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/25 hover:opacity-95">
                        {{ __('Ke papan pemuka') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/25 hover:opacity-95">
                        {{ __('Log masuk') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="home-grid pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="home-blob absolute -left-24 -top-24 h-96 w-96 rounded-full bg-indigo-400/30 blur-3xl dark:bg-indigo-500/20"></div>
            <div class="home-blob-2 absolute -right-24 top-10 h-96 w-96 rounded-full bg-fuchsia-400/20 blur-3xl dark:bg-fuchsia-500/10"></div>
        </div>

        <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-24">
            <div>
                <span class="reveal inline-flex items-center gap-1.5 rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-700 ring-1 ring-indigo-500/20 dark:text-indigo-300" style="--d: 0ms">
                    <i class="ri-sparkling-2-line text-sm" aria-hidden="true"></i>
                    {{ __('Sistem pengurusan majlis') }}
                </span>
                <h1 class="reveal mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl lg:text-5xl" style="--d: 100ms">
                    {{ __('Kehadiran &') }}
                    <span class="home-gradient-animate bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-600 bg-clip-text text-transparent dark:from-indigo-400 dark:via-violet-400 dark:to-indigo-400">{{ __('giliran panggilan majlis') }}</span>,
                    {{ __('tersusun dari pintu masuk ke pentas.') }}
                </h1>
                <p class="reveal mt-4 max-w-xl text-base leading-relaxed text-muted-foreground" style="--d: 200ms">
                    {{ __('Sahkan kehadiran pegawai, urus giliran panggilan, dan papar pengumuman di pentas — semuanya dikemas kini secara masa nyata dalam satu sistem.') }}
                </p>
                <div class="reveal mt-8 flex flex-wrap items-center gap-3" style="--d: 300ms">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="home-shine btn btn-lg relative overflow-hidden bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/25 hover:opacity-95">
                            {{ __('Ke papan pemuka') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="home-shine btn btn-lg relative overflow-hidden bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/25 hover:opacity-95">
                            {{ __('Log masuk') }}
                        </a>
                    @endauth
                    <a href="#ciri" class="btn btn-outline btn-lg">
                        {{ __('Lihat ciri') }}
                    </a>
                </div>
            </div>

            {{-- Decorative mock presentation card (static, no live data) --}}
            <div class="reveal home-float relative mx-auto w-full max-w-md motion-reduce:transform-none" style="--d: 150ms">
                <div class="home-orbit" aria-hidden="true"></div>

                <span class="home-float-delay absolute -right-4 -top-4 z-10 inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-md dark:text-emerald-300">
                    <i class="ri-checkbox-circle-fill text-emerald-500"></i>
                    {{ __('Hadir') }} <span data-count-to="342">342</span>
                </span>

                <div class="home-tilt relative overflow-hidden rounded-2xl border border-border bg-card shadow-lg" data-tilt>
                    <div class="home-glare" aria-hidden="true"></div>
                    <div class="home-gradient-animate h-1.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                <span class="home-pulse-dot h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                {{ __('Sesi pagi') }}
                            </span>
                            <span class="text-xs font-medium text-muted-foreground">{{ __('Giliran seterusnya') }}</span>
                        </div>

                        <div class="mt-6 text-center">
                            <p class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-5xl font-bold tabular-nums tracking-tight text-transparent dark:from-indigo-400 dark:to-violet-400">
                                <span data-count-to="128">128</span>
                            </p>
                            <div class="mx-auto mt-4 h-3 w-40 rounded-full bg-muted"></div>
                            <div class="mx-auto mt-2 h-2 w-24 rounded-full bg-muted"></div>
                        </div>

                        <div class="mt-6 rounded-xl border border-border/60 bg-muted/30 p-4">
                            <div class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>{{ __('Kemajuan') }}</span>
                                <span class="font-semibold tabular-nums text-foreground">72%</span>
                            </div>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-background">
                                <div class="home-progress h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500" style="--to: 72%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Mod acara --}}
    <section class="mx-auto max-w-6xl px-4 pb-4 sm:px-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="reveal flex items-start gap-4 rounded-2xl border border-amber-500/20 bg-gradient-to-br from-amber-500/5 to-transparent p-5 shadow-sm" style="--d: 0ms">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 ring-1 ring-amber-500/20 dark:text-amber-400" aria-hidden="true">
                    <i class="ri-award-line text-xl leading-none"></i>
                </span>
                <div class="min-w-0">
                    <p class="font-semibold text-foreground">{{ __('Anugerah Pekerja Cemerlang (APC)') }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ __('Panggilan mengikut no. kerusi dengan sokongan giliran lewat.') }}</p>
                </div>
            </div>
            <div class="reveal flex items-start gap-4 rounded-2xl border border-rose-500/20 bg-gradient-to-br from-rose-500/5 to-transparent p-5 shadow-sm" style="--d: 100ms">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-600 ring-1 ring-rose-500/20 dark:text-rose-400" aria-hidden="true">
                    <i class="ri-hand-heart-line text-xl leading-none"></i>
                </span>
                <div class="min-w-0">
                    <p class="font-semibold text-foreground">{{ __('Jasamu Dikenang') }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ __('Mod acara khusus untuk penghargaan perkhidmatan dan persaraan.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Ciri --}}
    <section id="ciri" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="reveal mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ __('Ciri utama') }}</h2>
            <p class="mt-2 text-sm text-muted-foreground">{{ __('Direka untuk pasukan kawalan, media, dan pegawai yang hadir.') }}</p>
        </div>

        @php
            $tints = [
                'emerald' => ['icon' => 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 dark:text-emerald-400', 'hover' => 'hover:border-emerald-500/30'],
                'indigo' => ['icon' => 'bg-indigo-500/10 text-indigo-600 ring-indigo-500/20 dark:text-indigo-400', 'hover' => 'hover:border-indigo-500/30'],
                'amber' => ['icon' => 'bg-amber-500/10 text-amber-600 ring-amber-500/20 dark:text-amber-400', 'hover' => 'hover:border-amber-500/30'],
                'violet' => ['icon' => 'bg-violet-500/10 text-violet-600 ring-violet-500/20 dark:text-violet-400', 'hover' => 'hover:border-violet-500/30'],
                'sky' => ['icon' => 'bg-sky-500/10 text-sky-600 ring-sky-500/20 dark:text-sky-400', 'hover' => 'hover:border-sky-500/30'],
                'rose' => ['icon' => 'bg-rose-500/10 text-rose-600 ring-rose-500/20 dark:text-rose-400', 'hover' => 'hover:border-rose-500/30'],
            ];
        @endphp
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['icon' => 'ri-user-follow-line', 'color' => 'emerald', 'title' => __('Pengesahan kehadiran'), 'desc' => __('Sahkan kehadiran pegawai terus daripada jadual, dengan carian pantas.')],
                ['icon' => 'ri-sort-number-asc', 'color' => 'indigo', 'title' => __('Giliran & no. kerusi'), 'desc' => __('Susunan panggilan automatik mengikut no. kerusi atau giliran lewat.')],
                ['icon' => 'ri-alarm-warning-line', 'color' => 'amber', 'title' => __('Sesi lewat'), 'desc' => __('Pegawai yang hadir lewat diberi giliran panggilan mengikut ketibaan.')],
                ['icon' => 'ri-slideshow-line', 'color' => 'violet', 'title' => __('Layar pembentangan'), 'desc' => __('Paparan pentas masa nyata untuk nama dan giliran semasa.')],
                ['icon' => 'ri-line-chart-line', 'color' => 'sky', 'title' => __('Analitik masa nyata'), 'desc' => __('Pantau kemajuan pengumuman dan kedudukan semasa acara.')],
                ['icon' => 'ri-file-chart-line', 'color' => 'rose', 'title' => __('Laporan'), 'desc' => __('Jana laporan kehadiran lengkap untuk semakan pasca-acara.')],
            ] as $feature)
                <div class="home-spotlight reveal overflow-hidden rounded-2xl border border-border bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md motion-reduce:transform-none {{ $tints[$feature['color']]['hover'] }}" style="--d: {{ $loop->index * 90 }}ms">
                    <span class="relative flex h-11 w-11 items-center justify-center rounded-xl ring-1 {{ $tints[$feature['color']]['icon'] }}" aria-hidden="true">
                        <i class="{{ $feature['icon'] }} text-xl leading-none"></i>
                    </span>
                    <p class="relative mt-4 font-semibold text-foreground">{{ $feature['title'] }}</p>
                    <p class="relative mt-1 text-sm leading-relaxed text-muted-foreground">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Aliran kerja --}}
    <section class="border-y border-border/60 bg-gradient-to-b from-indigo-50/60 to-transparent dark:from-indigo-950/30">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="reveal text-center text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ __('Aliran kerja') }}</h2>

            <div class="relative mt-10 grid gap-8 md:grid-cols-3">
                <div class="home-dash-line pointer-events-none absolute inset-x-0 top-5 hidden h-0.5 rounded-full md:block" aria-hidden="true"></div>

                @foreach ([
                    ['step' => '1', 'title' => __('Daftar & RSVP'), 'desc' => __('Pegawai berdaftar dan mengesahkan penyertaan sebelum acara.')],
                    ['step' => '2', 'title' => __('Sahkan kehadiran di kaunter'), 'desc' => __('Petugas menyahkan kehadiran fizikal semasa ketibaan.')],
                    ['step' => '3', 'title' => __('Umum di pentas'), 'desc' => __('Nama dipanggil mengikut giliran dan dipaparkan di layar.')],
                ] as $step)
                    <div class="reveal relative text-center md:text-left" style="--d: {{ $loop->index * 120 }}ms">
                        <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-violet-600 text-sm font-bold text-white md:mx-0">
                            {{ $step['step'] }}
                        </span>
                        <p class="mt-4 font-semibold text-foreground">{{ $step['title'] }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Peranan --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h2 class="reveal text-center text-2xl font-bold tracking-tight text-foreground sm:text-3xl">{{ __('Dibina untuk setiap peranan') }}</h2>

        <div class="mt-10 grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['icon' => 'ri-shield-user-line', 'color' => 'indigo', 'title' => __('Admin'), 'desc' => __('Urus sesi majlis, kehadiran, dan laporan keseluruhan acara.')],
                ['icon' => 'ri-tv-2-line', 'color' => 'violet', 'title' => __('Media'), 'desc' => __('Kawal layar utama, presentasi, dan pantau analitik pengumuman.')],
                ['icon' => 'ri-user-line', 'color' => 'emerald', 'title' => __('Pengguna'), 'desc' => __('Semak status kehadiran dan maklumat sesi sendiri.')],
            ] as $role)
                <div class="reveal rounded-2xl border border-border bg-card p-5 text-center shadow-sm" style="--d: {{ $loop->index * 90 }}ms">
                    <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl ring-1 {{ $tints[$role['color']]['icon'] }}" aria-hidden="true">
                        <i class="{{ $role['icon'] }} text-xl leading-none"></i>
                    </span>
                    <p class="mt-4 font-semibold text-foreground">{{ $role['title'] }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ $role['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-border/60">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:px-6">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}</p>
            <a href="{{ route('login') }}" class="hover:text-foreground">{{ __('Log masuk') }}</a>
        </div>
    </footer>

    <script>
        (function () {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Scroll-reveal
            var revealEls = document.querySelectorAll('.reveal');
            if (reduce || !('IntersectionObserver' in window)) {
                revealEls.forEach(function (el) { el.classList.add('is-visible'); });
            } else {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });
                revealEls.forEach(function (el) { io.observe(el); });
            }

            // Count-up numbers
            var countEls = document.querySelectorAll('[data-count-to]');
            function animateCount(el) {
                var to = parseInt(el.getAttribute('data-count-to'), 10) || 0;
                if (reduce) {
                    el.textContent = String(to);
                    return;
                }
                var start = null;
                var duration = 1400;
                function step(ts) {
                    if (start === null) start = ts;
                    var progress = Math.min((ts - start) / duration, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = String(Math.round(eased * to));
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                }
                window.requestAnimationFrame(step);
            }
            if (countEls.length) {
                if (reduce || !('IntersectionObserver' in window)) {
                    countEls.forEach(function (el) { el.textContent = el.getAttribute('data-count-to'); });
                } else {
                    var countIo = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                animateCount(entry.target);
                                countIo.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.5 });
                    countEls.forEach(function (el) { countIo.observe(el); });
                }
            }

            // 3D tilt + glare on hero mock card
            var tiltEl = document.querySelector('[data-tilt]');
            var canHover = window.matchMedia('(hover: hover)').matches;
            if (tiltEl && canHover && !reduce) {
                tiltEl.addEventListener('pointermove', function (e) {
                    var rect = tiltEl.getBoundingClientRect();
                    var px = (e.clientX - rect.left) / rect.width;
                    var py = (e.clientY - rect.top) / rect.height;
                    var rx = (0.5 - py) * 10;
                    var ry = (px - 0.5) * 10;
                    tiltEl.style.setProperty('--rx', rx.toFixed(2) + 'deg');
                    tiltEl.style.setProperty('--ry', ry.toFixed(2) + 'deg');
                    tiltEl.style.setProperty('--mx', (px * 100).toFixed(1) + '%');
                    tiltEl.style.setProperty('--my', (py * 100).toFixed(1) + '%');
                });
                tiltEl.addEventListener('pointerleave', function () {
                    tiltEl.style.setProperty('--rx', '0deg');
                    tiltEl.style.setProperty('--ry', '0deg');
                });
            }

            // Cursor-following spotlight on feature cards
            var ciriSection = document.getElementById('ciri');
            if (ciriSection && canHover && !reduce) {
                ciriSection.addEventListener('pointermove', function (e) {
                    var target = e.target.closest('.home-spotlight');
                    if (!target) return;
                    var rect = target.getBoundingClientRect();
                    target.style.setProperty('--mx', (e.clientX - rect.left) + 'px');
                    target.style.setProperty('--my', (e.clientY - rect.top) + 'px');
                });
            }

            // Header scroll shadow
            var header = document.querySelector('[data-home-header]');
            if (header) {
                var ticking = false;
                function onScroll() {
                    if (ticking) return;
                    ticking = true;
                    window.requestAnimationFrame(function () {
                        header.classList.toggle('is-scrolled', window.scrollY > 8);
                        ticking = false;
                    });
                }
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();
            }
        })();
    </script>
</body>
</html>
