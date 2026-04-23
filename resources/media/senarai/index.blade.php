<x-dashboard-layout :title="__('Senarai kehadiran - Persediaan')" role="media">
    <div class="relative min-h-[calc(100dvh-8rem)] overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-1">
        <div class="relative h-full rounded-xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 dark:from-slate-950 dark:to-slate-900">
            {{-- Animated background elements --}}
            <div class="absolute inset-0 overflow-hidden opacity-20">
                <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-purple-400 mix-blend-multiply blur-3xl filter"></div>
                <div class="animation-delay-2000 absolute -right-4 top-0 h-72 w-72 animate-pulse rounded-full bg-indigo-400 mix-blend-multiply blur-3xl filter"></div>
                <div class="animation-delay-4000 absolute -bottom-8 left-20 h-72 w-72 animate-pulse rounded-full bg-pink-400 mix-blend-multiply blur-3xl filter"></div>
            </div>

            <div class="relative mx-auto max-w-2xl space-y-12 py-16">
                {{-- Header --}}
                <header class="text-center">
                    <div class="mb-4 inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-medium text-purple-200 backdrop-blur-sm">
                        <i class="ri-presentation-line mr-2"></i>
                        {{ __('Mod Presentasi') }}
                    </div>
                    <h1 class="mb-4 bg-gradient-to-r from-white via-purple-200 to-pink-200 bg-clip-text text-5xl font-extrabold tracking-tight text-transparent sm:text-6xl">
                        {{ __('Persediaan Paparan') }}
                    </h1>
                    <p class="text-xl text-slate-300">
                        {{ __('Pilih sesi untuk memulakan persembahan kehadiran pegawai') }}
                    </p>
                </header>

                {{-- Stats Preview --}}
                @if ($pegawaiCount > 0)
                    <div class="grid grid-cols-3 gap-4">
                        <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-center backdrop-blur-sm">
                            <div class="text-3xl font-bold text-white">{{ $pegawaiCount }}</div>
                            <div class="mt-1 text-sm text-slate-300">{{ __('Pegawai') }}</div>
                        </div>
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-center backdrop-blur-sm">
                            <div class="text-3xl font-bold text-emerald-200">{{ $ontimeCount }}</div>
                            <div class="mt-1 text-sm text-emerald-300">{{ __('Tepat masa') }}</div>
                        </div>
                        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-center backdrop-blur-sm">
                            <div class="text-3xl font-bold text-amber-200">{{ $lateCount }}</div>
                            <div class="mt-1 text-sm text-amber-300">{{ __('Lewat') }}</div>
                        </div>
                    </div>
                @endif

                {{-- Setup Form --}}
                <div class="group rounded-2xl border border-white/20 bg-white/10 p-8 backdrop-blur-xl transition-all duration-300 hover:border-white/30 hover:bg-white/15 hover:shadow-2xl hover:shadow-purple-500/20">
                    <form id="presentation-setup-form" class="space-y-6">
                        <div>
                            <label for="sesi-select" class="mb-3 flex items-center text-lg font-semibold text-white">
                                <i class="ri-calendar-event-line mr-2 text-xl text-purple-300"></i>
                                {{ __('Pilih Sesi Majlis') }}
                            </label>
                            <select
                                id="sesi-select"
                                class="w-full rounded-xl border-2 border-white/20 bg-slate-800/50 px-5 py-4 text-lg text-white backdrop-blur-sm transition-all duration-200 focus:border-purple-400 focus:outline-none focus:ring-4 focus:ring-purple-400/30"
                            >
                                <option value="">{{ __('Semua sesi') }}</option>
                                @foreach ($allSesis as $sesi)
                                    <option value="{{ $sesi->id }}" @selected($selectedSesi?->id === $sesi->id)>
                                        {{ $sesi->sesi }}
                                        @if ($sesi->is_late)
                                            ({{ __('Lewat') }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-sm text-slate-400">
                                {{ __('Pilih sesi khusus atau pilih "Semua sesi" untuk paparan keseluruhan') }}
                            </p>
                        </div>

                        <button
                            type="button"
                            id="start-presentation-btn"
                            class="group/btn relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 px-8 py-5 text-lg font-bold text-white shadow-lg shadow-purple-500/50 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl hover:shadow-purple-500/60 active:scale-[0.98]"
                        >
                            <span class="relative z-10 flex items-center justify-center">
                                <i class="ri-play-circle-line mr-2 text-2xl"></i>
                                {{ __('Mula Presentasi') }}
                                <i class="ri-arrow-right-line ml-2 transition-transform duration-300 group-hover/btn:translate-x-1"></i>
                            </span>
                            <div class="absolute inset-0 -z-0 bg-gradient-to-r from-pink-500 to-purple-500 opacity-0 transition-opacity duration-300 group-hover/btn:opacity-100"></div>
                        </button>
                    </form>
                </div>

                {{-- Quick Tips --}}
                <div class="rounded-xl border border-slate-700/50 bg-slate-800/30 p-6 backdrop-blur-sm">
                    <h3 class="mb-4 flex items-center text-lg font-semibold text-white">
                        <i class="ri-lightbulb-line mr-2 text-xl text-amber-400"></i>
                        {{ __('Panduan Pantas') }}
                    </h3>
                    <ul class="space-y-3 text-slate-300">
                        <li class="flex items-start">
                            <i class="ri-keyboard-line mr-3 mt-0.5 text-purple-400"></i>
                            <span>{{ __('Gunakan kekunci ← / → untuk navigasi pegawai') }}</span>
                        </li>
                        <li class="flex items-start">
                            <i class="ri-fullscreen-line mr-3 mt-0.5 text-purple-400"></i>
                            <span>{{ __('Klik butang skrin penuh untuk paparan optimum') }}</span>
                        </li>
                        <li class="flex items-start">
                            <i class="ri-image-2-line mr-3 mt-0.5 text-purple-400"></i>
                            <span>{{ __('Latar belakang aktif akan dipaparkan secara automatik') }}</span>
                        </li>
                        <li class="flex items-start">
                            <i class="ri-logout-box-line mr-3 mt-0.5 text-purple-400"></i>
                            <span>{{ __('Tekan ESC atau klik butang keluar untuk kembali') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const btn = document.getElementById('start-presentation-btn');

                function exitFullscreenIfActive() {
                    const doc = document;

                    if (!doc.fullscreenElement && !doc.webkitFullscreenElement && !doc.msFullscreenElement) {
                        return;
                    }

                    if (doc.exitFullscreen) {
                        doc.exitFullscreen().catch(function () {});

                        return;
                    }

                    if (doc.webkitExitFullscreen) {
                        try {
                            doc.webkitExitFullscreen();
                        } catch (err) {
                            /* ignore */
                        }

                        return;
                    }

                    if (doc.msExitFullscreen) {
                        try {
                            doc.msExitFullscreen();
                        } catch (err) {
                            /* ignore */
                        }
                    }
                }

                function onSetupPageReady() {
                    exitFullscreenIfActive();
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', onSetupPageReady);
                } else {
                    onSetupPageReady();
                }

                function navigateToPresentation(url) {
                    document.body.style.transition = 'opacity 0.3s ease-out';
                    document.body.style.opacity = '0';

                    setTimeout(function () {
                        window.location.href = url;
                    }, 300);
                }

                function requestFullscreenElement(el) {
                    if (el.requestFullscreen) {
                        return el.requestFullscreen();
                    }
                    if (el.webkitRequestFullscreen) {
                        return Promise.resolve(el.webkitRequestFullscreen());
                    }
                    if (el.msRequestFullscreen) {
                        return Promise.resolve(el.msRequestFullscreen());
                    }

                    return Promise.reject(new Error('Fullscreen not supported'));
                }

                btn.addEventListener('click', async function () {
                    const sesiId = document.getElementById('sesi-select').value;
                    const url = "{{ route('media.senarai.present') }}" + (sesiId ? '?sesi_id=' + encodeURIComponent(sesiId) : '');

                    try {
                        await requestFullscreenElement(document.documentElement);
                    } catch (err) {
                        /* Rejected or unsupported — continue to presentation without fullscreen */
                    }

                    navigateToPresentation(url);
                });
            })();
        </script>
    @endpush
</x-dashboard-layout>
