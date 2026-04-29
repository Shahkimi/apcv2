<x-dashboard-layout :title="__('Senarai kehadiran - Persediaan')" role="media">
    {{-- Custom styles --}}
    @push('styles')
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-12px) rotate(1deg); }
            66% { transform: translateY(-6px) rotate(-1deg); }
        }
        @keyframes shimmer {
            0% { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(1.6); opacity: 0; }
        }
        @keyframes orb1 {
            0%, 100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(40px, -30px) scale(1.1); }
        }
        @keyframes orb2 {
            0%, 100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(-30px, 40px) scale(0.9); }
        }
        @keyframes orb3 {
            0%, 100% { transform: translate(0,0) scale(1); }
            50% { transform: translate(20px, 20px) scale(1.05); }
        }
        .orb-1 { animation: orb1 8s ease-in-out infinite; }
        .orb-2 { animation: orb2 10s ease-in-out infinite; }
        .orb-3 { animation: orb3 12s ease-in-out infinite; }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-fade-in-up { animation: fadeInUp 0.7s ease-out forwards; }
        .animate-fade-in-up-delay-1 { animation: fadeInUp 0.7s ease-out 0.15s forwards; opacity: 0; }
        .animate-fade-in-up-delay-2 { animation: fadeInUp 0.7s ease-out 0.3s forwards; opacity: 0; }
        .shimmer-text {
            background: linear-gradient(90deg, #0c4a6e 0%, #0ea5e9 30%, #0c4a6e 50%, #06b6d4 70%, #0c4a6e 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 4s linear infinite;
        }
        .glass-card {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(14, 165, 233, 0.15);
            box-shadow: 0 4px 24px rgba(14, 165, 233, 0.08), 0 2px 8px rgba(0,0,0,0.04);
        }
        .glass-card:hover {
            background: rgba(255,255,255,0.95);
            border-color: rgba(14, 165, 233, 0.3);
            box-shadow: 0 8px 32px rgba(14, 165, 233, 0.15), 0 4px 16px rgba(0,0,0,0.06);
        }
        .select-styled {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2306b6d4' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1.25rem center;
        }
        .select-styled option {
            background-color: #ffffff;
            color: #1e293b;
        }
        .btn-glow {
            box-shadow: 0 2px 16px rgba(14, 165, 233, 0.3), 0 8px 32px rgba(6, 182, 212, 0.2);
        }
        .btn-glow:hover {
            box-shadow: 0 4px 24px rgba(14, 165, 233, 0.4), 0 12px 40px rgba(6, 182, 212, 0.25);
        }
        .pulse-ring::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 9999px;
            border: 2px solid rgba(14, 165, 233, 0.5);
            animation: pulseRing 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        .stat-badge {
            background: rgba(14, 165, 233, 0.08);
            border: 1px solid rgba(14, 165, 233, 0.2);
        }
    </style>
    @endpush

    <div class="rounded-2xl bg-gradient-to-br from-sky-400 via-cyan-400 to-blue-400 p-1">
    <div class="relative min-h-[calc(100dvh-8rem)] overflow-hidden rounded-2xl bg-white">

        {{-- Background gradient mesh --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute inset-0 bg-gradient-to-br from-sky-50 via-cyan-50/80 to-blue-50"></div>
            {{-- Animated orbs --}}
            <div class="orb-1 absolute -top-32 -left-32 h-96 w-96 rounded-full bg-sky-400/15 blur-3xl"></div>
            <div class="orb-2 absolute -top-16 -right-32 h-80 w-80 rounded-full bg-cyan-300/12 blur-3xl"></div>
            <div class="orb-3 absolute -bottom-32 left-1/3 h-96 w-96 rounded-full bg-blue-400/10 blur-3xl"></div>
            <div class="orb-1 absolute bottom-0 right-0 h-64 w-64 rounded-full bg-indigo-300/12 blur-2xl"></div>
            {{-- Grid overlay --}}
            <div class="absolute inset-0 opacity-[0.02]"
                 style="background-image: linear-gradient(rgba(15,23,42,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(15,23,42,0.1) 1px, transparent 1px); background-size: 48px 48px;">
            </div>
        </div>

        {{-- Top accent bar --}}
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-sky-400/50 to-transparent"></div>

        <div class="relative mx-auto max-w-2xl px-6 py-16 space-y-10">

            {{-- Header section --}}
            <header class="text-center space-y-6 animate-fade-in-up">

                {{-- Mode badge --}}
                <div class="inline-flex items-center gap-2.5 rounded-full border border-sky-400/40 bg-sky-100 px-5 py-2 text-sm font-medium text-sky-700 shadow-lg shadow-sky-400/10 backdrop-blur-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-500 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-500"></span>
                    </span>
                    <i class="ri-presentation-line text-sky-600"></i>
                    {{ __("Mod Presentasi") }}
                </div>

                {{-- Title --}}
                <div>
                    <h1 class="shimmer-text text-5xl font-extrabold tracking-tight sm:text-6xl leading-tight">
                        {{ __("Persediaan Paparan") }}
                    </h1>
                    <div class="mt-3 h-px w-24 mx-auto bg-gradient-to-r from-transparent via-sky-400 to-transparent"></div>
                </div>
                
                {{-- Stats row --}}
                <div class="flex items-center justify-center gap-4 flex-wrap">
                    <div class="stat-badge flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-medium text-slate-700">
                        <i class="ri-fullscreen-line text-sky-500"></i>
                        {{ __("Mod Skrin Penuh") }}
                    </div>
                    <div class="stat-badge flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-medium text-slate-700">
                        <i class="ri-slideshow-3-line text-cyan-500"></i>
                        {{ __("Auto Persembahan") }}
                    </div>
                    <div class="stat-badge flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-medium text-slate-700">
                        <i class="ri-time-line text-blue-500"></i>
                        {{ __("Masa Nyata") }}
                    </div>
                </div>
            </header>

            {{-- Main Setup Card --}}
            <div class="glass-card animate-fade-in-up-delay-1 rounded-2xl p-8 transition-all duration-500">

                {{-- Card header --}}
                <div class="mb-8 flex items-center gap-4">
                    <div class="relative pulse-ring flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-cyan-600 shadow-lg shadow-sky-500/30">
                        <i class="ri-calendar-event-line text-xl text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ __("Konfigurasi Sesi") }}</h2>
                        <p class="text-sm text-slate-600">{{ __("Tetapkan parameter persembahan anda") }}</p>
                    </div>
                </div>

                <form id="presentation-setup-form" class="space-y-6">

                    {{-- Session select --}}
                    <div class="space-y-2">
                        <label for="sesi-select" class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <i class="ri-calendar-line text-sky-500"></i>
                            {{ __("Pilih Sesi Majlis") }}
                        </label>

                        <div class="relative">
                            <select
                                id="sesi-select"
                                class="select-styled w-full rounded-xl border border-slate-300 bg-white px-4 py-4 pr-12 text-base text-slate-900 shadow-sm backdrop-blur-sm transition-all duration-200 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/30 hover:border-slate-400"
                            >
                                <option value="">{{ __("— Semua Sesi —") }}</option>
                                @foreach ($allSesis as $sesi)
                                    <option value="{{ $sesi->id }}" @selected($selectedSesi?->id === $sesi->id)>
                                        {{ $sesi->sesi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <p class="flex items-center gap-1.5 text-xs text-slate-500">
                            <i class="ri-information-line"></i>
                            {{ __("Pilih sesi khusus atau pilih semua sesi untuk paparan keseluruhan") }}
                        </p>
                    </div>

                    {{-- Divider --}}
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-slate-200"></div>
                        </div>
                    </div>

                    {{-- Start button --}}
                    <button
                        type="button"
                        id="start-presentation-btn"
                        class="btn-glow group/btn relative w-full overflow-hidden rounded-xl bg-gradient-to-r from-sky-500 via-cyan-500 to-blue-600 px-8 py-5 text-base font-bold text-white transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]"
                    >
                        {{-- Shimmer sweep --}}
                        <span class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/25 to-transparent transition-transform duration-700 group-hover/btn:translate-x-full"></span>

                        <span class="relative flex items-center justify-center gap-3">
                            <i class="ri-play-circle-fill text-2xl opacity-90"></i>
                            <span>{{ __("Mula Presentasi") }}</span>
                            <i class="ri-arrow-right-line text-lg transition-transform duration-300 group-hover/btn:translate-x-1.5"></i>
                        </span>
                    </button>

                    <a
                        href="{{ route('media.kawalan.presentation.index') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-sky-300/60 bg-white/70 px-4 py-3 text-sm font-semibold text-sky-700 transition hover:border-sky-400 hover:bg-sky-50"
                    >
                        <i class="ri-settings-4-line"></i>
                        {{ __('Kawalan Paparan Presentasi') }}
                    </a>

                </form>
            </div>

            {{-- Footer hint --}}
            <p class="animate-fade-in-up-delay-2 text-center text-xs text-slate-500">
                <i class="ri-lock-line mr-1"></i>
                {{ __("Paparan akan dibuka dalam mod skrin penuh secara automatik") }}
            </p>

        </div>
    </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const btn = document.getElementById('start-presentation-btn');

                function exitFullscreenIfActive() {
                    const doc = document;
                    if (!doc.fullscreenElement && !doc.webkitFullscreenElement && !doc.msFullscreenElement) return;
                    if (doc.exitFullscreen) { doc.exitFullscreen().catch(function () {}); return; }
                    if (doc.webkitExitFullscreen) { try { doc.webkitExitFullscreen(); } catch (err) {} return; }
                    if (doc.msExitFullscreen) { try { doc.msExitFullscreen(); } catch (err) {} }
                }

                function onSetupPageReady() { exitFullscreenIfActive(); }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', onSetupPageReady);
                } else {
                    onSetupPageReady();
                }

                function navigateToPresentation(url) {
                    document.body.style.transition = 'opacity 0.4s ease-out';
                    document.body.style.opacity = '0';
                    setTimeout(function () { window.location.href = url; }, 400);
                }

                function requestFullscreenElement(el) {
                    if (el.requestFullscreen) return el.requestFullscreen();
                    if (el.webkitRequestFullscreen) return Promise.resolve(el.webkitRequestFullscreen());
                    if (el.msRequestFullscreen) return Promise.resolve(el.msRequestFullscreen());
                    return Promise.reject(new Error('Fullscreen not supported'));
                }

                btn.addEventListener('click', async function () {
                    const sesiId = document.getElementById('sesi-select').value;
                    const url = "{{ route('media.senarai.present') }}" + (sesiId ? '?sesi_id=' + encodeURIComponent(sesiId) : '');
                    try { await requestFullscreenElement(document.documentElement); } catch (err) {}
                    navigateToPresentation(url);
                });
            })();
        </script>
    @endpush
</x-dashboard-layout>
