<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Presentasi Kehadiran') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        .transition-content { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }

        /*
         * Officer name — set any size in px (e.g. 50px). Edit only the numbers below.
         * - default: phones / narrow
         * - sm:      from 640px width up
         * - md:      from 768px width up
         */
        :root {
            --officer-name-font-size: 36px;
            --officer-name-font-size-sm: 44px;
            --officer-name-font-size-md: 52px;
            /*
             * Jawatan line — same breakpoint idea as officer name; default 30px.
             */
            --officer-jawatan-font-size: 30px;
            --officer-jawatan-font-size-sm: 38px;
            --officer-jawatan-font-size-md: 46px;
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

        @media (min-width: 640px) {
            #officer-name {
                font-size: var(--officer-name-font-size-sm);
            }

            #officer-jawatan {
                font-size: var(--officer-jawatan-font-size-sm);
            }
        }

        @media (min-width: 768px) {
            #officer-name {
                font-size: var(--officer-name-font-size-md);
            }

            #officer-jawatan {
                font-size: var(--officer-jawatan-font-size-md);
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
        <div class="relative z-10 mt-[30vh] w-full max-w-6xl px-4 text-center sm:mt-[34vh] sm:px-12 md:mt-[40vh]">
            <div id="officer-display" class="transition-content space-y-1 sm:space-y-2">
                <h1 id="officer-name" class="transition-content"></h1>
                <p id="officer-jawatan" class="transition-content"></p>
                <p id="officer-ptj" class="text-2xl font-medium text-white sm:text-4xl"></p>
            </div>
        </div>
    </div>

    <script>
        const officers = @json($officerSlides);
        const setupUrl = @json(route('media.senarai.index'));

        let currentIndex = 0;
        const totalOfficers = officers.length;

        if (totalOfficers === 0) {
            window.location.href = setupUrl;
        }

        const nameEl = document.getElementById('officer-name');
        const jawatanEl = document.getElementById('officer-jawatan');
        const ptjEl = document.getElementById('officer-ptj');

        function showOfficer(index) {
            if (totalOfficers === 0) return;
            if (index < 0) index = 0;
            if (index >= totalOfficers) index = totalOfficers - 1;

            currentIndex = index;
            const officer = officers[currentIndex];

            nameEl.style.opacity = '0';
            nameEl.style.transform = 'translateY(20px)';
            jawatanEl.style.opacity = '0';
            jawatanEl.style.transform = 'translateY(20px)';
            ptjEl.style.opacity = '0';
            ptjEl.style.transform = 'translateY(20px)';

            setTimeout(function() {
                nameEl.textContent = officer.nama;
                jawatanEl.textContent = officer.jawatan;
                ptjEl.textContent = officer.ptj;

                nameEl.style.opacity = '1';
                nameEl.style.transform = 'translateY(0)';

                setTimeout(function() {
                    jawatanEl.style.opacity = '1';
                    jawatanEl.style.transform = 'translateY(0)';
                }, 120);

                setTimeout(function() {
                    ptjEl.style.opacity = '1';
                    ptjEl.style.transform = 'translateY(0)';
                }, 240);
            }, 250);
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
            if (e.key === 'ArrowRight' && currentIndex < totalOfficers - 1) {
                e.preventDefault();
                showOfficer(currentIndex + 1);
            } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                e.preventDefault();
                showOfficer(currentIndex - 1);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                exitFullscreenIfActive().finally(function () {
                    window.location.href = setupUrl;
                });
            } else if (e.key === 'Home' && totalOfficers > 0) {
                e.preventDefault();
                showOfficer(0);
            } else if (e.key === 'End' && totalOfficers > 0) {
                e.preventDefault();
                showOfficer(totalOfficers - 1);
            }
        });

        if (totalOfficers > 0) {
            showOfficer(0);
        }
    </script>
</body>
</html>
