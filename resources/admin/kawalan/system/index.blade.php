<x-dashboard-layout :title="__('Sistem')" role="admin">
    <x-crud-header
        :title="__('Tetapan sistem')"
        :description="__('Tindakan pentadbiran yang memberi kesan kepada semua rekod pegawai. Gunakan dengan berhati-hati.')"
        :show-create="false"
    />

    <div
        class="mx-auto max-w-2xl"
        x-data="{
            confirmOpen: false,
            loading: false,
            toast: '',
            toastOk: true,
            openConfirm() {
                this.confirmOpen = true;
            },
            closeConfirm() {
                if (!this.loading) {
                    this.confirmOpen = false;
                }
            },
            showToast(msg, ok = true) {
                this.toast = msg;
                this.toastOk = ok;
                clearTimeout(this._toastT);
                this._toastT = setTimeout(() => (this.toast = ''), 5000);
            },
            async runReset() {
                this.loading = true;
                try {
                    const { data } = await window.axios.post(@js(route('admin.kawalan.system.reset')));
                    this.confirmOpen = false;
                    this.showToast(data.message ?? '{{ __('Berjaya.') }}', true);
                } catch (e) {
                    const msg =
                        e.response?.data?.message ??
                        '{{ __('Ralat semasa menetapkan semula. Sila cuba lagi.') }}';
                    this.showToast(msg, false);
                } finally {
                    this.loading = false;
                }
            },
        }"
        x-cloak
        @keydown.escape.window="closeConfirm()"
    >
        {{-- Toast --}}
        <div
            x-show="toast !== ''"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-6 right-6 z-[60] max-w-sm rounded-xl border px-4 py-3 text-sm shadow-lg"
            :class="toastOk ? 'border-green-600/40 bg-green-600/15 text-green-900 dark:text-green-100' : 'border-destructive/40 bg-destructive/15 text-destructive'"
            role="status"
            x-text="toast"
        ></div>

        {{-- Main card (Cleopatra-style) --}}
        <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-lg font-semibold text-foreground">{{ __('Tetap semula kehadiran pegawai') }}</h3>
                <span
                    class="rounded-lg border border-border bg-muted/40 px-2 py-1 text-xs font-medium text-muted-foreground"
                    title="{{ __('Tindakan global') }}"
                >
                    {{ __('Global') }}
                </span>
            </div>

            <div class="mb-4 flex items-center gap-4 rounded-xl bg-muted/30 p-3">
                <div
                    class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br from-amber-400 to-orange-600 shadow-inner"
                >
                    <svg class="h-9 w-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.75"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-semibold text-foreground">{{ __('Padam pautan sesi & status kehadiran') }}</h4>
                    <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                        {{ __('Semua pegawai akan ditetapkan: tiada sesi majlis, meja 0, panggilan lewat 0, tidak hadir, tidak lewat.') }}
                    </p>
                    <ul class="mt-2 list-inside list-disc text-xs text-muted-foreground">
                        <li><code class="text-foreground/80">sesi_majlis_id</code> → NULL</li>
                        <li><code class="text-foreground/80">no_meja</code>, <code class="text-foreground/80">no_panggilan_lewat</code> → 0</li>
                        <li><code class="text-foreground/80">is_attend</code>, <code class="text-foreground/80">is_late</code> → 0</li>
                        <li>{{ __('Rekod pengumuman senarai (jadual pegawai diumumkan) dikosongkan.') }}</li>
                    </ul>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-red-600 shadow-md"
                    >
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-foreground">{{ __('Tindakan tidak boleh diundur') }}</p>
                        <p class="text-xs text-muted-foreground">{{ __('Pastikan anda memahami kesan sebelum meneruskan.') }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-destructive px-4 py-2.5 text-sm font-semibold text-destructive-foreground shadow-sm transition hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-60"
                    @click="openConfirm()"
                    :disabled="loading"
                >
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    {{ __('Tetap semula semua pegawai') }}
                </button>
            </div>
        </div>

        {{-- Confirmation modal --}}
        <div
            x-show="confirmOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 p-4 backdrop-blur-sm"
            style="display: none"
            role="dialog"
            aria-modal="true"
            aria-labelledby="reset-confirm-title"
        >
            <div
                @click.outside="closeConfirm()"
                class="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
            >
                <h2 id="reset-confirm-title" class="text-lg font-semibold text-foreground">
                    {{ __('Sahkan tetapan semula?') }}
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ __('Ini akan mengemas kini semua rekod pegawai. Tindakan ini tidak boleh diundur.') }}
                </p>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" class="btn btn-outline" @click="closeConfirm()" :disabled="loading">
                        {{ __('Batal') }}
                    </button>
                    <button
                        type="button"
                        class="btn bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="runReset()"
                        :disabled="loading"
                    >
                        <span x-show="!loading">{{ __('Ya, tetap semula') }}</span>
                        <span x-show="loading" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            {{ __('Memproses…') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
