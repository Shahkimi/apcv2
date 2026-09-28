<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Presentasi Kehadiran') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        .transition-content { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }

        /*
         * Officer name — set any size in px (e.g. 50px). Edit only the numbers below.
         * - default: phones / narrow
         * - sm:      from 640px width up
         * - md:      from 768px width up
         */
        :root {
            --officer-name-font-size: {{ (int) $displaySettings['fonts']['name_base'] }}px;
            --officer-name-font-size-sm: {{ (int) $displaySettings['fonts']['name_sm'] }}px;
            --officer-name-font-size-md: {{ (int) $displaySettings['fonts']['name_md'] }}px;
            /*
             * Jawatan line — same breakpoint idea as officer name; default 30px.
             */
            --officer-jawatan-font-size: {{ (int) $displaySettings['fonts']['jawatan_base'] }}px;
            --officer-jawatan-font-size-sm: {{ (int) $displaySettings['fonts']['jawatan_sm'] }}px;
            --officer-jawatan-font-size-md: {{ (int) $displaySettings['fonts']['jawatan_md'] }}px;
            --officer-ptj-font-size: {{ (int) $displaySettings['fonts']['ptj_base_px'] }}px;
            --officer-ptj-font-size-sm: {{ (int) $displaySettings['fonts']['ptj_sm_px'] }}px;
            /*
             * Jasamu-only lines (tarikh bersara / bersara / tempoh berkhidmat) — same breakpoint idea.
             */
            --officer-tarikh-font-size: {{ (int) $displaySettings['fonts']['tarikh_base'] }}px;
            --officer-tarikh-font-size-sm: {{ (int) $displaySettings['fonts']['tarikh_sm'] }}px;
            --officer-tarikh-font-size-md: {{ (int) $displaySettings['fonts']['tarikh_md'] }}px;
            --officer-bersara-font-size: {{ (int) $displaySettings['fonts']['bersara_base'] }}px;
            --officer-bersara-font-size-sm: {{ (int) $displaySettings['fonts']['bersara_sm'] }}px;
            --officer-bersara-font-size-md: {{ (int) $displaySettings['fonts']['bersara_md'] }}px;
            --officer-tempoh-font-size: {{ (int) $displaySettings['fonts']['tempoh_base'] }}px;
            --officer-tempoh-font-size-sm: {{ (int) $displaySettings['fonts']['tempoh_sm'] }}px;
            --officer-tempoh-font-size-md: {{ (int) $displaySettings['fonts']['tempoh_md'] }}px;
            --officer-mt-base: {{ $displaySettings['position']['mt_base'] }};
            --officer-mt-sm: {{ $displaySettings['position']['mt_sm'] }};
            --officer-mt-md: {{ $displaySettings['position']['mt_md'] }};
            --officer-translate-y: {{ $displaySettings['position']['translate_y'] }};
        }

        #officer-name {
            font-size: var(--officer-name-font-size);
            font-weight: 700;
            color: #fff;
            line-height: 1.1;
        }

        #officer-jawatan {
            font-size: var(--officer-jawatan-font-size);
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.2;
        }

        .officer-line {
            font-weight: 500;
            color: #fff;
            line-height: 1.2;
        }

        #officer-ptj {
            font-size: var(--officer-ptj-font-size);
        }

        #officer-tarikh {
            font-size: var(--officer-tarikh-font-size);
        }

        #officer-bersara {
            font-size: var(--officer-bersara-font-size);
        }

        #officer-tempoh {
            font-size: var(--officer-tempoh-font-size);
        }

        .officer-display-wrap {
            margin-top: var(--officer-mt-base);
            transform: translateY(var(--officer-translate-y));
        }

        @media (min-width: 640px) {
            #officer-name {
                font-size: var(--officer-name-font-size-sm);
            }

            #officer-jawatan {
                font-size: var(--officer-jawatan-font-size-sm);
            }

            #officer-ptj {
                font-size: var(--officer-ptj-font-size-sm);
            }

            #officer-tarikh {
                font-size: var(--officer-tarikh-font-size-sm);
            }

            #officer-bersara {
                font-size: var(--officer-bersara-font-size-sm);
            }

            #officer-tempoh {
                font-size: var(--officer-tempoh-font-size-sm);
            }

            .officer-display-wrap {
                margin-top: var(--officer-mt-sm);
            }
        }

        @media (min-width: 768px) {
            #officer-name {
                font-size: var(--officer-name-font-size-md);
            }

            #officer-jawatan {
                font-size: var(--officer-jawatan-font-size-md);
            }

            #officer-tarikh {
                font-size: var(--officer-tarikh-font-size-md);
            }

            #officer-bersara {
                font-size: var(--officer-bersara-font-size-md);
            }

            #officer-tempoh {
                font-size: var(--officer-tempoh-font-size-md);
            }

            .officer-display-wrap {
                margin-top: var(--officer-mt-md);
            }
        }
    </style>
