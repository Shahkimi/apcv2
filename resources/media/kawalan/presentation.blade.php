<x-dashboard-layout :title="__('Kawalan Paparan Presentasi')" role="media">
    <x-kawalan-shell>
        <div>
            <a
                href="{{ route('media.senarai.index') }}"
                class="mb-3 inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground"
            >
                <i class="ri-arrow-left-line" aria-hidden="true"></i>
                {{ __('Kembali ke Senarai') }}
            </a>
            <x-crud-header
                :title="__('Kawalan Paparan Presentasi')"
                :description="__('Ubah kedudukan teks dan saiz fon untuk skrin presentasi senarai kehadiran.')"
                :show-create="false"
            />
        </div>

        @php
            $bps = [
                'base' => ['label' => __('Telefon'), 'hint' => '< 640px'],
                'sm' => ['label' => __('Tablet'), 'hint' => '≥ 640px'],
                'md' => ['label' => __('Desktop'), 'hint' => '≥ 768px'],
            ];
            $fontRows = [
                ['icon' => 'ri-user-line', 'label' => __('Nama'), 'key' => 'fonts_name', 'name' => 'fonts[name_%s]', 'min' => 10, 'max' => 200],
                ['icon' => 'ri-briefcase-line', 'label' => __('Jawatan'), 'key' => 'fonts_jawatan', 'name' => 'fonts[jawatan_%s]', 'min' => 10, 'max' => 200],
            ];
            if ($isJasamu) {
                $fontRows[] = ['icon' => 'ri-calendar-check-line', 'label' => __('Tarikh bersara'), 'key' => 'fonts_tarikh', 'name' => 'fonts[tarikh_%s]', 'min' => 10, 'max' => 200];
                $fontRows[] = ['icon' => 'ri-user-heart-line', 'label' => __('Bersara'), 'key' => 'fonts_bersara', 'name' => 'fonts[bersara_%s]', 'min' => 10, 'max' => 200];
                $fontRows[] = ['icon' => 'ri-history-line', 'label' => __('Tempoh berkhidmat'), 'key' => 'fonts_tempoh', 'name' => 'fonts[tempoh_%s]', 'min' => 10, 'max' => 200];
            }
            $mtRow = ['icon' => 'ri-align-top', 'label' => __('Margin atas'), 'key' => 'position_mt', 'name' => 'position[mt_%s]', 'min' => 0, 'max' => 2000];
        @endphp

        <div
            x-data="presentationSettings(@js([
                'formValues' => $formValues,
                'profiles' => $profiles,
                'activeProfileId' => $activeProfileId,
                'max' => $maxProfiles,
                'ptjPx' => $ptjFontPx,
                'backdropUrl' => $backdrop?->file_path ? $backdrop->image_url : null,
                'routes' => [
                    'update' => route('media.kawalan.presentation.update'),
                    'profilesStore' => route('media.kawalan.presentation.profiles.store'),
                    'profilesUpdate' => route('media.kawalan.presentation.profiles.update', ['presentationProfile' => '__ID__']),
                    'profilesDestroy' => route('media.kawalan.presentation.profiles.destroy', ['presentationProfile' => '__ID__']),
                    'profilesApply' => route('media.kawalan.presentation.profiles.apply', ['presentationProfile' => '__ID__']),
                ],
            ]))"
        >
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-300/70 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('media.kawalan.presentation.update') }}"
                @submit.prevent="save()"
                class="lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(20rem,26rem)] lg:items-start lg:gap-x-10"
            >
                @csrf
                @method('PUT')

                {{-- Aside: live preview + saved profiles --}}
                <aside class="mb-10 space-y-10 lg:order-2 lg:sticky lg:top-6 lg:mb-0">

                    {{-- Live preview --}}
                    <section class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                {{ __('Pratonton') }}
                            </h2>
                            <span class="text-[11px] text-muted-foreground">{{ __('Desktop') }} · 1280×720</span>
                        </div>

                        <div class="rounded-xl border border-border/70 bg-muted/20 p-3" x-init="initPreview($el)">
                            <div class="mx-auto overflow-hidden rounded-md shadow-sm" :style="previewFrameStyle()">
                                <div
                                    class="presentation-preview-stage flex items-center justify-center bg-cover bg-center bg-no-repeat text-white"
                                    :style="previewStageStyle()"
                                >
                                    <div class="officer-display-wrap w-full max-w-6xl text-center">
                                        <div class="officer-display">
                                            <div class="officer-name">{{ __('Nama Pegawai Contoh') }}</div>
                                            <div class="officer-jawatan">{{ __('Pegawai Teknologi Maklumat') }}</div>
                                            @if ($isJasamu)
                                                <div class="officer-tarikh">{{ __('Tarikh bersara: 31/12/2026') }}</div>
                                                <div class="officer-bersara">{{ __('Bersara: Wajib') }}</div>
                                                <div class="officer-tempoh">{{ __('Tempoh berkhidmat: 25 tahun') }}</div>
                                            @else
                                                <div class="officer-ptj">{{ __('PTJ Contoh') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            {{ __('Pratonton anggaran — skrin sebenar bergantung pada resolusi.') }}
                        </p>
                    </section>

                    {{-- Saved profiles --}}
                    <section class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                {{ __('Profil') }}
                            </h2>
                            <span class="text-[11px] tabular-nums text-muted-foreground" x-text="profiles.length + ' / ' + max"></span>
                        </div>

                        <template x-if="profiles.length === 0">
                            <p class="rounded-md border border-dashed border-border/70 px-3 py-4 text-center text-xs text-muted-foreground">
                                {{ __('Belum ada profil. Laraskan nilai dan simpan sebagai profil.') }}
                            </p>
                        </template>

                        <ul class="max-h-72 overflow-y-auto" x-show="profiles.length > 0">
                            <template x-for="profile in profiles" :key="profile.id">
                                <li
                                    class="flex items-center gap-2 border-t border-border/50 py-2 first:border-t-0"
                                    :class="profile.id === activeProfileId ? '-mx-2 rounded-md bg-primary/5 px-2' : ''"
                                >
                                    <span
                                        class="h-1.5 w-1.5 shrink-0 rounded-full"
                                        :class="profile.id === activeProfileId ? 'bg-primary' : 'bg-transparent'"
                                    ></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-foreground" x-text="profile.name"></p>
                                        <p class="text-[11px] text-muted-foreground">
                                            <span x-text="profile.updated_human"></span>
                                            <span x-show="profile.id === activeProfileId" class="ml-1 font-semibold uppercase tracking-wide text-primary">{{ __('Aktif') }}</span>
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center">
                                        <button type="button" class="btn btn-ghost btn-sm btn-icon text-primary" :disabled="busy" title="{{ __('Guna') }}" @click="applyProfile(profile)">
                                            <i class="ri-play-circle-line" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm btn-icon text-muted-foreground" :disabled="busy" title="{{ __('Kemas kini dengan nilai semasa') }}" @click="overwriteProfile(profile)">
                                            <i class="ri-save-3-line" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm btn-icon text-muted-foreground" :disabled="busy" title="{{ __('Namakan semula') }}" @click="renameProfile(profile)">
                                            <i class="ri-pencil-line" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm btn-icon text-muted-foreground hover:text-destructive" :disabled="busy" title="{{ __('Padam') }}" @click="deleteProfile(profile)">
                                            <i class="ri-delete-bin-line" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        <button
                            type="button"
                            class="btn btn-outline btn-sm w-full gap-1.5"
                            :disabled="busy || profiles.length >= max"
                            :title="profiles.length >= max ? '{{ __('Had profil dicapai') }}' : ''"
                            @click="saveAsProfile()"
                        >
                            <i class="ri-add-line" aria-hidden="true"></i>
                            <span>{{ __('Simpan sebagai profil') }}</span>
                        </button>
                    </section>
                </aside>

                {{-- Main column: matrix settings --}}
                <div class="space-y-12 lg:order-1">

                    {{-- Kedudukan --}}
                    <section class="space-y-5">
                        <div>
                            <h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                {{ __('Kedudukan Teks') }}
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">{{ __('Semua nilai kedudukan menggunakan unit px. Pratonton memaparkan nilai Desktop.') }}</p>
                        </div>

                        <div class="grid grid-cols-[minmax(5.5rem,8rem)_repeat(3,minmax(0,1fr))] gap-x-3 gap-y-4 sm:gap-x-4">
                            <div></div>
                            @foreach ($bps as $bp => $meta)
                                <div>
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider {{ $bp === 'md' ? 'text-primary' : 'text-muted-foreground' }}">{{ $meta['label'] }}</span>
                                    <span class="block text-[11px] text-muted-foreground">{{ $meta['hint'] }}</span>
                                </div>
                            @endforeach

                            <div class="flex items-center gap-2 pt-2 text-sm font-medium text-foreground">
                                <i class="{{ $mtRow['icon'] }} text-muted-foreground" aria-hidden="true"></i>
                                {{ $mtRow['label'] }}
                            </div>
                            @foreach (array_keys($bps) as $bp)
                                @php $key = $mtRow['key'].'_'.$bp; $name = sprintf($mtRow['name'], $bp); @endphp
                                <div class="min-w-0">
                                    <div class="relative">
                                        <input
                                            type="number"
                                            id="{{ $key }}"
                                            name="{{ $name }}"
                                            min="{{ $mtRow['min'] }}"
                                            max="{{ $mtRow['max'] }}"
                                            step="1"
                                            value="{{ $formValues[$key] }}"
                                            x-model.number="values.{{ $key }}"
                                            class="matrix-number h-9 w-full rounded-md border border-input bg-background pl-3 pr-8 text-sm tabular-nums focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring"
                                            :class="errors.{{ $key }} ? 'border-destructive' : ''"
                                        >
                                        <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-[11px] text-muted-foreground">px</span>
                                    </div>
                                    <input
                                        type="range"
                                        min="{{ $mtRow['min'] }}"
                                        max="{{ $mtRow['max'] }}"
                                        step="1"
                                        x-model.number="values.{{ $key }}"
                                        class="matrix-range mt-2 w-full"
                                        aria-label="{{ $mtRow['label'] }} {{ $bps[$bp]['label'] }}"
                                    >
                                    <template x-if="errors.{{ $key }}">
                                        <p class="mt-1 text-xs text-destructive" x-text="errors.{{ $key }}"></p>
                                    </template>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                            <div class="flex items-center gap-2 text-sm font-medium text-foreground">
                                <i class="ri-arrow-up-down-line text-muted-foreground" aria-hidden="true"></i>
                                {{ __('Translate Y') }}
                            </div>
                            <div class="inline-flex h-9 items-stretch overflow-hidden rounded-md border border-input">
                                <button type="button" class="px-2.5 text-muted-foreground hover:bg-accent" @click="nudge('position_translate_y', -10, -1000, 1000)" aria-label="-10">
                                    <i class="ri-subtract-line" aria-hidden="true"></i>
                                </button>
                                <div class="relative">
                                    <input
                                        type="number"
                                        id="position_translate_y"
                                        name="position[translate_y]"
                                        min="-1000"
                                        max="1000"
                                        step="1"
                                        value="{{ $formValues['position_translate_y'] }}"
                                        x-model.number="values.position_translate_y"
                                        class="matrix-number h-full w-24 border-x border-input bg-background pl-3 pr-8 text-center text-sm tabular-nums focus:outline-none"
                                        :class="errors.position_translate_y ? 'border-destructive' : ''"
                                    >
                                    <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-[11px] text-muted-foreground">px</span>
                                </div>
                                <button type="button" class="px-2.5 text-muted-foreground hover:bg-accent" @click="nudge('position_translate_y', 10, -1000, 1000)" aria-label="+10">
                                    <i class="ri-add-line" aria-hidden="true"></i>
                                </button>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ __('Positif = ke bawah') }}</span>
                            <template x-if="errors.position_translate_y">
                                <p class="w-full text-xs text-destructive" x-text="errors.position_translate_y"></p>
                            </template>
                        </div>
                    </section>

                    {{-- Saiz fon --}}
                    <section class="space-y-5">
                        <div>
                            <h2 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                                {{ __('Saiz Fon') }}
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ $isJasamu
                                    ? __('Semua baris (nama, jawatan, tarikh bersara, bersara, tempoh berkhidmat) guna unit px.')
                                    : __('Nama dan jawatan guna unit px. PTJ guna kelas Tailwind untuk responsif.') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-[minmax(5.5rem,8rem)_repeat(3,minmax(0,1fr))] gap-x-3 gap-y-4 sm:gap-x-4">
                            <div></div>
                            @foreach ($bps as $bp => $meta)
                                <div>
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider {{ $bp === 'md' ? 'text-primary' : 'text-muted-foreground' }}">{{ $meta['label'] }}</span>
                                    <span class="block text-[11px] text-muted-foreground">{{ $meta['hint'] }}</span>
                                </div>
                            @endforeach

                            @foreach ($fontRows as $row)
                                <div class="flex items-center gap-2 pt-2 text-sm font-medium text-foreground">
                                    <i class="{{ $row['icon'] }} text-muted-foreground" aria-hidden="true"></i>
                                    {{ $row['label'] }}
                                </div>
                                @foreach (array_keys($bps) as $bp)
                                    @php $key = $row['key'].'_'.$bp; $name = sprintf($row['name'], $bp); @endphp
                                    <div class="min-w-0">
                                        <div class="relative">
                                            <input
                                                type="number"
                                                id="{{ $key }}"
                                                name="{{ $name }}"
                                                min="{{ $row['min'] }}"
                                                max="{{ $row['max'] }}"
                                                step="1"
                                                value="{{ $formValues[$key] }}"
                                                x-model.number="values.{{ $key }}"
                                                class="matrix-number h-9 w-full rounded-md border border-input bg-background pl-3 pr-8 text-sm tabular-nums focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring"
                                                :class="errors.{{ $key }} ? 'border-destructive' : ''"
                                            >
                                            <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-[11px] text-muted-foreground">px</span>
                                        </div>
                                        <input
                                            type="range"
                                            min="{{ $row['min'] }}"
                                            max="{{ $row['max'] }}"
                                            step="1"
                                            x-model.number="values.{{ $key }}"
                                            class="matrix-range mt-2 w-full"
                                            aria-label="{{ $row['label'] }} {{ $bps[$bp]['label'] }}"
                                        >
                                        <template x-if="errors.{{ $key }}">
                                            <p class="mt-1 text-xs text-destructive" x-text="errors.{{ $key }}"></p>
                                        </template>
                                    </div>
                                @endforeach
                            @endforeach

                            @unless ($isJasamu)
                                <div class="flex items-center gap-2 pt-2 text-sm font-medium text-foreground">
                                    <i class="ri-building-line text-muted-foreground" aria-hidden="true"></i>
                                    {{ __('PTJ') }}
                                </div>
                                <div class="min-w-0">
                                    <select
                                        id="fonts_ptj_base"
                                        name="fonts[ptj_base]"
                                        x-model="values.fonts_ptj_base"
                                        class="h-9 w-full rounded-md border border-input bg-background px-2.5 text-sm"
                                        :class="errors.fonts_ptj_base ? 'border-destructive' : ''"
                                    >
                                        @foreach ($ptjFontOptions as $option)
                                            <option value="{{ $option }}" @selected($formValues['fonts_ptj_base'] === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <template x-if="errors.fonts_ptj_base">
                                        <p class="mt-1 text-xs text-destructive" x-text="errors.fonts_ptj_base"></p>
                                    </template>
                                </div>
                                <div class="col-span-2 min-w-0">
                                    <select
                                        id="fonts_ptj_sm"
                                        name="fonts[ptj_sm]"
                                        x-model="values.fonts_ptj_sm"
                                        class="h-9 w-full rounded-md border border-input bg-background px-2.5 text-sm"
                                        :class="errors.fonts_ptj_sm ? 'border-destructive' : ''"
                                    >
                                        @foreach ($ptjFontOptions as $option)
                                            <option value="{{ $option }}" @selected($formValues['fonts_ptj_sm'] === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-[11px] text-muted-foreground">{{ __('Tablet & Desktop') }}</p>
                                    <template x-if="errors.fonts_ptj_sm">
                                        <p class="mt-1 text-xs text-destructive" x-text="errors.fonts_ptj_sm"></p>
                                    </template>
                                </div>
                            @endunless
                        </div>
                    </section>
                </div>

                {{-- Sticky action bar --}}
                <div class="sticky bottom-4 z-10 mt-10 lg:order-3 lg:col-span-2">
                    <div class="flex h-14 items-center justify-between rounded-xl border border-border/70 bg-card/95 px-4 shadow-lg backdrop-blur">
                        <div class="flex min-w-0 items-center gap-2 text-sm">
                            <span class="h-2 w-2 shrink-0 rounded-full" :class="isDirty() ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                            <span class="truncate text-muted-foreground" x-text="statusText()"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn btn-ghost btn-sm" :disabled="busy || !isDirty()" @click="revert()">
                                {{ __('Revert') }}
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="busy">
                                <i class="ri-loader-4-line animate-spin" x-show="busy" aria-hidden="true"></i>
                                <i class="ri-save-3-line" x-show="!busy" aria-hidden="true"></i>
                                <span>{{ __('Simpan Tetapan') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </x-kawalan-shell>

    @push('scripts')
        <script>
            window.presentationSettings = (init) => ({
                values: { ...init.formValues },
                saved: { ...init.formValues },
                profiles: init.profiles,
                activeProfileId: init.activeProfileId,
                loadedProfileId: init.activeProfileId,
                max: init.max,
                routes: init.routes,
                errors: {},
                busy: false,

                // Live preview — Desktop (md ≥ 768px) only, virtual stage 1280×720
                previewWidth: 0,
                stage: { w: 1280, h: 720 },
                ptjPx: init.ptjPx,
                backdropUrl: init.backdropUrl,

                initPreview(el) {
                    this.previewWidth = el.clientWidth - 24;
                    new ResizeObserver((entries) => {
                        this.previewWidth = entries[0].contentRect.width;
                    }).observe(el);
                },

                previewScale() {
                    return this.previewWidth ? this.previewWidth / this.stage.w : 0;
                },

                previewFrameStyle() {
                    const k = this.previewScale();
                    return { width: `${this.stage.w * k}px`, height: `${this.stage.h * k}px` };
                },

                previewStageStyle() {
                    return {
                        width: `${this.stage.w}px`,
                        height: `${this.stage.h}px`,
                        transform: `scale(${this.previewScale()})`,
                        backgroundImage: this.backdropUrl
                            ? `url("${this.backdropUrl}")`
                            : 'linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%)',
                        ...this.previewVars(),
                    };
                },

                previewVars() {
                    const v = this.values;
                    const n = (x) => Number(x) || 0;

                    return {
                        '--officer-mt': `${n(v.position_mt_md)}px`,
                        '--officer-translate-y': `${n(v.position_translate_y)}px`,
                        '--officer-px': '48px',
                        '--officer-gap': '8px',
                        '--officer-name-font-size': `${n(v.fonts_name_md)}px`,
                        '--officer-jawatan-font-size': `${n(v.fonts_jawatan_md)}px`,
                        '--officer-ptj-font-size': `${this.ptjPx[v.fonts_ptj_sm] ?? 24}px`,
                        '--officer-tarikh-font-size': `${n(v.fonts_tarikh_md)}px`,
                        '--officer-bersara-font-size': `${n(v.fonts_bersara_md)}px`,
                        '--officer-tempoh-font-size': `${n(v.fonts_tempoh_md)}px`,
                    };
                },

                nudge(key, delta, min, max) {
                    const v = Number(this.values[key]) || 0;
                    this.values[key] = Math.min(max, Math.max(min, v + delta));
                },

                isDirty() {
                    return JSON.stringify(this.values) !== JSON.stringify(this.saved);
                },

                statusText() {
                    if (this.isDirty()) {
                        return @json(__('Perubahan belum disimpan'));
                    }

                    const active = this.profiles.find((p) => p.id === this.loadedProfileId);
                    if (active) {
                        return @json(__('Menggunakan profil')) + ': ' + active.name;
                    }

                    return @json(__('Tiada perubahan'));
                },

                revert() {
                    this.values = { ...this.saved };
                    this.errors = {};
                },

                buildConfigPayload() {
                    return {
                        position: {
                            mt_base: this.values.position_mt_base,
                            mt_sm: this.values.position_mt_sm,
                            mt_md: this.values.position_mt_md,
                            translate_y: this.values.position_translate_y,
                        },
                        fonts: {
                            name_base: this.values.fonts_name_base,
                            name_sm: this.values.fonts_name_sm,
                            name_md: this.values.fonts_name_md,
                            jawatan_base: this.values.fonts_jawatan_base,
                            jawatan_sm: this.values.fonts_jawatan_sm,
                            jawatan_md: this.values.fonts_jawatan_md,
                            ptj_base: this.values.fonts_ptj_base,
                            ptj_sm: this.values.fonts_ptj_sm,
                            tarikh_base: this.values.fonts_tarikh_base,
                            tarikh_sm: this.values.fonts_tarikh_sm,
                            tarikh_md: this.values.fonts_tarikh_md,
                            bersara_base: this.values.fonts_bersara_base,
                            bersara_sm: this.values.fonts_bersara_sm,
                            bersara_md: this.values.fonts_bersara_md,
                            tempoh_base: this.values.fonts_tempoh_base,
                            tempoh_sm: this.values.fonts_tempoh_sm,
                            tempoh_md: this.values.fonts_tempoh_md,
                        },
                    };
                },

                matchesProfile(profile) {
                    return JSON.stringify(profile.form_values) === JSON.stringify(this.values);
                },

                async save() {
                    if (this.busy) return;
                    this.busy = true;
                    this.errors = {};

                    try {
                        const matched = this.profiles.find((p) => this.matchesProfile(p));
                        const payload = this.buildConfigPayload();
                        if (matched) {
                            payload.profile_id = matched.id;
                        }

                        const { data } = await window.axios.put(this.routes.update, payload, {
                            headers: { Accept: 'application/json' },
                        });

                        this.saved = { ...this.values };
                        this.activeProfileId = data.active_profile_id;
                        this.loadedProfileId = data.active_profile_id;
                        this.toast('success', data.message);
                    } catch (e) {
                        this.handleValidationError(e);
                    } finally {
                        this.busy = false;
                    }
                },

                async saveAsProfile() {
                    if (this.busy || this.profiles.length >= this.max) return;

                    const { value: name } = await window.Swal.fire({
                        title: @json(__('Simpan sebagai profil')),
                        input: 'text',
                        inputPlaceholder: @json(__('Nama profil')),
                        showCancelButton: true,
                        confirmButtonText: @json(__('Simpan')),
                        cancelButtonText: @json(__('Batal')),
                        background: 'var(--popover)',
                        color: 'var(--popover-foreground)',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'kawalan-swal2-popup',
                            htmlContainer: 'kawalan-swal2-text',
                            actions: 'kawalan-swal2-actions',
                            confirmButton: 'kawalan-swal2-confirm-success',
                            cancelButton: 'kawalan-swal2-cancel',
                            input: 'kawalan-swal2-input',
                        },
                        inputValidator: (value) => (!value || !value.trim() ? @json(__('Nama diperlukan')) : undefined),
                    });

                    if (!name) return;

                    this.busy = true;
                    try {
                        const { data } = await window.axios.post(this.routes.profilesStore, {
                            name: name.trim(),
                            config: this.buildConfigPayload(),
                        });

                        this.profiles = data.profiles;
                        this.activeProfileId = data.active_profile_id;
                        this.loadedProfileId = data.profile.id;
                        this.toast('success', data.message);
                    } catch (e) {
                        this.toastFromException(e);
                    } finally {
                        this.busy = false;
                    }
                },

                async applyProfile(profile) {
                    if (this.busy) return;
                    this.busy = true;

                    try {
                        const { data } = await window.axios.post(this.routes.profilesApply.replace('__ID__', profile.id));

                        this.values = { ...data.form_values };
                        this.saved = { ...data.form_values };
                        this.loadedProfileId = data.active_profile_id;
                        this.activeProfileId = data.active_profile_id;
                        this.profiles = data.profiles;
                        this.errors = {};
                        this.toast('success', data.message);
                    } catch (e) {
                        this.toastFromException(e);
                    } finally {
                        this.busy = false;
                    }
                },

                async overwriteProfile(profile) {
                    if (this.busy) return;

                    const result = await window.Swal.fire({
                        title: @json(__('Kemas kini profil ini dengan nilai semasa?')),
                        text: profile.name,
                        icon: 'warning',
                        showCancelButton: true,
                        reverseButtons: true,
                        confirmButtonText: @json(__('Kemas kini')),
                        cancelButtonText: @json(__('Batal')),
                        background: 'var(--popover)',
                        color: 'var(--popover-foreground)',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'kawalan-swal2-popup',
                            htmlContainer: 'kawalan-swal2-text',
                            actions: 'kawalan-swal2-actions',
                            confirmButton: 'kawalan-swal2-confirm-warning',
                            cancelButton: 'kawalan-swal2-cancel',
                        },
                    });

                    if (!result.isConfirmed) return;

                    this.busy = true;
                    try {
                        const { data } = await window.axios.put(this.routes.profilesUpdate.replace('__ID__', profile.id), {
                            config: this.buildConfigPayload(),
                        });

                        this.profiles = data.profiles;
                        this.toast('success', data.message);
                    } catch (e) {
                        this.toastFromException(e);
                    } finally {
                        this.busy = false;
                    }
                },

                async renameProfile(profile) {
                    if (this.busy) return;

                    const { value: name } = await window.Swal.fire({
                        title: @json(__('Namakan semula profil')),
                        input: 'text',
                        inputValue: profile.name,
                        showCancelButton: true,
                        confirmButtonText: @json(__('Simpan')),
                        cancelButtonText: @json(__('Batal')),
                        background: 'var(--popover)',
                        color: 'var(--popover-foreground)',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'kawalan-swal2-popup',
                            htmlContainer: 'kawalan-swal2-text',
                            actions: 'kawalan-swal2-actions',
                            confirmButton: 'kawalan-swal2-confirm-success',
                            cancelButton: 'kawalan-swal2-cancel',
                            input: 'kawalan-swal2-input',
                        },
                        inputValidator: (value) => (!value || !value.trim() ? @json(__('Nama diperlukan')) : undefined),
                    });

                    if (!name || name.trim() === profile.name) return;

                    this.busy = true;
                    try {
                        const { data } = await window.axios.put(this.routes.profilesUpdate.replace('__ID__', profile.id), {
                            name: name.trim(),
                        });

                        this.profiles = data.profiles;
                        this.toast('success', data.message);
                    } catch (e) {
                        this.toastFromException(e);
                    } finally {
                        this.busy = false;
                    }
                },

                async deleteProfile(profile) {
                    if (this.busy) return;

                    const confirmed = await window.kawalanConfirmDelete({
                        title: @json(__('Padam profil ini?')),
                        text: profile.name,
                        confirmButtonText: @json(__('Padam')),
                        cancelButtonText: @json(__('Batal')),
                    });

                    if (!confirmed) return;

                    this.busy = true;
                    try {
                        const { data } = await window.axios.delete(this.routes.profilesDestroy.replace('__ID__', profile.id));

                        this.profiles = data.profiles;
                        this.activeProfileId = data.active_profile_id;
                        if (this.loadedProfileId === profile.id) {
                            this.loadedProfileId = null;
                        }
                        this.toast('success', data.message);
                    } catch (e) {
                        this.toastFromException(e);
                    } finally {
                        this.busy = false;
                    }
                },

                handleValidationError(e) {
                    if (e.response && e.response.status === 422 && e.response.data.errors) {
                        const flat = {};
                        Object.keys(e.response.data.errors).forEach((key) => {
                            flat[key.replace('.', '_')] = e.response.data.errors[key][0];
                        });
                        this.errors = flat;
                        this.toast('error', @json(__('Sila semak semula nilai yang dimasukkan.')));
                        return;
                    }

                    this.toastFromException(e);
                },

                toastFromException(e) {
                    const message = e.response?.data?.message || @json(__('Ralat berlaku. Sila cuba lagi.'));
                    this.toast('error', message);
                },

                toast(icon, message) {
                    window.Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon,
                        title: message,
                        showConfirmButton: false,
                        timer: 1800,
                        timerProgressBar: true,
                        background: 'var(--popover)',
                        color: 'var(--popover-foreground)',
                    });
                },
            });
        </script>
    @endpush
</x-dashboard-layout>
