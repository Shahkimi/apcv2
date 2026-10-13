<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Kiosk Kehadiran') }} — {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Self check-in kiosk — fixed palette (independent of dark mode), sized for reading at arm's length */
        .k-title { font-size: clamp(2.25rem, 3.6vw, 4rem); line-height: 1.05; }
        .k-sub { font-size: clamp(1rem, 1.35vw, 1.4rem); }
        .k-name { font-size: clamp(1.75rem, 3vw, 3.25rem); line-height: 1.1; }
        .k-seat { font-size: clamp(2.5rem, 4.5vw, 4.75rem); line-height: 1; }
        .k-clock { font-size: clamp(1.5rem, 2.2vw, 2.4rem); line-height: 1; }

        .kiosk-stack { display: grid; }
        .kiosk-stack > * { grid-area: 1 / 1; }

        .kiosk-bg-glow {
            background:
                radial-gradient(60rem 40rem at 15% 10%, rgba(16, 185, 129, 0.25), transparent 60%),
                radial-gradient(50rem 35rem at 85% 90%, rgba(56, 189, 248, 0.2), transparent 60%);
            animation: kiosk-drift 18s ease-in-out infinite alternate;
        }

        .kiosk-search {
            display: flex;
            align-items: center;
            gap: clamp(0.75rem, 1.2vw, 1.25rem);
            border-radius: 9999px;
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: clamp(0.85rem, 1.4vw, 1.35rem) clamp(1.25rem, 2vw, 2rem);
            box-shadow: 0 1px 6px rgba(32, 33, 36, 0.18), 0 12px 32px -12px rgba(15, 23, 42, 0.35);
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .kiosk-search.is-complete {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.18), 0 12px 32px -12px rgba(15, 23, 42, 0.35);
        }

        .kiosk-search.is-idle { animation: kiosk-attract 2.6s ease-in-out infinite; }

        .kiosk-slot {
            position: relative;
            display: inline-grid;
            place-items: center;
            width: clamp(1.6rem, 2.6vw, 2.9rem);
            height: clamp(2.4rem, 3.8vw, 4rem);
            border-radius: 0.6rem;
            background: #f1f5f9;
            color: #0f172a;
            font-size: clamp(1.6rem, 2.7vw, 3rem);
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            transition: background-color 0.15s ease;
        }

        .kiosk-slot.is-filled {
            background: #ecfdf5;
            color: #065f46;
            animation: kiosk-pop 0.18s ease-out;
        }

        .kiosk-slot.is-caret::after {
            content: '';
            position: absolute;
            top: 22%;
            left: 50%;
            width: 3px;
            height: 56%;
            border-radius: 2px;
            transform: translateX(-50%);
            background: #10b981;
            animation: kiosk-blink 1s steps(1) infinite;
        }

        .kiosk-search.is-loading .kiosk-slot { animation: kiosk-fade 0.9s ease-in-out infinite; }

        .kiosk-sep {
            color: #94a3b8;
            font-size: clamp(1.4rem, 2.4vw, 2.6rem);
            font-weight: 600;
        }

        .kiosk-shake {
            border-color: #f43f5e !important;
            animation: kiosk-shake 0.45s cubic-bezier(0.36, 0.07, 0.19, 0.97) both !important;
        }

        .kiosk-kbd {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            min-width: 2.25rem;
            height: 2.25rem;
            padding: 0 0.65rem;
            border-radius: 0.6rem;
            background: #fff;
            color: #0f172a;
            font-size: 0.85rem;
            font-weight: 700;
            font-family: inherit;
            box-shadow: inset 0 -3px 0 #cbd5e1, 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .kiosk-kbd-lg {
            min-width: 5rem;
            height: 3.75rem;
            border-radius: 0.9rem;
            font-size: 1.15rem;
        }

        .kiosk-numpad-key {
            display: grid;
            place-items: center;
            height: clamp(3rem, 6.2vh, 4.5rem);
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.92);
            color: #0f172a;
            font-size: clamp(1.25rem, 1.8vw, 1.75rem);
            font-weight: 700;
            box-shadow: inset 0 -4px 0 #cbd5e1, 0 2px 6px rgba(0, 0, 0, 0.25);
            transition: transform 0.08s ease, background-color 0.08s ease, box-shadow 0.08s ease;
        }

        .kiosk-numpad-key.is-pressed {
            transform: translateY(3px);
            background: #a7f3d0;
            box-shadow: inset 0 -1px 0 #6ee7b7, 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .kiosk-pressable.is-pressed { transform: scale(0.98); filter: brightness(1.08); }

        /* Check-in ticket: details + actions on the left, seat stub on the right behind a perforation. */
        .kiosk-ticket { --stub: clamp(15rem, 21vw, 20rem); --notch: 1.15rem; }
        .kiosk-ticket-shadow { filter: drop-shadow(0 28px 48px rgba(2, 6, 23, 0.55)); }
        .kiosk-ticket-grid { display: grid; grid-template-areas: "info" "stub" "actions"; }
        .kiosk-ticket-info { grid-area: info; }
        .kiosk-ticket-stub { grid-area: stub; }
        .kiosk-ticket-actions { grid-area: actions; }
        .k-stub-number { font-size: clamp(3rem, 5vw, 5.5rem); line-height: 1; }
        .k-name.k-name-long { font-size: clamp(1.5rem, 2.4vw, 2.6rem); }

        @media (min-width: 1024px) {
            .kiosk-ticket-grid {
                grid-template-columns: minmax(0, 1fr) var(--stub);
                grid-template-rows: 1fr auto;
                grid-template-areas: "info stub" "actions stub";
            }

            /* Real see-through notches where the perforation meets the top and bottom edges. */
            .kiosk-ticket {
                --cut: calc(100% - var(--stub) + 1px);
                -webkit-mask:
                    radial-gradient(circle var(--notch) at var(--cut) 0, transparent 97%, #000 100%) top / 100% 51% no-repeat,
                    radial-gradient(circle var(--notch) at var(--cut) 100%, transparent 97%, #000 100%) bottom / 100% 51% no-repeat;
                mask:
                    radial-gradient(circle var(--notch) at var(--cut) 0, transparent 97%, #000 100%) top / 100% 51% no-repeat,
                    radial-gradient(circle var(--notch) at var(--cut) 100%, transparent 97%, #000 100%) bottom / 100% 51% no-repeat;
            }
        }

        .kiosk-check-circle {
            stroke: #10b981;
            stroke-width: 3;
            stroke-dasharray: 151;
            stroke-dashoffset: 151;
            animation: kiosk-draw 0.6s ease-out forwards;
        }

        .kiosk-check-mark {
            stroke: #10b981;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 36;
            stroke-dashoffset: 36;
            animation: kiosk-draw 0.35s 0.5s ease-out forwards;
        }

        @keyframes kiosk-pop { from { transform: scale(0.82); } to { transform: scale(1); } }
        @keyframes kiosk-blink { 50% { opacity: 0; } }
        @keyframes kiosk-fade { 50% { opacity: 0.45; } }
        @keyframes kiosk-draw { to { stroke-dashoffset: 0; } }
        @keyframes kiosk-drift { from { transform: translate3d(-2%, -1%, 0) scale(1); } to { transform: translate3d(2%, 1%, 0) scale(1.06); } }
        @keyframes kiosk-attract {
            0%, 100% { box-shadow: 0 1px 6px rgba(32, 33, 36, 0.18), 0 12px 32px -12px rgba(15, 23, 42, 0.35); }
            50% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0.18), 0 12px 32px -12px rgba(15, 23, 42, 0.35); }
        }
        @keyframes kiosk-shake {
            10%, 90% { transform: translateX(-2px); }
            20%, 80% { transform: translateX(4px); }
            30%, 50%, 70% { transform: translateX(-8px); }
            40%, 60% { transform: translateX(8px); }
        }

        @media (max-height: 760px) {
            .kiosk-numpad { display: none !important; }
        }

        @media (prefers-reduced-motion: reduce) {
            .kiosk *, .kiosk *::before, .kiosk *::after {
                animation: none !important;
                transition: none !important;
            }

            .kiosk-check-circle, .kiosk-check-mark { stroke-dashoffset: 0; }
        }
    </style>
</head>
<body class="kiosk h-screen w-screen select-none overflow-hidden bg-slate-950 font-sans text-white antialiased">
    @php
        $steps = [1 => __('Masukkan No. KP'), 2 => __('Semak Maklumat'), 3 => __('Selesai')];
        $numpad = [
            ['7', '7'], ['8', '8'], ['9', '9'],
            ['4', '4'], ['5', '5'], ['6', '6'],
            ['1', '1'], ['2', '2'], ['3', '3'],
            ['Backspace', '⌫'], ['0', '0'], ['Enter', '⏎'],
        ];
    @endphp

    <div
        x-data="kiosk(@js([
            'urls' => [
                'lookup' => route('user.kiosk.lookup'),
                'verify' => route('user.kehadiran.verify', ['pegawai' => '__ID__']),
                'exit' => $exitUrl,
            ],
            'sessionName' => $activeSesiName,
        ]))"
        x-cloak
        class="relative flex h-full w-full flex-col"
    >
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-cover bg-center"
            @if ($backdropUrl)
                style="background-image: url('{{ e($backdropUrl) }}');"
            @else
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);"
            @endif
            aria-hidden="true"
        ></div>
        <div class="absolute inset-0 bg-slate-950/70" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-slate-950/40 via-transparent to-slate-950/90" aria-hidden="true"></div>
        <div class="kiosk-bg-glow pointer-events-none absolute -inset-[10%]" aria-hidden="true"></div>

        {{-- Top bar --}}
        <header class="relative z-10 flex items-center justify-between gap-6 px-[clamp(1.5rem,3vw,3rem)] pt-[clamp(1rem,3vh,2rem)]">
            <div class="flex min-w-0 items-center gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white/15 text-2xl font-extrabold ring-1 ring-white/25 backdrop-blur">
                    {{ mb_substr(config('app.name', 'A'), 0, 1) }}
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-white/70">{{ config('app.name') }} · {{ __('Kiosk Kehadiran') }}</p>
                    <p class="truncate text-lg font-bold sm:text-xl">{{ $eventTitle }}</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-6">
                <span
                    class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold ring-1"
                    :class="sessionName ? 'bg-emerald-500/20 text-emerald-100 ring-emerald-300/40' : 'bg-amber-500/20 text-amber-100 ring-amber-300/40'"
                >
                    <span class="h-2 w-2 rounded-full" :class="sessionName ? 'animate-pulse bg-emerald-300' : 'bg-amber-300'"></span>
                    <span x-text="sessionName ? @js(__('Sesi')) + ': ' + sessionName : @js(__('Tiada sesi aktif'))"></span>
                </span>
                <div class="text-right">
                    <p class="k-clock font-bold tabular-nums" x-text="clockTime()"></p>
                    <p class="mt-1 text-sm text-white/70" x-text="clockDate()"></p>
                </div>
            </div>
        </header>

        {{-- Steps --}}
        <nav class="relative z-10 mt-[clamp(0.75rem,2.5vh,1.75rem)] flex justify-center px-6" aria-label="{{ __('Langkah') }}">
            <ol class="flex items-center gap-2 rounded-full bg-white/10 p-1.5 ring-1 ring-white/15 backdrop-blur">
                @foreach ($steps as $n => $label)
                    <li
                        class="flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold transition-colors"
                        :class="step() === {{ $n }} ? 'bg-white text-slate-900 shadow' : (step() > {{ $n }} ? 'text-emerald-200' : 'text-white/60')"
                        :aria-current="step() === {{ $n }} ? 'step' : null"
                    >
                        <span
                            class="grid h-6 w-6 place-items-center rounded-full text-xs"
                            :class="step() === {{ $n }} ? 'bg-emerald-600 text-white' : (step() > {{ $n }} ? 'bg-emerald-400/25 text-emerald-100' : 'bg-white/15')"
                        >
                            <i class="ri-check-line" x-show="step() > {{ $n }}" aria-hidden="true"></i>
                            <span x-show="step() <= {{ $n }}">{{ $n }}</span>
                        </span>
                        <span class="hidden sm:inline">{{ $label }}</span>
                    </li>
                    @if ($n < count($steps))
                        <li aria-hidden="true" class="text-white/40"><i class="ri-arrow-right-s-line"></i></li>
                    @endif
                @endforeach
            </ol>
        </nav>

        {{-- Stage --}}
        <main class="kiosk-stack relative z-10 min-h-0 flex-1 place-items-center px-[clamp(1rem,3vw,3rem)] py-[clamp(1rem,3vh,2rem)]">

            {{-- 1. Search --}}
            <section x-show="state === 'search' || state === 'loading'" x-transition.duration.250ms class="flex w-full items-center justify-center gap-[clamp(1.5rem,3vw,3.5rem)]">
                <div class="w-[min(58rem,100%)] rounded-[2rem] bg-white p-[clamp(1.5rem,3vw,3rem)] text-slate-900 shadow-2xl ring-1 ring-white/40 backdrop-blur">
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.2em] text-emerald-700">
                        <i class="ri-shield-user-line text-lg" aria-hidden="true"></i>
                        {{ __('Daftar Masuk Kendiri') }}
                    </div>
                    <h1 class="k-title mt-3 font-extrabold tracking-tight">{{ __('Selamat Datang') }}</h1>
                    <p class="k-sub mt-3 text-slate-600">
                        {{ __('Sila masukkan') }} <span class="font-semibold text-slate-900">{{ __('No. Kad Pengenalan') }}</span> {{ __('anda (12 digit) menggunakan papan kekunci nombor.') }}
                    </p>

                    <div
                        class="kiosk-search mt-[clamp(1.25rem,3vh,2.25rem)]"
                        :class="{
                            'kiosk-shake': shaking,
                            'is-complete': digits.length === 12,
                            'is-idle': digits.length === 0 && state === 'search',
                            'is-loading': state === 'loading',
                        }"
                        role="textbox"
                        aria-readonly="true"
                        :aria-label="@js(__('No. Kad Pengenalan')) + ': ' + digits.join('')"
                    >
                        <i class="ri-search-line shrink-0 text-[clamp(1.5rem,2.2vw,2.25rem)] text-slate-400" aria-hidden="true"></i>
                        <div class="flex min-w-0 flex-1 items-center justify-center gap-[clamp(0.2rem,0.45vw,0.45rem)]">
                            <template x-for="token in slotTokens()" :key="token.key">
                                <span
                                    :class="token.sep ? 'kiosk-sep' : ['kiosk-slot', token.value ? 'is-filled' : '', token.caret ? 'is-caret' : '']"
                                    x-text="token.sep ? '–' : token.value"
                                ></span>
                            </template>
                        </div>
                        <span class="shrink-0 text-right text-sm font-semibold tabular-nums text-slate-500">
                            <i class="ri-loader-4-line animate-spin text-2xl text-emerald-600" x-show="state === 'loading'" aria-hidden="true"></i>
                            <span x-show="state !== 'loading'" x-text="digits.length + '/12'"></span>
                        </span>
                    </div>

                    <p
                        class="mt-4 flex items-center gap-2 text-[clamp(0.95rem,1.2vw,1.2rem)]"
                        :class="shaking ? 'font-semibold text-rose-600' : (escPresses.length ? 'font-semibold text-amber-600' : 'text-slate-500')"
                    >
                        <i :class="shaking ? 'ri-error-warning-line' : (escPresses.length ? 'ri-logout-box-r-line' : 'ri-information-line')" aria-hidden="true"></i>
                        <span x-text="searchHint()"></span>
                    </p>
                </div>

                {{-- Numpad mirror: shows which keys work and flashes on each press --}}
                <div class="kiosk-numpad hidden w-[clamp(13rem,18vw,17rem)] shrink-0 rounded-[2rem] bg-white/10 p-4 ring-1 ring-white/20 backdrop-blur lg:block" aria-hidden="true">
                    <div class="kiosk-numpad-key mb-3 !h-[clamp(2.5rem,5vh,3.5rem)] text-base" :class="pressedKey === 'Escape' ? 'is-pressed' : ''">Esc</div>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($numpad as [$keyId, $keyLabel])
                            <div
                                class="kiosk-numpad-key {{ $keyId === 'Enter' ? '!bg-emerald-500 !text-white' : '' }}"
                                :class="pressedKey === '{{ $keyId }}' ? 'is-pressed' : ''"
                            >{{ $keyLabel }}</div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-center text-xs font-medium text-white/70">{{ __('Guna papan kekunci nombor') }}</p>
                </div>
            </section>

            {{-- 2. Confirm: check-in ticket (details | perforation | seat stub) --}}
            <section x-show="state === 'confirm'" x-transition.duration.250ms class="w-[min(76rem,100%)]">
                <div class="kiosk-ticket-shadow">
                    <div class="kiosk-ticket kiosk-ticket-grid overflow-hidden rounded-[2rem] bg-white text-slate-900">
                        <div class="kiosk-ticket-info min-w-0 px-[clamp(1.5rem,3vw,3rem)] pb-[clamp(1.25rem,3vh,2rem)] pt-[clamp(1.5rem,3.5vh,2.75rem)]">
                            <p class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.2em] text-emerald-700">
                                <i class="ri-user-search-line text-lg" aria-hidden="true"></i>
                                {{ __('Adakah ini anda?') }}
                            </p>
                            <h2 class="k-name mt-[clamp(0.75rem,2vh,1.25rem)] break-words font-extrabold" :class="String(officer()?.nama ?? '').length > 36 ? 'k-name-long' : ''" x-text="officer()?.nama"></h2>
                            <p class="mt-3 flex items-center gap-3">
                                <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">{{ __('No. KP') }}</span>
                                <span class="font-mono text-lg tracking-[0.12em] text-slate-600" x-text="maskedIc()"></span>
                            </p>

                            <dl class="mt-[clamp(1rem,2.5vh,1.75rem)] grid gap-x-10 gap-y-[clamp(0.75rem,2vh,1.25rem)] border-t border-slate-100 pt-[clamp(1rem,2.5vh,1.75rem)] sm:grid-cols-2" :class="detailRows().length > 4 ? 'xl:grid-cols-3' : ''">
                                <template x-for="row in detailRows()" :key="row.key">
                                    <div class="min-w-0">
                                        <dt class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400" x-text="row.label"></dt>
                                        <dd class="mt-1.5 text-[clamp(1.05rem,1.35vw,1.35rem)] font-semibold leading-snug text-slate-900">
                                            <span x-show="row.badge" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-base ring-1" :class="row.badge">
                                                <i :class="row.icon" aria-hidden="true"></i>
                                                <span x-text="row.value"></span>
                                            </span>
                                            <span x-show="!row.badge" class="break-words" x-text="row.value"></span>
                                        </dd>
                                    </div>
                                </template>
                            </dl>
                        </div>

                        <div class="kiosk-ticket-stub flex flex-col border-t-2 border-dashed border-slate-200 bg-slate-50 px-6 py-[clamp(1rem,2.5vh,1.75rem)] lg:border-l-2 lg:border-t-0">
                            <div class="flex flex-1 flex-wrap justify-center gap-x-12 lg:flex-col lg:flex-nowrap">
                                <template x-for="(card, i) in seatCards()" :key="card.key">
                                    <div class="py-[clamp(0.5rem,1.75vh,1.25rem)] text-center" :class="i ? 'lg:border-t lg:border-slate-200' : ''">
                                        <p class="flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-500">
                                            <i :class="card.icon" aria-hidden="true"></i>
                                            <span x-text="card.label"></span>
                                        </p>
                                        <p class="k-stub-number mt-1 font-black tabular-nums" :class="card.accent" x-text="card.value"></p>
                                    </div>
                                </template>
                            </div>

                            {{-- Visible auto-cancel: the confirm screen resets after IDLE_MS without a key press. --}}
                            <div class="mt-4 text-center" :class="busy ? 'invisible' : ''">
                                <div class="h-1.5 overflow-hidden rounded-full bg-slate-200">
                                    <div class="h-full rounded-full transition-[width] duration-300 ease-linear" :class="confirmSecondsLeft() <= 10 ? 'bg-amber-500' : 'bg-emerald-500'" :style="{ width: confirmProgress() + '%' }"></div>
                                </div>
                                <p class="mt-2 flex items-center justify-center gap-1.5 text-sm tabular-nums" :class="confirmSecondsLeft() <= 10 ? 'font-semibold text-amber-700' : 'text-slate-500'">
                                    <i class="ri-timer-line" aria-hidden="true"></i>
                                    <span x-text="confirmHint()"></span>
                                </p>
                            </div>
                        </div>

                        <div class="kiosk-ticket-actions grid gap-4 border-t border-slate-200 bg-slate-50/60 px-[clamp(1.5rem,3vw,3rem)] py-[clamp(1rem,2.5vh,1.75rem)] sm:grid-cols-2">
                            <div class="kiosk-pressable flex items-center gap-5 rounded-2xl bg-emerald-600 px-6 py-[clamp(0.85rem,2vh,1.25rem)] text-white shadow-lg shadow-emerald-900/25 transition" :class="pressedKey === 'Enter' ? 'is-pressed' : ''">
                                <span class="kiosk-kbd kiosk-kbd-lg">Enter <i class="ri-corner-down-left-line" aria-hidden="true"></i></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[clamp(1.15rem,1.6vw,1.6rem)] font-bold">{{ __('Sahkan Kehadiran') }}</p>
                                    <p class="text-sm text-emerald-100">{{ __('Ya, ini saya') }}</p>
                                </div>
                                <i class="ri-loader-4-line animate-spin text-3xl" x-show="busy" aria-hidden="true"></i>
                            </div>
                            <div class="kiosk-pressable flex items-center gap-5 rounded-2xl bg-white px-6 py-[clamp(0.85rem,2vh,1.25rem)] text-slate-800 ring-1 ring-slate-200 transition" :class="pressedKey === 'Escape' ? 'is-pressed' : ''">
                                <span class="kiosk-kbd kiosk-kbd-lg">Esc</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[clamp(1.15rem,1.6vw,1.6rem)] font-bold">{{ __('Batal') }}</p>
                                    <p class="text-sm text-slate-500">{{ __('Bukan saya / kembali') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- 3a. Already attended --}}
            <section x-show="state === 'already'" x-transition.duration.250ms class="w-[min(60rem,100%)]">
                <div class="rounded-[2rem] bg-white p-[clamp(1.5rem,3vw,3rem)] text-center text-slate-900 shadow-2xl ring-1 ring-white/40 backdrop-blur">
                    <span class="mx-auto grid h-24 w-24 place-items-center rounded-full bg-sky-100 text-5xl text-sky-600">
                        <i class="ri-information-line" aria-hidden="true"></i>
                    </span>
                    <h2 class="k-title mt-6 font-extrabold tracking-tight">{{ __('Anda telah direkodkan hadir') }}</h2>
                    <p class="k-sub mt-3 text-slate-600" x-text="officer()?.nama"></p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <template x-for="card in seatCards()" :key="card.key">
                            <div class="min-w-[11rem] rounded-2xl px-6 py-4 text-left ring-1" :class="card.tint">
                                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.18em] opacity-80">
                                    <i :class="card.icon" aria-hidden="true"></i>
                                    <span x-text="card.label"></span>
                                </p>
                                <p class="k-seat mt-2 font-black tabular-nums" x-text="card.value"></p>
                            </div>
                        </template>
                    </div>
                    @include('user::kiosk.partials.countdown', ['ring' => 'stroke-sky-500'])
                </div>
            </section>

            {{-- 3b. Success --}}
            <section x-show="state === 'success'" x-transition.duration.250ms class="w-[min(60rem,100%)]">
                <div class="rounded-[2rem] bg-white p-[clamp(1.5rem,3vw,3rem)] text-center text-slate-900 shadow-2xl ring-1 ring-white/40 backdrop-blur">
                    <template x-if="state === 'success'">
                        <svg viewBox="0 0 52 52" class="mx-auto h-28 w-28" aria-hidden="true">
                            <circle class="kiosk-check-circle" cx="26" cy="26" r="24" fill="none" />
                            <path class="kiosk-check-mark" fill="none" d="M14 27l8 8 16-16" />
                        </svg>
                    </template>
                    <h2 class="k-title mt-4 font-extrabold tracking-tight">
                        {{ __('Terima kasih') }}, <span class="text-emerald-700" x-text="officer()?.nama"></span>!
                    </h2>
                    <p class="k-sub mt-3 text-slate-600">{{ __('Kehadiran anda telah disahkan.') }}</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <template x-for="card in seatCards()" :key="card.key">
                            <div class="min-w-[11rem] rounded-2xl px-6 py-4 text-left ring-1" :class="card.tint">
                                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.18em] opacity-80">
                                    <i :class="card.icon" aria-hidden="true"></i>
                                    <span x-text="card.label"></span>
                                </p>
                                <p class="k-seat mt-2 font-black tabular-nums" x-text="card.value"></p>
                            </div>
                        </template>
                    </div>
                    <p
                        x-show="officer() && !officer().rsvp"
                        class="mx-auto mt-6 max-w-2xl rounded-2xl bg-amber-50 px-5 py-3 text-[clamp(1rem,1.3vw,1.25rem)] font-medium text-amber-800 ring-1 ring-amber-200"
                    >
                        <i class="ri-megaphone-line mr-1" aria-hidden="true"></i>
                        {{ __('Sila tunggu nombor panggilan anda dipanggil oleh urus setia.') }}
                    </p>
                    @include('user::kiosk.partials.countdown', ['ring' => 'stroke-emerald-500'])
                </div>
            </section>

            {{-- Error --}}
            <section x-show="state === 'error'" x-transition.duration.250ms class="w-[min(56rem,100%)]">
                <div class="rounded-[2rem] bg-white p-[clamp(1.5rem,3vw,3rem)] text-center text-slate-900 shadow-2xl ring-1 ring-white/40 backdrop-blur">
                    <span class="mx-auto grid h-24 w-24 place-items-center rounded-full bg-rose-100 text-5xl text-rose-600">
                        <i :class="error?.icon ?? 'ri-error-warning-line'" aria-hidden="true"></i>
                    </span>
                    <h2 class="k-title mt-6 font-extrabold tracking-tight" x-text="error?.title"></h2>
                    <p class="k-sub mt-3 text-slate-600" x-text="error?.message"></p>
                    <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
                        <i class="ri-customer-service-2-line" aria-hidden="true"></i>
                        {{ __('Perlukan bantuan? Sila rujuk kaunter urus setia.') }}
                    </p>
                    @include('user::kiosk.partials.countdown', ['ring' => 'stroke-rose-500'])
                </div>
            </section>
        </main>

        {{-- Key legend (the confirm ticket shows its own Enter/Esc tiles) --}}
        <footer x-show="state !== 'confirm'" class="relative z-10 flex flex-wrap items-center justify-center gap-3 px-6 pb-[clamp(1rem,3vh,2rem)]">
            <template x-for="item in legend()" :key="item.label">
                <div class="flex items-center gap-2 rounded-full bg-white/10 py-1.5 pl-1.5 pr-4 text-sm text-white/90 ring-1 ring-white/15 backdrop-blur">
                    <template x-for="keyLabel in item.keys" :key="keyLabel">
                        <kbd class="kiosk-kbd" x-text="keyLabel"></kbd>
                    </template>
                    <span x-text="item.label"></span>
                </div>
            </template>
        </footer>

        <div class="sr-only" aria-live="assertive" x-text="announcement"></div>
    </div>

    @include('user::kiosk.partials.script')
</body>
</html>