</head>
<body class="overflow-hidden bg-slate-900">
    <div id="presentation-container"
         class="relative flex h-screen w-screen items-center justify-center bg-cover bg-center bg-no-repeat text-white"
    @if ($backdrop?->file_path)
         style="background-image: url('{{ e($backdrop->image_url) }}');"
    @else
         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);"
    @endif
    >
        <div class="officer-display-wrap relative z-10 w-full max-w-6xl px-4 text-center sm:px-12">
            <div id="officer-display" class="transition-content space-y-1 sm:space-y-2">
                <h1 id="officer-name" class="transition-content"></h1>
                <p id="officer-jawatan" class="transition-content"></p>
                @if ($isJasamu)
                    <p id="officer-tarikh" class="officer-line transition-content"></p>
                    <p id="officer-bersara" class="officer-line transition-content"></p>
                    <p id="officer-tempoh" class="officer-line transition-content"></p>
                @else
                    <p id="officer-ptj" class="officer-line"></p>
                @endif
            </div>
        </div>
    </div>

    <script>
        const officers = @json($officerSlides);
        const setupUrl = @json(route('media.senarai.index'));
        const progressReadUrl = @json(route('media.senarai.progress.show'));
        const progressUpdateUrl = @json(route('media.senarai.progress.update'));
        const sesiId = @json($sesiId);
        const isJasamu = @json($isJasamu);
        const extraLines = isJasamu
            ? [
                ['officer-tarikh', (o) => `{{ __('Tarikh bersara') }}: ${o.tarikh_bersara}`],
                ['officer-bersara', (o) => `{{ __('Bersara') }}: ${o.bersara}`],
                ['officer-tempoh', (o) => `{{ __('Tempoh berkhidmat') }}: ${o.tempoh}`],
            ]
            : [['officer-ptj', (o) => o.ptj]];
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const storageKey = `senarai_position_${sesiId ?? 'all'}`;

        const NAV_MIN_MS = 250;
        const SYNC_COOLDOWN_MS = 8000;

        let currentIndex = 0;
        let displayGeneration = 0;
        let navSeq = 0;
        let lastUserNavAt = 0;
        let lastNavAt = 0;
        let pendingSaves = 0;
        let saveChain = Promise.resolve();
        let syncInFlight = false;
        let lastWriteUpdatedAt = null;
        const totalOfficers = officers.length;

        if (totalOfficers === 0) {
            window.location.href = setupUrl;
        }

        const nameEl = document.getElementById('officer-name');
        const jawatanEl = document.getElementById('officer-jawatan');
        const extraEls = extraLines.map(([id]) => document.getElementById(id)).filter(Boolean);

        function fillExtras(officer) {
            extraLines.forEach(([id, getText]) => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = getText(officer);
                }
            });
        }

        function normalizeIndex(index) {
            if (totalOfficers <= 0) {
                return 0;
            }

            return Math.max(0, Math.min(index, totalOfficers - 1));
        }

        function saveProgress(index) {
            const officer = officers[index];
            if (!officer || officer.id == null) {
                return;
            }

            localStorage.setItem(storageKey, String(index));

            const payload = {
                index,
                pegawai_id: officer.id,
            };

            if (sesiId != null) {
                payload.sesi_id = sesiId;
            }

            pendingSaves += 1;
            saveChain = saveChain
                .then(function () {
                    return fetch(progressUpdateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });
                })
                .then(function (response) {
                    return response && response.ok ? response.json() : null;
                })
                .then(function (data) {
                    if (data && data.last_updated_at) {
                        lastWriteUpdatedAt = data.last_updated_at;
                    }
                })
                .catch(function () {
                    /* localStorage still preserves position when network is unstable */
                })
                .finally(function () {
                    pendingSaves -= 1;
                });
        }

        function showOfficer(index, options = {}) {
            if (totalOfficers === 0) return;

            displayGeneration += 1;
            const gen = displayGeneration;

            const persist = options.persist ?? true;
            const animate = options.animate ?? true;
            const normalizedIndex = normalizeIndex(index);

            currentIndex = normalizedIndex;
            const officer = officers[currentIndex];

            if (persist) {
                navSeq += 1;
                lastUserNavAt = Date.now();
            }

            if (!animate) {
                nameEl.textContent = officer.nama;
                jawatanEl.textContent = officer.jawatan;
                fillExtras(officer);
                nameEl.style.opacity = '1';
                jawatanEl.style.opacity = '1';
                nameEl.style.transform = 'translateY(0)';
                jawatanEl.style.transform = 'translateY(0)';
                extraEls.forEach(function(el) {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                });
            } else {
                nameEl.style.opacity = '0';
                nameEl.style.transform = 'translateY(20px)';
                jawatanEl.style.opacity = '0';
                jawatanEl.style.transform = 'translateY(20px)';
                extraEls.forEach(function(el) {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                });

                setTimeout(function() {
                    if (gen !== displayGeneration) {
                        return;
                    }

                    nameEl.textContent = officer.nama;
                    jawatanEl.textContent = officer.jawatan;
                    fillExtras(officer);

                    nameEl.style.opacity = '1';
                    nameEl.style.transform = 'translateY(0)';

                    setTimeout(function() {
                        if (gen !== displayGeneration) {
                            return;
                        }

                        jawatanEl.style.opacity = '1';
                        jawatanEl.style.transform = 'translateY(0)';
                    }, 120);

                    setTimeout(function() {
                        if (gen !== displayGeneration) {
                            return;
                        }

                        extraEls.forEach(function(el) {
                            el.style.opacity = '1';
                            el.style.transform = 'translateY(0)';
                        });
                    }, 240);
                }, 250);
            }

            if (persist) {
                saveProgress(currentIndex);
            }
        }

        async function syncFromServer(options = {}) {
            const force = options.force ?? false;

            if (syncInFlight || pendingSaves > 0 || document.hidden) {
                return null;
            }

            if (!force && Date.now() - lastUserNavAt < SYNC_COOLDOWN_MS) {
                return null;
            }

            const seqAtStart = navSeq;
            syncInFlight = true;

            const query = sesiId != null ? `?sesi_id=${encodeURIComponent(String(sesiId))}` : '';

            try {
                const response = await fetch(`${progressReadUrl}${query}`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                if (!response.ok) {
                    return null;
                }

                const data = await response.json();

                if (navSeq !== seqAtStart || pendingSaves > 0) {
                    return null;
                }

                if (lastWriteUpdatedAt && data.last_updated_at && data.last_updated_at <= lastWriteUpdatedAt) {
                    return null;
                }

                const serverIndex = normalizeIndex(Number(data.current_index ?? 0));
                if (serverIndex !== currentIndex) {
                    currentIndex = serverIndex;
                    localStorage.setItem(storageKey, String(serverIndex));
                    showOfficer(serverIndex, { persist: false, animate: true });
                }

                return data;
            } catch (err) {
                /* keep local progress when network fails */
                return null;
            } finally {
                syncInFlight = false;
            }
        }

        async function loadInitialPosition() {
            const storedValue = localStorage.getItem(storageKey);
            if (storedValue !== null && storedValue !== '') {
                showOfficer(normalizeIndex(Number(storedValue)), { persist: false, animate: false });
            } else {
                showOfficer(0, { persist: false, animate: false });
            }

            const serverData = await syncFromServer({ force: true });

            if (!serverData || !serverData.last_updated_at) {
                saveProgress(currentIndex);
            }
        }

        function exitFullscreenIfActive() {
            const doc = document;

            if (!doc.fullscreenElement && !doc.webkitFullscreenElement && !doc.msFullscreenElement) {
                return Promise.resolve();
            }

            if (doc.exitFullscreen) {
                return doc.exitFullscreen().catch(function () {});
            }

            if (doc.webkitExitFullscreen) {
                try {
                    doc.webkitExitFullscreen();
                } catch (err) {
                    /* ignore */
                }

                return Promise.resolve();
            }

            if (doc.msExitFullscreen) {
                try {
                    doc.msExitFullscreen();
                } catch (err) {
                    /* ignore */
                }

                return Promise.resolve();
            }

            return Promise.resolve();
        }

        (function autoEnterFullscreenForPresentation() {
            if (totalOfficers === 0) {
                return;
            }

            const root = document.documentElement;

            function requestFullscreenOnRoot() {
                if (document.fullscreenElement != null || document.webkitFullscreenElement != null || document.msFullscreenElement != null) {
                    return Promise.resolve();
                }

                if (root.requestFullscreen) {
                    return root.requestFullscreen().catch(function () {});
                }

                if (root.webkitRequestFullscreen) {
                    try {
                        root.webkitRequestFullscreen();
                    } catch (err) {
                        /* ignore */
                    }

                    return Promise.resolve();
                }

                if (root.msRequestFullscreen) {
                    try {
                        root.msRequestFullscreen();
                    } catch (err) {
                        /* ignore */
                    }

                    return Promise.resolve();
                }

                return Promise.resolve();
            }

            /* Fullscreen from the setup page is lost after navigation; Chrome needs a fresh user gesture on this document. */
            requestFullscreenOnRoot();

            function detachGestureListeners() {
                document.removeEventListener('pointerdown', onPointerDownForFullscreen, true);
                document.removeEventListener('keydown', onKeyDownForFullscreen, true);
            }

            function onPointerDownForFullscreen() {
                detachGestureListeners();
                requestFullscreenOnRoot();
            }

            function onKeyDownForFullscreen(e) {
                if (e.key === 'Escape') {
                    return;
                }
                detachGestureListeners();
                requestFullscreenOnRoot();
            }

            document.addEventListener('pointerdown', onPointerDownForFullscreen, true);
            document.addEventListener('keydown', onKeyDownForFullscreen, true);
        })();

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                exitFullscreenIfActive().finally(function () {
                    window.location.href = setupUrl;
                });
                return;
            }

            const isNavKey = e.key === 'ArrowRight' || e.key === 'ArrowLeft' || e.key === 'Home' || e.key === 'End';
            if (!isNavKey) {
                return;
            }

            if (Date.now() - lastNavAt < NAV_MIN_MS) {
                e.preventDefault();
                return;
            }

            if (e.key === 'ArrowRight' && currentIndex < totalOfficers - 1) {
                e.preventDefault();
                lastNavAt = Date.now();
                showOfficer(currentIndex + 1);
            } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                e.preventDefault();
                lastNavAt = Date.now();
                showOfficer(currentIndex - 1);
            } else if (e.key === 'Home' && totalOfficers > 0) {
                e.preventDefault();
                lastNavAt = Date.now();
                showOfficer(0);
            } else if (e.key === 'End' && totalOfficers > 0) {
                e.preventDefault();
                lastNavAt = Date.now();
                showOfficer(totalOfficers - 1);
            }
        });

        setInterval(syncFromServer, 5000);
        loadInitialPosition();
    </script>
</body>
</html>
