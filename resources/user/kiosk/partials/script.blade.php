<script>
    (() => {
        const L = {
            ready: @json(__('Sedia. Sila masukkan No. KP anda.')),
            searching: @json(__('Mencari maklumat anda…')),
            found: @json(__('Maklumat ditemui. Tekan Enter untuk sahkan atau Esc untuk batal.')),
            example: @json(__('Contoh: 900101-01-1234')),
            digitsLeft: @json(__(':n digit lagi')),
            pressEnter: @json(__('Tekan Enter untuk cari.')),
            mustBe12: @json(__('No. KP mesti 12 digit.')),
            escMore: @json(__('Tekan Esc :n kali lagi untuk keluar mod kiosk.')),
            exiting: @json(__('Keluar mod kiosk…')),
            alreadyTitle: @json(__('Anda telah direkodkan hadir')),
            successTitle: @json(__('Kehadiran anda telah disahkan.')),
            notFoundTitle: @json(__('No. KP tidak ditemui')),
            notFoundMessage: @json(__('Sila semak semula No. KP yang dimasukkan.')),
            invalidTitle: @json(__('No. KP tidak sah')),
            noSessionTitle: @json(__('Tiada sesi aktif')),
            noSessionMessage: @json(__('Pendaftaran belum dibuka. Sila tunggu sesi diaktifkan.')),
            mismatchTitle: @json(__('Sesi tidak sepadan')),
            mismatchMessage: @json(__('Anda berdaftar untuk sesi :pegawai tetapi sesi semasa adalah :sesi.')),
            morning: @json(__('pagi')),
            evening: @json(__('petang')),
            throttleTitle: @json(__('Terlalu banyak cubaan')),
            throttleMessage: @json(__('Sila tunggu sebentar sebelum mencuba lagi.')),
            networkTitle: @json(__('Ralat sambungan')),
            networkMessage: @json(__('Tidak dapat menghubungi pelayan. Sila cuba lagi.')),
            verifyTitle: @json(__('Kehadiran tidak dapat disahkan')),
            verifyMessage: @json(__('Ralat berlaku. Sila cuba lagi.')),
            returnNow: @json(__('Tekan Enter atau Esc untuk kembali sekarang.')),
            returnFix: @json(__('Enter: betulkan No. KP · Esc: kembali')),
            legendDigits: @json(__('Masukkan No. KP')),
            legendDelete: @json(__('Padam')),
            legendSearch: @json(__('Cari')),
            legendClear: @json(__('Kosongkan')),
            legendConfirm: @json(__('Sahkan kehadiran')),
            legendCancel: @json(__('Batal')),
            legendBack: @json(__('Kembali')),
            legendFix: @json(__('Betulkan No. KP')),
            seat: @json(__('No. Kerusi')),
            table: @json(__('No. Meja')),
            call: @json(__('No. Panggilan')),
            rsvpYes: @json(__('Berdaftar (RSVP)')),
            rsvpNo: @json(__('Tanpa RSVP')),
            jawatan: @json(__('Jawatan')),
            ptj: @json(__('PTJ')),
            sesi: @json(__('Sesi')),
            status: @json(__('Status')),
            retireDate: @json(__('Tarikh Bersara')),
            retireType: @json(__('Jenis Persaraan')),
            service: @json(__('Tempoh Berkhidmat')),
            autoCancel: @json(__('Batal automatik dalam :n saat')),
        };

        const IDLE_MS = 30000;
        const EXIT_WINDOW_MS = 2000;
        const RING = 100.53;
        const timeFormat = new Intl.DateTimeFormat('ms-MY', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        const dateFormat = new Intl.DateTimeFormat('ms-MY', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

        // Kept outside Alpine's reactive state: native objects/timers must not be proxied.
        let audioCtx = null;
        let flashTimer = null;

        function tone(freq, start, duration, type = 'sine', volume = 0.05) {
            if (!audioCtx) return;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            const t = audioCtx.currentTime + start;
            osc.type = type;
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(volume, t + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + duration);
            osc.connect(gain).connect(audioCtx.destination);
            osc.start(t);
            osc.stop(t + duration + 0.02);
        }

        const isBlank = (v) => v === null || v === undefined || v === '' || v === '-' || v === '—';

        window.kiosk = (init) => ({
            urls: init.urls,
            sessionName: init.sessionName,
            state: 'search',
            digits: [],
            result: null,
            receipt: null,
            error: null,
            busy: false,
            shaking: false,
            pressedKey: null,
            escPresses: [],
            announcement: '',
            now: Date.now(),
            lastActivity: Date.now(),
            deadline: null,
            countdownMs: 0,

            init() {
                window.addEventListener('keydown', (e) => this.onKey(e), true);
                ['contextmenu', 'paste', 'drop', 'dragstart', 'selectstart'].forEach((type) => {
                    window.addEventListener(type, (e) => e.preventDefault(), true);
                });
                // In fullscreen, keep Esc for the page (cancel) instead of leaving fullscreen.
                document.addEventListener('fullscreenchange', () => {
                    if (document.fullscreenElement && navigator.keyboard?.lock) {
                        navigator.keyboard.lock(['Escape']).catch(() => {});
                    }
                });
                setInterval(() => this.tick(), 250);
                this.announce(L.ready);
            },

            // ---- Keyboard: only digits, Enter, Esc and Backspace/Delete do anything ----
            normalizeKey(e) {
                if (e.ctrlKey || e.altKey || e.metaKey) return null;
                if (/^Numpad[0-9]$/.test(e.code)) return e.code.slice(-1);
                if (/^[0-9]$/.test(e.key)) return e.key;
                if (e.key === 'Enter') return 'Enter';
                if (e.key === 'Escape') return 'Escape';
                if (e.key === 'Backspace' || e.key === 'Delete' || e.code === 'NumpadDecimal') return 'Backspace';

                return null;
            },

            onKey(e) {
                e.preventDefault();
                e.stopPropagation();

                const key = this.normalizeKey(e);
                if (key === null) return;
                if (e.repeat && (key === 'Enter' || key === 'Escape')) return;

                this.lastActivity = Date.now();
                this.flash(key);
                this.ensureAudio();

                if (!(key === 'Escape' && this.state === 'search' && this.digits.length === 0)) {
                    this.enterFullscreen();
                }

                switch (this.state) {
                    case 'search':
                        this.onSearchKey(key);
                        break;
                    case 'confirm':
                        if (key === 'Enter') this.confirm();
                        if (key === 'Escape' && !this.busy) this.reset();
                        break;
                    case 'error':
                        if (key === 'Enter') this.reset(this.error?.keepDigits === true);
                        if (key === 'Escape') this.reset();
                        break;
                    case 'already':
                    case 'success':
                        if (key === 'Enter' || key === 'Escape') this.reset();
                        break;
                    default:
                        // loading: ignore input until the lookup settles
                        break;
                }
            },

            onSearchKey(key) {
                if (/^[0-9]$/.test(key)) {
                    this.escPresses = [];
                    if (this.digits.length >= 12) return;
                    this.digits.push(key);
                    this.sound('tick');
                    if (this.digits.length === 12) this.lookup();

                    return;
                }

                if (key === 'Backspace') {
                    this.escPresses = [];
                    this.digits.pop();

                    return;
                }

                if (key === 'Enter') {
                    if (this.digits.length === 12) {
                        this.lookup();
                    } else {
                        this.shake();
                    }

                    return;
                }

                if (key === 'Escape') {
                    if (this.digits.length) {
                        this.digits = [];
                        this.escPresses = [];

                        return;
                    }

                    const now = Date.now();
                    this.escPresses = [...this.escPresses.filter((t) => now - t < EXIT_WINDOW_MS), now];
                    if (this.escPresses.length >= 3) this.exitKiosk();
                }
            },

            // ---- Server calls ----
            async lookup() {
                if (this.busy) return;
                this.busy = true;
                this.state = 'loading';
                this.announce(L.searching);

                try {
                    const { data } = await window.axios.post(
                        this.urls.lookup,
                        { no_kp: this.digits.join('') },
                        { headers: { Accept: 'application/json' } },
                    );
                    this.result = data;
                    this.sessionName = data.active_sesi_name ?? null;
                    const officer = data.pegawai;

                    if (officer.is_attend) {
                        this.show('already', 8);
                        this.sound('found');
                        this.announce(L.alreadyTitle);

                        return;
                    }

                    if (data.active_sesi_s_kehadiran === null || data.active_sesi_s_kehadiran === undefined) {
                        this.fail({ title: L.noSessionTitle, message: L.noSessionMessage, icon: 'ri-calendar-close-line' });

                        return;
                    }

                    if (Number(officer.s_kehadiran) !== Number(data.active_sesi_s_kehadiran)) {
                        const label = (v) => (Number(v) === 0 ? L.morning : L.evening);
                        this.fail({
                            title: L.mismatchTitle,
                            message: L.mismatchMessage
                                .replace(':pegawai', label(officer.s_kehadiran))
                                .replace(':sesi', label(data.active_sesi_s_kehadiran)),
                            icon: 'ri-time-line',
                        });

                        return;
                    }

                    this.state = 'confirm';
                    this.sound('found');
                    this.announce(officer.nama + '. ' + L.found);
                } catch (e) {
                    const status = e.response?.status;
                    if (status === 404) {
                        this.fail({ title: L.notFoundTitle, message: L.notFoundMessage, icon: 'ri-user-unfollow-line', keepDigits: true });
                    } else if (status === 422) {
                        this.fail({ title: L.invalidTitle, message: e.response?.data?.message ?? L.mustBe12, icon: 'ri-error-warning-line', keepDigits: true });
                    } else if (status === 429) {
                        this.fail({ title: L.throttleTitle, message: L.throttleMessage, icon: 'ri-timer-line' });
                    } else {
                        this.fail({ title: L.networkTitle, message: L.networkMessage, icon: 'ri-wifi-off-line' });
                    }
                } finally {
                    this.busy = false;
                }
            },

            async confirm() {
                const officer = this.officer();
                if (this.busy || !officer) return;
                this.busy = true;

                try {
                    const { data } = await window.axios.put(
                        this.urls.verify.replace('__ID__', String(officer.id)),
                        { is_attend: 1 },
                        { headers: { Accept: 'application/json' } },
                    );
                    this.receipt = { no_meja: data.no_meja, no_panggilan_lewat: data.no_panggilan_lewat };
                    this.show('success', 8);
                    this.sound('success');
                    this.announce(officer.nama + '. ' + L.successTitle);
                } catch (e) {
                    this.fail({
                        title: L.verifyTitle,
                        message: e.response?.data?.message ?? L.verifyMessage,
                        icon: 'ri-close-circle-line',
                    });
                } finally {
                    this.busy = false;
                }
            },

            // ---- State helpers ----
            show(state, seconds) {
                this.state = state;
                this.countdownMs = seconds * 1000;
                this.deadline = Date.now() + this.countdownMs;
            },

            fail(error) {
                this.error = error;
                this.show('error', 6);
                this.sound('error');
                this.announce(error.title + '. ' + error.message);
            },

            reset(keepDigits = false) {
                this.state = 'search';
                this.result = null;
                this.receipt = null;
                this.error = null;
                this.deadline = null;
                this.escPresses = [];
                if (!keepDigits) this.digits = [];
                this.announce(L.ready);
            },

            tick() {
                this.now = Date.now();

                if (this.deadline && this.now >= this.deadline) {
                    this.reset();

                    return;
                }

                const idle = this.now - this.lastActivity > IDLE_MS;
                if (idle && ((this.state === 'search' && this.digits.length) || this.state === 'confirm') && !this.busy) {
                    this.reset();
                }

                const lastEsc = this.escPresses[this.escPresses.length - 1];
                if (lastEsc && this.now - lastEsc > EXIT_WINDOW_MS) this.escPresses = [];
            },

            shake() {
                this.shaking = false;
                this.sound('error');
                requestAnimationFrame(() => {
                    this.shaking = true;
                    setTimeout(() => (this.shaking = false), 1600);
                });
            },

            flash(key) {
                this.pressedKey = key;
                clearTimeout(flashTimer);
                flashTimer = setTimeout(() => (this.pressedKey = null), 160);
            },

            announce(text) {
                this.announcement = text;
            },

            enterFullscreen() {
                const root = document.documentElement;
                if (document.fullscreenElement || !root.requestFullscreen) return;
                root.requestFullscreen({ navigationUI: 'hide' }).catch(() => {});
            },

            exitKiosk() {
                this.announce(L.exiting);
                const go = () => {
                    window.location.href = this.urls.exit;
                };
                navigator.keyboard?.unlock?.();
                if (document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().then(go, go);
                } else {
                    go();
                }
            },

            ensureAudio() {
                if (audioCtx) return;
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    audioCtx = Ctx ? new Ctx() : null;
                } catch (e) {
                    audioCtx = null;
                }
            },

            sound(kind) {
                try {
                    if (kind === 'tick') tone(1200, 0, 0.05, 'sine', 0.025);
                    if (kind === 'found') {
                        tone(660, 0, 0.12);
                        tone(880, 0.1, 0.16);
                    }
                    if (kind === 'success') {
                        tone(660, 0, 0.14);
                        tone(880, 0.12, 0.14);
                        tone(1320, 0.24, 0.32);
                    }
                    if (kind === 'error') {
                        tone(220, 0, 0.18, 'square', 0.035);
                        tone(180, 0.18, 0.24, 'square', 0.035);
                    }
                } catch (e) {
                    // Sound is a nicety; never let it break the flow.
                }
            },

            // ---- View helpers ----
            step() {
                if (this.state === 'success') return 3;
                if (this.state === 'confirm' || this.state === 'already') return 2;
                if (this.state === 'error') return this.result ? 2 : 1;

                return 1;
            },

            slotTokens() {
                const tokens = [];
                for (let i = 0; i < 12; i++) {
                    if (i === 6 || i === 8) tokens.push({ key: 'sep' + i, sep: true });
                    tokens.push({
                        key: 'd' + i,
                        sep: false,
                        value: this.digits[i] ?? '',
                        caret: this.state === 'search' && i === this.digits.length,
                    });
                }

                return tokens;
            },

            searchHint() {
                if (this.state === 'loading') return L.searching;
                if (this.shaking) return L.mustBe12;
                if (this.escPresses.length) return L.escMore.replace(':n', String(3 - this.escPresses.length));
                if (this.digits.length === 12) return L.pressEnter;
                if (this.digits.length === 0) return L.example;

                return L.digitsLeft.replace(':n', String(12 - this.digits.length));
            },

            legend() {
                switch (this.state) {
                    case 'confirm':
                        return [
                            { keys: ['Enter'], label: L.legendConfirm },
                            { keys: ['Esc'], label: L.legendCancel },
                        ];
                    case 'already':
                    case 'success':
                        return [{ keys: ['Enter', 'Esc'], label: L.legendBack }];
                    case 'error':
                        return this.error?.keepDigits
                            ? [{ keys: ['Enter'], label: L.legendFix }, { keys: ['Esc'], label: L.legendBack }]
                            : [{ keys: ['Enter', 'Esc'], label: L.legendBack }];
                    default:
                        return [
                            { keys: ['0–9'], label: L.legendDigits },
                            { keys: ['⌫'], label: L.legendDelete },
                            { keys: ['Enter'], label: L.legendSearch },
                            { keys: ['Esc'], label: L.legendClear },
                        ];
                }
            },

            officer() {
                return this.result?.pegawai ?? null;
            },

            maskedIc() {
                const d = String(this.officer()?.no_kp ?? '').replace(/\D/g, '');
                if (d.length !== 12) return '';

                return `${d.slice(0, 6)}-••-••${d.slice(10)}`;
            },

            detailRows() {
                const o = this.officer();
                if (!o) return [];

                const rows = [
                    { key: 'jawatan', label: L.jawatan, value: o.jawatan_name },
                    { key: 'ptj', label: L.ptj, value: o.ptj_name },
                    { key: 'sesi', label: L.sesi, value: this.result.active_sesi_name ?? o.sesi_name },
                    {
                        key: 'rsvp',
                        label: L.status,
                        value: o.rsvp ? L.rsvpYes : L.rsvpNo,
                        icon: o.rsvp ? 'ri-checkbox-circle-fill' : 'ri-walk-line',
                        badge: o.rsvp ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200',
                    },
                ];

                if (this.result.event_mode === 'jasamu') {
                    rows.push(
                        { key: 'tarikh', label: L.retireDate, value: o.tarikh_bersara },
                        { key: 'bersara', label: L.retireType, value: o.bersara_name },
                        { key: 'tempoh', label: L.service, value: o.tempoh_berkhidmat },
                    );
                }

                return rows.filter((row) => !isBlank(row.value));
            },

            seatCards() {
                const o = this.officer();
                if (!o) return [];

                // tint: boxed cards (already/success); accent: number colour on the confirm ticket stub.
                const tones = {
                    emerald: { tint: 'bg-emerald-50 text-emerald-800 ring-emerald-200', accent: 'text-emerald-700' },
                    indigo: { tint: 'bg-indigo-50 text-indigo-800 ring-indigo-200', accent: 'text-indigo-700' },
                    amber: { tint: 'bg-amber-50 text-amber-800 ring-amber-200', accent: 'text-amber-600' },
                };
                const meja = this.receipt ? this.receipt.no_meja : o.no_meja;
                const lewat = this.receipt ? this.receipt.no_panggilan_lewat : o.no_panggilan_lewat;
                const hasLate = !isBlank(lewat) && Number(lewat) > 0;
                const cards = [];

                if (o.rsvp) {
                    cards.push({ key: 'kerusi', ...tones.emerald, icon: 'ri-armchair-line', label: L.seat, value: isBlank(o.no_kerusi) ? '-' : o.no_kerusi });
                    if (this.result.show_table_number) {
                        cards.push({ key: 'meja', ...tones.indigo, icon: 'ri-table-line', label: L.table, value: isBlank(meja) ? '-' : meja });
                    }
                    if (hasLate) {
                        cards.push({ key: 'lewat', ...tones.amber, icon: 'ri-megaphone-line', label: L.call, value: lewat });
                    }
                } else {
                    cards.push({ key: 'lewat', ...tones.amber, icon: 'ri-megaphone-line', label: L.call, value: hasLate ? lewat : '-' });
                }

                return cards;
            },

            secondsLeft() {
                return this.deadline ? Math.max(0, Math.ceil((this.deadline - this.now) / 1000)) : 0;
            },

            ringOffset() {
                if (!this.deadline || !this.countdownMs) return 0;
                const remaining = Math.max(0, this.deadline - this.now);

                return RING * (1 - remaining / this.countdownMs);
            },

            // Confirm screen auto-cancels after IDLE_MS without a key press (see tick()).
            confirmSecondsLeft() {
                return Math.max(0, Math.ceil((this.lastActivity + IDLE_MS - this.now) / 1000));
            },

            confirmProgress() {
                const remaining = Math.max(0, this.lastActivity + IDLE_MS - this.now);

                return Math.min(100, (remaining / IDLE_MS) * 100);
            },

            confirmHint() {
                return L.autoCancel.replace(':n', String(this.confirmSecondsLeft()));
            },

            returnHint() {
                return this.state === 'error' && this.error?.keepDigits ? L.returnFix : L.returnNow;
            },

            clockTime() {
                return timeFormat.format(this.now);
            },

            clockDate() {
                return dateFormat.format(this.now);
            },
        });
    })();
</script>
