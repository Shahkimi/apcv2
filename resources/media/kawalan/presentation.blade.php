<x-dashboard-layout :title="__('Kawalan Paparan Presentasi')" role="media">
    <x-kawalan-shell>
        <x-crud-header
            :title="__('Kawalan Paparan Presentasi')"
            :description="__('Ubah kedudukan teks dan saiz fon untuk skrin presentasi senarai kehadiran.')"
            :show-create="false"
        />

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-300/70 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-1 lg:items-start">
            <form method="POST" action="{{ route('media.kawalan.presentation.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <section class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                        <i class="ri-align-center-vertical text-lg text-muted-foreground" aria-hidden="true"></i>
                        {{ __('Kedudukan Teks') }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ __('Semua nilai kedudukan menggunakan unit px. Translate Y positif = gerak ke bawah.') }}</p>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @php
                            $mtBase = old('position.mt_base', (int) rtrim($config['position']['mt_base'], 'px'));
                            $mtSm = old('position.mt_sm', (int) rtrim($config['position']['mt_sm'], 'px'));
                            $mtMd = old('position.mt_md', (int) rtrim($config['position']['mt_md'], 'px'));
                            $ty = old('position.translate_y', (int) rtrim($config['position']['translate_y'], 'px'));
                        @endphp

                        <div class="space-y-2">
                            <label for="position_mt_base" class="block text-sm font-medium text-foreground">{{ __('Margin Atas (Telefon)') }}</label>
                            <input
                                type="number"
                                min="0"
                                max="2000"
                                id="position_mt_base"
                                name="position[mt_base]"
                                value="{{ $mtBase }}"
                                step="1"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm"
                            >
                            <input type="range" min="0" max="2000" step="1" id="position_mt_base_range" value="{{ $mtBase }}" class="w-full accent-violet-600" data-number-id="position_mt_base">
                            @error('position.mt_base') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="position_mt_sm" class="block text-sm font-medium text-foreground">{{ __('Margin Atas (sm >= 640px)') }}</label>
                            <input
                                type="number"
                                min="0"
                                max="2000"
                                id="position_mt_sm"
                                name="position[mt_sm]"
                                value="{{ $mtSm }}"
                                step="1"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm"
                            >
                            <input type="range" min="0" max="2000" step="1" id="position_mt_sm_range" value="{{ $mtSm }}" class="w-full accent-violet-600" data-number-id="position_mt_sm">
                            @error('position.mt_sm') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="position_mt_md" class="block text-sm font-medium text-foreground">{{ __('Margin Atas (md >= 768px)') }}</label>
                            <input
                                type="number"
                                min="0"
                                max="2000"
                                id="position_mt_md"
                                name="position[mt_md]"
                                value="{{ $mtMd }}"
                                step="1"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm"
                            >
                            <input type="range" min="0" max="2000" step="1" id="position_mt_md_range" value="{{ $mtMd }}" class="w-full accent-violet-600" data-number-id="position_mt_md">
                            @error('position.mt_md') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="position_translate_y" class="block text-sm font-medium text-foreground">{{ __('Translate Y (ke bawah)') }}</label>
                            <input
                                type="number"
                                min="-1000"
                                max="1000"
                                id="position_translate_y"
                                name="position[translate_y]"
                                value="{{ $ty }}"
                                step="1"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm"
                            >
                            <input type="range" min="-1000" max="1000" step="1" id="position_translate_y_range" value="{{ $ty }}" class="w-full accent-violet-600" data-number-id="position_translate_y">
                            @error('position.translate_y') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-foreground">{{ __('Saiz Fon (px)') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ __('Nama dan jawatan guna unit px. PTJ guna kelas Tailwind untuk responsif.') }}</p>

                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        @php
                            $nameBase = old('fonts.name_base', $config['fonts']['name_base']);
                            $nameSm = old('fonts.name_sm', $config['fonts']['name_sm']);
                            $nameMd = old('fonts.name_md', $config['fonts']['name_md']);
                            $jawatanBase = old('fonts.jawatan_base', $config['fonts']['jawatan_base']);
                            $jawatanSm = old('fonts.jawatan_sm', $config['fonts']['jawatan_sm']);
                            $jawatanMd = old('fonts.jawatan_md', $config['fonts']['jawatan_md']);
                        @endphp

                        <div class="space-y-2">
                            <label for="fonts_name_base" class="block text-sm font-medium text-foreground">{{ __('Nama (Telefon)') }}</label>
                            <input type="number" min="10" max="200" id="fonts_name_base" name="fonts[name_base]" value="{{ $nameBase }}" step="1" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                            <input type="range" min="10" max="200" step="1" id="fonts_name_base_range" value="{{ $nameBase }}" class="w-full accent-violet-600" data-number-id="fonts_name_base">
                            @error('fonts.name_base') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="fonts_name_sm" class="block text-sm font-medium text-foreground">{{ __('Nama (sm)') }}</label>
                            <input type="number" min="10" max="200" id="fonts_name_sm" name="fonts[name_sm]" value="{{ $nameSm }}" step="1" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                            <input type="range" min="10" max="200" step="1" id="fonts_name_sm_range" value="{{ $nameSm }}" class="w-full accent-violet-600" data-number-id="fonts_name_sm">
                            @error('fonts.name_sm') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="fonts_name_md" class="block text-sm font-medium text-foreground">{{ __('Nama (md)') }}</label>
                            <input type="number" min="10" max="200" id="fonts_name_md" name="fonts[name_md]" value="{{ $nameMd }}" step="1" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                            <input type="range" min="10" max="200" step="1" id="fonts_name_md_range" value="{{ $nameMd }}" class="w-full accent-violet-600" data-number-id="fonts_name_md">
                            @error('fonts.name_md') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="fonts_jawatan_base" class="block text-sm font-medium text-foreground">{{ __('Jawatan (Telefon)') }}</label>
                            <input type="number" min="10" max="200" id="fonts_jawatan_base" name="fonts[jawatan_base]" value="{{ $jawatanBase }}" step="1" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                            <input type="range" min="10" max="200" step="1" id="fonts_jawatan_base_range" value="{{ $jawatanBase }}" class="w-full accent-violet-600" data-number-id="fonts_jawatan_base">
                            @error('fonts.jawatan_base') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="fonts_jawatan_sm" class="block text-sm font-medium text-foreground">{{ __('Jawatan (sm)') }}</label>
                            <input type="number" min="10" max="200" id="fonts_jawatan_sm" name="fonts[jawatan_sm]" value="{{ $jawatanSm }}" step="1" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                            <input type="range" min="10" max="200" step="1" id="fonts_jawatan_sm_range" value="{{ $jawatanSm }}" class="w-full accent-violet-600" data-number-id="fonts_jawatan_sm">
                            @error('fonts.jawatan_sm') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="fonts_jawatan_md" class="block text-sm font-medium text-foreground">{{ __('Jawatan (md)') }}</label>
                            <input type="number" min="10" max="200" id="fonts_jawatan_md" name="fonts[jawatan_md]" value="{{ $jawatanMd }}" step="1" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                            <input type="range" min="10" max="200" step="1" id="fonts_jawatan_md_range" value="{{ $jawatanMd }}" class="w-full accent-violet-600" data-number-id="fonts_jawatan_md">
                            @error('fonts.jawatan_md') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="fonts_ptj_base" class="block text-sm font-medium text-foreground">{{ __('PTJ (Telefon)') }}</label>
                            <select id="fonts_ptj_base" name="fonts[ptj_base]" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                                @foreach ($ptjFontOptions as $option)
                                    <option value="{{ $option }}" @selected(old('fonts.ptj_base', $config['fonts']['ptj_base']) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('fonts.ptj_base') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="fonts_ptj_sm" class="block text-sm font-medium text-foreground">{{ __('PTJ (sm ke atas)') }}</label>
                            <select id="fonts_ptj_sm" name="fonts[ptj_sm]" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm">
                                @foreach ($ptjFontOptions as $option)
                                    <option value="{{ $option }}" @selected(old('fonts.ptj_sm', $config['fonts']['ptj_sm']) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @error('fonts.ptj_sm') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        id="presentation-revert-btn"
                        class="inline-flex items-center rounded-xl border border-border bg-background px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                    >
                        {{ __('Revert') }}
                    </button>
                    <button type="submit" class="inline-flex items-center rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-500">
                        {{ __('Simpan Tetapan') }}
                    </button>
                    <a href="{{ route('media.senarai.index') }}" class="text-sm font-medium text-muted-foreground hover:text-foreground">
                        {{ __('Kembali ke Senarai') }}
                    </a>
                </div>
            </form>
        </div>
    </x-kawalan-shell>

    @push('scripts')
        <script>
            (() => {
                const numberIds = [
                    'position_mt_base',
                    'position_mt_sm',
                    'position_mt_md',
                    'position_translate_y',
                    'fonts_name_base',
                    'fonts_name_sm',
                    'fonts_name_md',
                    'fonts_jawatan_base',
                    'fonts_jawatan_sm',
                    'fonts_jawatan_md',
                ];

                const bindRange = (rangeEl) => {
                    if (!rangeEl) return;
                    const numberId = rangeEl.dataset.numberId;
                    const numberEl = document.getElementById(numberId);
                    if (!numberEl) return;

                    // Keep number input and range slider in sync
                    numberEl.value = rangeEl.value;

                    rangeEl.addEventListener('input', () => {
                        numberEl.value = rangeEl.value;
                    });

                    numberEl.addEventListener('input', () => {
                        rangeEl.value = numberEl.value;
                    });
                };

                const ranges = Array.from(document.querySelectorAll('input[type="range"][data-number-id]'));
                ranges.forEach(bindRange);

                // Revert button resets inputs back to initial saved values (page load)
                const revertBtn = document.getElementById('presentation-revert-btn');
                const initialValues = {};

                numberIds.forEach((id) => {
                    initialValues[id] = document.getElementById(id)?.value ?? '';
                });

                initialValues.fonts_ptj_base = document.getElementById('fonts_ptj_base')?.value ?? '';
                initialValues.fonts_ptj_sm = document.getElementById('fonts_ptj_sm')?.value ?? '';

                if (!revertBtn) return;

                revertBtn.addEventListener('click', () => {
                    numberIds.forEach((id) => {
                        const el = document.getElementById(id);
                        if (!el) return;

                        el.value = initialValues[id];

                        const range = document.querySelector(`input[type="range"][data-number-id="${id}"]`);
                        if (range) range.value = initialValues[id];
                    });

                    const ptjBase = document.getElementById('fonts_ptj_base');
                    const ptjSm = document.getElementById('fonts_ptj_sm');
                    if (ptjBase) ptjBase.value = initialValues.fonts_ptj_base;
                    if (ptjSm) ptjSm.value = initialValues.fonts_ptj_sm;
                });
            })();
        </script>
    @endpush
</x-dashboard-layout>
