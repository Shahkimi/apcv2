<x-dashboard-layout :title="__('Sistem')" role="admin">
    <x-crud-header
        :title="__('Tetapan sistem')"
        :description="__('Tindakan pentadbiran yang memberi kesan kepada semua rekod pegawai. Gunakan dengan berhati-hati.')"
        :show-create="false"
    />

    @php
        $wipeItems = [
            ['key' => 'pegawai', 'label' => __('Pegawai'), 'icon' => 'ri-team-line', 'note' => __('Termasuk status kehadiran, nombor meja/panggilan & senarai diumumkan.')],
            ['key' => 'jawatan', 'label' => __('Jawatan'), 'icon' => 'ri-briefcase-line', 'note' => __('Boleh diimport semula di Kawalan › Import pangkalan.')],
            ['key' => 'gred', 'label' => __('Gred'), 'icon' => 'ri-medal-line', 'note' => __('Boleh diimport semula di Kawalan › Import pangkalan.')],
        ];
        $pageInit = [
            'overview' => $overview,
            'urls' => [
                'reset' => route('admin.kawalan.system.reset'),
                'wipe' => route('admin.kawalan.system.wipe'),
            ],
            'labels' => [
                'pegawai' => __('Pegawai'),
                'jawatan' => __('Jawatan'),
                'gred' => __('Gred'),
                'records' => __('rekod'),
                'resetPegawai' => __('Rekod pegawai dikemas kini'),
                'resetHadir' => __('Status hadir dikosongkan'),
                'resetLewat' => __('Status lewat dikosongkan'),
                'resetAnnounced' => __('Senarai diumumkan dikosongkan'),
                'pick' => __('Pilih data yang hendak dipadam.'),
                'nothing' => __('Tiada rekod untuk dipadam dalam pilihan ini.'),
                'willDelete' => __(':n rekod akan dipadam'),
                'ok' => __('Berjaya.'),
                'resetFail' => __('Ralat semasa menetapkan semula. Sila cuba lagi.'),
                'wipeFail' => __('Ralat semasa memadam data. Sila cuba lagi.'),
            ],
        ];
    @endphp

    <div class="mx-auto max-w-4xl" x-data="systemPage(@js($pageInit))" @keydown.escape.window="closeModal()">
        <div class="space-y-8">
            {{-- Danger zone --}}
            <section aria-labelledby="system-danger-title">
                <div class="mb-3">
                    <h2 id="system-danger-title" class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-destructive">
                        <i class="ri-alarm-warning-line text-base" aria-hidden="true"></i>
                        {{ __('Zon bahaya') }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ __('Tindakan di bawah memberi kesan kepada semua rekod dan tidak boleh diundur.') }}</p>
                </div>

                <div class="divide-y divide-border overflow-hidden rounded-xl border border-red-200 bg-card shadow-sm dark:border-red-900">
                    {{-- Reset attendance --}}
                    <div class="flex flex-col gap-4 p-5 md:flex-row md:items-start md:justify-between">
                        <div class="flex min-w-0 gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                <i class="ri-restart-line text-xl" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-foreground">{{ __('Tetap semula kehadiran') }}</h3>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ __('Kosongkan status kehadiran semua pegawai untuk memulakan majlis semula. Data pegawai, jawatan dan gred kekal.') }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-border px-2.5 py-1 text-muted-foreground">
                                        <i class="ri-user-follow-line" aria-hidden="true"></i>
                                        <span><b class="font-semibold text-foreground" x-text="fmt(stats.hadir)">{{ number_format($overview['hadir']) }}</b> {{ __('hadir') }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-border px-2.5 py-1 text-muted-foreground">
                                        <i class="ri-time-line" aria-hidden="true"></i>
                                        <span><b class="font-semibold text-foreground" x-text="fmt(stats.lewat)">{{ number_format($overview['lewat']) }}</b> {{ __('lewat') }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-border px-2.5 py-1 text-muted-foreground">
                                        <i class="ri-megaphone-line" aria-hidden="true"></i>
                                        <span><b class="font-semibold text-foreground" x-text="fmt(stats.diumumkan)">{{ number_format($overview['diumumkan']) }}</b> {{ __('diumumkan') }}</span>
                                    </span>
                                </div>
                                <details class="group mt-3">
                                    <summary class="inline-flex cursor-pointer select-none items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground">
                                        <i class="ri-arrow-right-s-line text-base transition-transform group-open:rotate-90" aria-hidden="true"></i>
                                        {{ __('Apa yang ditetapkan semula?') }}
                                    </summary>
                                    <ul class="mt-2 list-disc space-y-1 pl-9 text-xs text-muted-foreground">
                                        <li>{{ __('Pautan sesi majlis pegawai dikosongkan.') }}</li>
                                        <li>{{ __('No. meja dan no. panggilan lewat ditetapkan kepada 0.') }}</li>
                                        <li>{{ __('Status hadir, status lewat dan masa hadir dikosongkan.') }}</li>
                                        <li>{{ __('Senarai pegawai yang telah diumumkan dikosongkan.') }}</li>
                                    </ul>
                                </details>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-destructive bg-card px-4 py-2.5 text-sm font-semibold text-destructive shadow-sm transition hover:bg-destructive hover:text-destructive-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50"
                            @click="openModal('reset')"
                            :disabled="busy || !stats.pegawai"
                        >
                            <i class="ri-restart-line text-base" aria-hidden="true"></i>
                            {{ __('Tetap semula') }}
                        </button>
                    </div>

                    {{-- Wipe data --}}
                    <div class="p-5">
                        <div class="flex gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-300">
                                <i class="ri-delete-bin-6-line text-xl" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-foreground">{{ __('Padam data pegawai & rujukan') }}</h3>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ __('Kosongkan jadual yang dipilih untuk majlis baharu. ID bermula semula dari 1. PTJ, Bersara, sesi majlis dan tetapan tidak terjejas.') }}
                                </p>
                            </div>
                        </div>

                        <fieldset class="mt-4 grid gap-3 sm:grid-cols-3 md:pl-[3.75rem]" :disabled="busy">
                            <legend class="sr-only">{{ __('Pilih data untuk dipadam') }}</legend>
                            @foreach ($wipeItems as $item)
                                <label
                                    class="flex h-full flex-col rounded-xl border p-4 transition"
                                    :class="isWiping(@js($item['key'])) ? 'border-destructive bg-destructive/10 ring-1 ring-destructive' : 'border-border'"
                                    :style="isLocked(@js($item['key'])) ? 'cursor: not-allowed' : 'cursor: pointer'"
                                >
                                    <span class="flex items-start justify-between gap-2">
                                        <span class="inline-flex items-center gap-2 text-sm font-semibold text-foreground">
                                            <i class="{{ $item['icon'] }} text-base text-muted-foreground" aria-hidden="true"></i>
                                            {{ $item['label'] }}
                                        </span>
                                        <input
                                            type="checkbox"
                                            class="mt-0.5 h-4 w-4 rounded border-border bg-background text-destructive focus:ring-destructive"
                                            :checked="isWiping(@js($item['key']))"
                                            :disabled="isLocked(@js($item['key']))"
                                            @change="toggleWipe(@js($item['key']))"
                                        />
                                    </span>
                                    <span class="mt-3 flex items-baseline gap-1.5">
                                        <span class="text-2xl font-bold tabular-nums text-foreground" x-text="fmt(stats[@js($item['key'])])">{{ number_format($overview[$item['key']]) }}</span>
                                        <span class="text-xs text-muted-foreground">{{ __('rekod') }}</span>
                                    </span>
                                    <span class="mt-2 text-xs leading-snug text-muted-foreground">{{ $item['note'] }}</span>
                                    @if ($item['key'] === 'pegawai')
                                        <span x-show="isLocked('pegawai')" style="display: none" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-destructive">
                                            <i class="ri-lock-line" aria-hidden="true"></i>
                                            {{ __('Wajib — bergantung pada Jawatan/Gred') }}
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </fieldset>

                        <div class="mt-4 flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between md:ml-[3.75rem]">
                            <p class="text-sm" :class="wipeTotal() > 0 ? 'font-medium text-destructive' : 'text-muted-foreground'" x-text="wipeSummary()" aria-live="polite"></p>
                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-destructive px-4 py-2.5 text-sm font-semibold text-destructive-foreground shadow-sm transition hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50"
                                @click="openModal('wipe')"
                                :disabled="!canWipe()"
                            >
                                <i class="ri-delete-bin-6-line text-base" aria-hidden="true"></i>
                                {{ __('Padam data dipilih') }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        {{-- Confirmation modal (shared by reset and wipe) --}}
        <div
            x-show="modal"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
            style="display: none"
            role="dialog"
            aria-modal="true"
            aria-labelledby="system-modal-title"
        >
            <div
                @click.outside="closeModal()"
                class="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
            >
                <div class="flex items-start gap-4">
                    <span
                        class="grid h-11 w-11 shrink-0 place-items-center rounded-full"
                        :class="modal === 'wipe' ? 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'"
                    >
                        <i class="text-xl" :class="modal === 'wipe' ? 'ri-delete-bin-6-line' : 'ri-restart-line'" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 id="system-modal-title" class="text-lg font-semibold text-foreground">
                            <span x-show="modal === 'reset'">{{ __('Tetap semula kehadiran?') }}</span>
                            <span x-show="modal === 'wipe'">{{ __('Padam data dipilih?') }}</span>
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            <span x-show="modal === 'reset'">{{ __('Semua pegawai akan kembali ke status belum hadir.') }}</span>
                            <span x-show="modal === 'wipe'">{{ __('Rekod berikut akan dipadam sepenuhnya.') }}</span>
                        </p>
                    </div>
                </div>

                <ul class="mt-4 divide-y divide-border rounded-lg border border-border">
                    <template x-for="row in modalRows()" :key="row.key">
                        <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <span class="text-foreground" x-text="row.label"></span>
                            <span class="font-semibold tabular-nums" :class="modal === 'wipe' ? 'text-destructive' : 'text-foreground'" x-text="row.value"></span>
                        </li>
                    </template>
                </ul>

                <div class="mt-3 space-y-2 text-xs text-muted-foreground" x-show="modal === 'wipe'">
                    <p x-show="wipeKeys().includes('pegawai')">{{ __('Status kehadiran, nombor meja/panggilan dan senarai pegawai diumumkan turut dikosongkan.') }}</p>
                </div>

                @if ($activeSesi)
                    <p class="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 p-2.5 text-xs text-amber-900 dark:bg-amber-950 dark:text-amber-100">
                        <i class="ri-broadcast-line mt-px" aria-hidden="true"></i>
                        {{ __('Sesi ":sesi" sedang aktif.', ['sesi' => $activeSesi]) }}
                    </p>
                @endif

                <p class="mt-3 flex items-center gap-1.5 text-xs font-medium text-destructive">
                    <i class="ri-error-warning-line" aria-hidden="true"></i>
                    {{ __('Tindakan ini tidak boleh diundur.') }}
                </p>

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" class="btn btn-outline" x-ref="cancelBtn" @click="closeModal()" :disabled="busy">
                        {{ __('Batal') }}
                    </button>
                    <button
                        type="button"
                        class="btn bg-destructive text-destructive-foreground hover:opacity-90"
                        @click="confirm()"
                        :disabled="busy"
                    >
                        <span x-show="!busy">
                            <span x-show="modal === 'reset'">{{ __('Ya, tetap semula') }}</span>
                            <span x-show="modal === 'wipe'">{{ __('Ya, padam') }}</span>
                        </span>
                        <span x-show="busy" class="inline-flex items-center gap-2">
                            <i class="ri-loader-4-line animate-spin" aria-hidden="true"></i>
                            {{ __('Memproses…') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast --}}
        <div
            x-show="toast.text !== ''"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-6 right-6 z-[60] flex max-w-sm items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-lg"
            :class="toast.ok
                ? 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100'
                : 'border-red-200 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100'"
            style="display: none"
            role="status"
            aria-live="polite"
        >
            <i class="mt-0.5 text-base" :class="toast.ok ? 'ri-checkbox-circle-fill text-emerald-600' : 'ri-error-warning-fill text-red-600'" aria-hidden="true"></i>
            <span x-text="toast.text"></span>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                // Kept outside Alpine's reactive state.
                let toastTimer = null;

                Alpine.data('systemPage', (init) => ({
                    stats: init.overview,
                    urls: init.urls,
                    L: init.labels,
                    modal: null,
                    busy: false,
                    selected: [],
                    toast: { text: '', ok: true },

                    fmt(n) {
                        return Number(n ?? 0).toLocaleString('ms-MY');
                    },

                    // Jawatan/Gred cascade to pegawai, so pegawai is forced (and locked) when either is picked.
                    isLocked(key) {
                        return key === 'pegawai' && (this.selected.includes('jawatan') || this.selected.includes('gred'));
                    },

                    isWiping(key) {
                        return this.selected.includes(key) || this.isLocked(key);
                    },

                    toggleWipe(key) {
                        if (this.isLocked(key)) return;
                        this.selected = this.selected.includes(key)
                            ? this.selected.filter((k) => k !== key)
                            : [...this.selected, key];
                    },

                    wipeKeys() {
                        return ['pegawai', 'jawatan', 'gred'].filter((k) => this.isWiping(k));
                    },

                    wipeTotal() {
                        return this.wipeKeys().reduce((sum, k) => sum + (this.stats[k] ?? 0), 0);
                    },

                    canWipe() {
                        return !this.busy && this.wipeTotal() > 0;
                    },

                    wipeSummary() {
                        if (!this.wipeKeys().length) return this.L.pick;
                        if (!this.wipeTotal()) return this.L.nothing;

                        return this.L.willDelete.replace(':n', this.fmt(this.wipeTotal()));
                    },

                    modalRows() {
                        if (this.modal === 'wipe') {
                            return this.wipeKeys().map((k) => ({ key: k, label: this.L[k], value: this.fmt(this.stats[k]) + ' ' + this.L.records }));
                        }
                        if (this.modal === 'reset') {
                            return [
                                { key: 'pegawai', label: this.L.resetPegawai, value: this.fmt(this.stats.pegawai) },
                                { key: 'hadir', label: this.L.resetHadir, value: this.fmt(this.stats.hadir) },
                                { key: 'lewat', label: this.L.resetLewat, value: this.fmt(this.stats.lewat) },
                                { key: 'diumumkan', label: this.L.resetAnnounced, value: this.fmt(this.stats.diumumkan) },
                            ];
                        }

                        return [];
                    },

                    openModal(kind) {
                        if (this.busy) return;
                        if (kind === 'wipe' && !this.canWipe()) return;
                        if (kind === 'reset' && !this.stats.pegawai) return;
                        this.modal = kind;
                        // Safe default: Enter on an opened dialog cancels instead of destroying data.
                        this.$nextTick(() => this.$refs.cancelBtn?.focus());
                    },

                    closeModal() {
                        if (!this.busy) this.modal = null;
                    },

                    async confirm() {
                        const kind = this.modal;
                        if (!kind || this.busy) return;
                        this.busy = true;

                        try {
                            const { data } = kind === 'wipe'
                                ? await window.axios.post(this.urls.wipe, { tables: this.wipeKeys() })
                                : await window.axios.post(this.urls.reset);
                            if (data.overview) this.stats = data.overview;
                            if (kind === 'wipe') this.selected = [];
                            this.busy = false;
                            this.modal = null;
                            this.showToast(data.message ?? this.L.ok, true);
                        } catch (e) {
                            this.showToast(e.response?.data?.message ?? (kind === 'wipe' ? this.L.wipeFail : this.L.resetFail), false);
                        } finally {
                            this.busy = false;
                        }
                    },

                    showToast(text, ok = true) {
                        this.toast = { text, ok };
                        clearTimeout(toastTimer);
                        toastTimer = setTimeout(() => (this.toast = { text: '', ok: true }), 5000);
                    },
                }));
            });
        </script>
    @endpush
</x-dashboard-layout>
