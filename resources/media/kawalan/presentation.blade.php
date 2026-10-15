<x-dashboard-layout :title="__('Kawalan Paparan Presentasi')" role="media">
    <x-kawalan-shell>
        <div>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <a
                    href="{{ route('media.senarai.index') }}"
                    class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground"
                >
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                    {{ __('Kembali ke Senarai') }}
                </a>
                <a
                    href="{{ route('media.senarai.present') }}"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-outline btn-sm gap-1.5"
                >
                    <i class="ri-slideshow-3-line" aria-hidden="true"></i>
                    {{ __('Buka skrin presentasi') }}
                </a>
            </div>
            <x-crud-header
                :title="__('Kawalan Paparan Presentasi')"
                :description="__('Pilih data yang dipaparkan di bawah nama pegawai, serta ubah kedudukan teks dan saiz fon untuk skrin presentasi senarai kehadiran.')"
                :show-create="false"
            />
        </div>

        @php
            $bps = [
                'base' => ['label' => __('Telefon'), 'hint' => '< 640px'],
                'sm' => ['label' => __('Tablet'), 'hint' => '≥ 640px'],
                'md' => ['label' => __('Desktop'), 'hint' => '≥ 768px'],
            ];
            $tabs = [
                'data' => ['label' => __('Data Dipaparkan'), 'icon' => 'ri-list-check-2', 'dirty' => null],
                'kedudukan' => ['label' => __('Kedudukan Teks'), 'icon' => 'ri-align-top', 'dirty' => 'position_'],
                'fon' => ['label' => __('Saiz Fon'), 'icon' => 'ri-font-size', 'dirty' => 'fonts_'],
                'fonstyle' => ['label' => __('Gaya Fon'), 'icon' => 'ri-font-family', 'dirty' => 'typo_'],
            ];
            $modeTabs = ['apc' => 'APC', 'jasamu' => 'Jasamu'];
            $fontRows = [
                ['type' => 'px', 'field' => null, 'icon' => 'ri-user-line', 'label' => __('Nama'), 'key' => 'fonts_name', 'name' => 'fonts[name_%s]'],
            ];
            foreach (['jawatan', 'ptj', 'tarikh', 'bersara', 'tempoh'] as $fieldKey) {
                $fontRows[] = [
                    'type' => $fieldKey === 'ptj' ? 'ptj' : 'px',
                    'field' => $fieldKey,
                    'icon' => $fieldDefs[$fieldKey]['icon'],
                    'label' => $fieldDefs[$fieldKey]['label'],
                    'key' => 'fonts_'.$fieldKey,
                    'name' => 'fonts['.$fieldKey.'_%s]',
                ];
            }
            $mtRow = ['icon' => 'ri-align-top', 'label' => __('Margin atas'), 'key' => 'position_mt', 'name' => 'position[mt_%s]', 'min' => 0, 'max' => 2000];
        @endphp

        {{-- Every library font, so the preview can switch fonts instantly (built from ids/route URLs only). --}}
        <style id="apc-library-font-faces">{!! $fontFaceCss !!}</style>

        <div
            x-data="presentationSettings(@js([
                'formValues' => $formValues,
                'profiles' => $profiles,
                'activeProfileId' => $activeProfileId,
                'max' => $maxProfiles,
                'ptjPx' => $ptjFontPx,
                'backdropUrl' => $backdrop?->file_path ? $backdrop->image_url : null,
                'fieldDefs' => $fieldDefs,
                'fieldDefaults' => $fieldDefaults,
                'currentMode' => $currentMode,
                'previewOfficer' => $previewOfficer,
                'sampleName' => __('Nama Pegawai Contoh'),
                'typo' => [
                    'parts' => array_keys($typoParts),
                    'labels' => collect($typoParts)->map(fn (array $meta) => $meta['label'])->all(),
                    'defaults' => $typoDefaults,
                    'tracking' => $trackingCss,
                    'trackingLabels' => $trackingOptions,
                    'weightLabels' => $weightOptions,
                    'modeLabels' => $modeTabs,
                    'defaultStack' => $defaultFontStack,
                ],
                'libraryFonts' => $libraryFonts,
                'routes' => [
                    'update' => route('media.kawalan.presentation.update'),
                    'fieldsUpdate' => route('media.kawalan.presentation.fields.update'),
                    'profilesStore' => route('media.kawalan.presentation.profiles.store'),
                    'profilesUpdate' => route('media.kawalan.presentation.profiles.update', ['presentationProfile' => '__ID__']),
                    'profilesDestroy' => route('media.kawalan.presentation.profiles.destroy', ['presentationProfile' => '__ID__']),
                    'profilesApply' => route('media.kawalan.presentation.profiles.apply', ['presentationProfile' => '__ID__']),
                    'fontList' => route('media.kawalan.fon.list'),
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
                        <div class="flex items-center justify-between gap-2">
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
                                            <div class="officer-name" :class="previewPartClass('name')" x-text="previewName()" @click="pickPreviewPart('name')"></div>
                                            <template x-for="line in previewLines()" :key="line.key">
                                                <div :class="['officer-' + line.key, previewPartClass(line.key)]" x-text="line.text" @click="pickPreviewPart(line.key)"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                            <span>
                                <span x-text="dataCaption()"></span>
                            </span>
                            <button
                                type="button"
                                x-show="preview !== null"
                                x-cloak
                                class="font-medium text-primary hover:underline"
                                @click="previewSource = previewSource === 'real' ? 'sample' : 'real'"
                                x-text="sourceToggleText()"
                            ></button>
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
                <div class="space-y-6 lg:order-1">

                    {{-- Tabs --}}
                    <div
                        class="grid grid-cols-2 gap-1 rounded-xl border border-input bg-muted/30 p-1 sm:grid-cols-4"
                        role="tablist"
                        aria-label="{{ __('Tetapan paparan') }}"
                        @keydown.arrow-right.prevent="focusTab(1)"
                        @keydown.arrow-left.prevent="focusTab(-1)"
                        @keydown.home.prevent="focusTab('first')"
                        @keydown.end.prevent="focusTab('last')"
                    >
                        @foreach ($tabs as $tabKey => $tabMeta)
                            <button
                                type="button"
                                role="tab"
                                id="tab-{{ $tabKey }}"
                                aria-controls="tabpanel-{{ $tabKey }}"
                                :aria-selected="tab === '{{ $tabKey }}'"
                                :tabindex="tab === '{{ $tabKey }}' ? 0 : -1"
                                class="relative inline-flex min-w-0 flex-col items-center justify-center gap-1 rounded-lg px-1.5 py-2 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:text-[13px]"
                                :class="tab === '{{ $tabKey }}' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="setTab('{{ $tabKey }}')"
                            >
                                <i class="{{ $tabMeta['icon'] }} text-base leading-none" aria-hidden="true"></i>
                                <span class="max-w-full truncate text-center leading-tight">{{ $tabMeta['label'] }}</span>
                                @if ($tabKey === 'data')
                                    <i
                                        x-show="fieldSaveState !== 'idle'"
                                        x-cloak
                                        class="text-xs"
                                        :class="[fieldSaveIcon(), fieldSaveState === 'error' ? 'text-destructive' : fieldSaveState === 'saved' ? 'text-emerald-600' : 'text-muted-foreground']"
                                        aria-hidden="true"
                                    ></i>
                                @else
                                    <span
                                        x-show="isGroupDirty('{{ $tabMeta['dirty'] }}')"
                                        x-cloak
                                        class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-amber-500"
                                        title="{{ __('Perubahan belum disimpan') }}"
                                    ></span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Data dipaparkan --}}
                    <section
                        id="tabpanel-data"
                        role="tabpanel"
                        aria-labelledby="tab-data"
                        x-show="tab === 'data'"
                        x-cloak
                        class="space-y-4"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium transition-colors"
                                        :class="fieldSaveClass()"
                                        role="status"
                                        aria-live="polite"
                                    >
                                        <i :class="fieldSaveIcon()" aria-hidden="true"></i>
                                        <span x-text="fieldSaveText()"></span>
                                    </span>
                                    <button
                                        type="button"
                                        x-show="fieldSaveState === 'error'"
                                        x-cloak
                                        class="text-[11px] font-medium text-destructive underline-offset-2 hover:underline"
                                        @click="persistFields()"
                                    >
                                        {{ __('Cuba lagi') }}
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ __('Tanda data yang mahu dipaparkan di bawah nama pegawai pada skrin utama. Guna anak panah untuk menyusun turutan. Perubahan di bahagian ini disimpan secara automatik.') }}
                                </p>
                            </div>
                            <div class="inline-flex shrink-0 rounded-lg border border-input bg-muted/30 p-0.5" role="group" aria-label="{{ __('Mod acara') }}">
                                @foreach ($modeTabs as $modeKey => $modeLabel)
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1 text-xs font-semibold transition-colors"
                                        :class="fieldMode === '{{ $modeKey }}' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                        :aria-pressed="fieldMode === '{{ $modeKey }}'"
                                        @click="fieldMode = '{{ $modeKey }}'"
                                    >
                                        {{ $modeLabel }}
                                        @if ($modeKey === $currentMode)
                                            <span class="rounded-full bg-primary/10 px-1.5 py-px text-[10px] font-semibold uppercase tracking-wide text-primary">{{ __('Aktif') }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <p
                            x-show="fieldMode !== currentMode"
                            x-cloak
                            class="flex items-start gap-2 rounded-md border border-amber-300/70 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            <i class="ri-information-line mt-px" aria-hidden="true"></i>
                            <span>{{ __('Anda sedang mengedit tetapan untuk mod yang tidak aktif. Ia digunakan apabila mod acara ditukar.') }}</span>
                        </p>

                        <ul class="divide-y divide-border/50 overflow-hidden rounded-xl border border-border/70 bg-card">
                            <li class="flex items-center gap-3 bg-muted/30 px-3 py-2.5">
                                <i class="ri-lock-2-line text-base text-muted-foreground" aria-hidden="true"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-foreground">{{ __('Nama Pegawai') }}</p>
                                    <p class="text-xs text-muted-foreground">{{ __('Sentiasa dipaparkan') }}</p>
                                </div>
                            </li>

                            <template x-for="(field, i) in currentFields()" :key="field.key">
                                <li class="flex items-center gap-2 px-3 py-2.5 transition-colors sm:gap-3" :class="field.visible ? '' : 'bg-muted/20'">
                                    <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                                        <input type="checkbox" class="peer sr-only" :checked="field.visible" @change="toggleField(i)">
                                        <span
                                            class="field-switch"
                                            aria-hidden="true"
                                        ></span>
                                        <i class="text-base" :class="[defs[field.key].icon, field.visible ? 'text-foreground' : 'text-muted-foreground']" aria-hidden="true"></i>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-medium" :class="field.visible ? 'text-foreground' : 'text-muted-foreground'" x-text="defs[field.key].label"></span>
                                            <span
                                                class="block truncate pr-0.5 text-xs"
                                                :class="sampleValue(field.key) === null ? 'italic text-amber-600 dark:text-amber-400' : 'text-muted-foreground'"
                                                x-text="sampleValue(field.key) ?? noDataText"
                                            ></span>
                                        </span>
                                    </label>

                                    <button
                                        type="button"
                                        class="inline-flex h-7 shrink-0 items-center gap-1 rounded-md border px-2 text-[11px] font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                        :class="field.label ? 'border-primary/30 bg-primary/10 text-primary' : 'border-input text-muted-foreground hover:bg-accent'"
                                        :disabled="!field.visible"
                                        :aria-pressed="field.label"
                                        :title="labelTitle(field)"
                                        @click="toggleLabel(i)"
                                    >
                                        <i class="ri-price-tag-3-line" aria-hidden="true"></i>
                                        <span class="hidden sm:inline">{{ __('Label') }}</span>
                                    </button>

                                    <div class="flex shrink-0 items-center">
                                        <button
                                            type="button"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground disabled:opacity-30 disabled:hover:bg-transparent"
                                            :disabled="i === 0"
                                            :aria-label="moveLabel(field, -1)"
                                            :title="moveLabel(field, -1)"
                                            @click="moveField(i, -1)"
                                        >
                                            <i class="ri-arrow-up-s-line" aria-hidden="true"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground disabled:opacity-30 disabled:hover:bg-transparent"
                                            :disabled="i === currentFields().length - 1"
                                            :aria-label="moveLabel(field, 1)"
                                            :title="moveLabel(field, 1)"
                                            @click="moveField(i, 1)"
                                        >
                                            <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        <p x-show="visibleCount() === 0" x-cloak class="text-xs text-muted-foreground">
                            {{ __('Tiada data dipilih — hanya nama pegawai akan dipaparkan.') }}
                        </p>

                        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input type="checkbox" class="peer sr-only" x-model="values.hide_empty" @change="queueFieldSave()">
                                <span
                                    class="field-switch"
                                    aria-hidden="true"
                                ></span>
                                <span class="text-sm text-foreground">
                                    {{ __('Sembunyi baris tanpa data') }}
                                    <span class="block text-xs text-muted-foreground">{{ __('Elak tanda "—" pada skrin apabila data kosong.') }}</span>
                                </span>
                            </label>

                            <div class="flex items-center gap-3">
                                <span class="text-xs tabular-nums text-muted-foreground" x-text="countText()"></span>
                                <button
                                    type="button"
                                    class="text-xs font-medium text-muted-foreground underline-offset-2 hover:text-foreground hover:underline"
                                    @click="resetFields()"
                                >
                                    {{ __('Tetap semula lalai') }}
                                </button>
                            </div>
                        </div>
                    </section>

                    {{-- Kedudukan --}}
                    <section
                        id="tabpanel-kedudukan"
                        role="tabpanel"
                        aria-labelledby="tab-kedudukan"
                        x-show="tab === 'kedudukan'"
                        x-cloak
                        class="space-y-5"
                    >
                        <p class="text-sm text-muted-foreground">{{ __('Semua nilai kedudukan menggunakan unit px. Pratonton memaparkan nilai Desktop.') }}</p>

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
                    <section
                        id="tabpanel-fon"
                        role="tabpanel"
                        aria-labelledby="tab-fon"
                        x-show="tab === 'fon'"
                        x-cloak
                        class="space-y-5"
                    >
                        <p class="text-sm text-muted-foreground">
                            {{ __('Hanya baris yang ditanda pada tab dipilih ditunjukkan. Saiz dalam unit px; PTJ guna kelas Tailwind untuk responsif.') }}
                        </p>

                        <div class="grid grid-cols-[minmax(5.5rem,8rem)_repeat(3,minmax(0,1fr))] gap-x-3 gap-y-4 sm:gap-x-4">
                            <div></div>
                            @foreach ($bps as $bp => $meta)
                                <div>
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider {{ $bp === 'md' ? 'text-primary' : 'text-muted-foreground' }}">{{ $meta['label'] }}</span>
                                    <span class="block text-[11px] text-muted-foreground">{{ $meta['hint'] }}</span>
                                </div>
                            @endforeach

                            @foreach ($fontRows as $row)
                                <div class="contents" @if ($row['field']) x-show="isFieldVisible('{{ $row['field'] }}')" @endif>
                                    <div class="flex items-center gap-2 pt-2 text-sm font-medium text-foreground">
                                        <i class="{{ $row['icon'] }} text-muted-foreground" aria-hidden="true"></i>
                                        {{ $row['label'] }}
                                    </div>

                                    @if ($row['type'] === 'ptj')
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
                                    @else
                                        @foreach (array_keys($bps) as $bp)
                                            @php $key = $row['key'].'_'.$bp; $name = sprintf($row['name'], $bp); @endphp
                                            <div class="min-w-0">
                                                <div class="relative">
                                                    <input
                                                        type="number"
                                                        id="{{ $key }}"
                                                        name="{{ $name }}"
                                                        min="10"
                                                        max="200"
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
                                                    min="10"
                                                    max="200"
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
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- Gaya fon --}}
                    <section
                        id="tabpanel-fonstyle"
                        role="tabpanel"
                        aria-labelledby="tab-fonstyle"
                        x-show="tab === 'fonstyle'"
                        x-cloak
                        class="space-y-4"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 space-y-2">
                                <p class="text-sm text-muted-foreground">
                                    {{ __('Pilih bahagian untuk ubah fon, ketebalan, gaya dan jarak huruf. Klik teks pada pratonton untuk terus ke bahagian itu.') }}
                                </p>
                                <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-medium">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-muted-foreground">
                                        <i class="ri-font-family" aria-hidden="true"></i>
                                        <span x-text="libraryCountText()"></span>
                                    </span>
                                    <span
                                        x-show="customisedParts().length > 0"
                                        x-cloak
                                        class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-primary"
                                    >
                                        <i class="ri-brush-line" aria-hidden="true"></i>
                                        <span x-text="customisedText()"></span>
                                    </span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-sm gap-1.5"
                                    :disabled="customisedParts().length === 0"
                                    @click="resetAllTypo()"
                                >
                                    <i class="ri-restart-line" aria-hidden="true"></i>
                                    {{ __('Set semula semua') }}
                                </button>
                                <a
                                    href="{{ route('media.kawalan.fon.index') }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn btn-outline btn-sm gap-1.5"
                                    title="{{ __('Dibuka dalam tab baharu. Senarai fon di sini dikemas kini sendiri apabila anda kembali.') }}"
                                >
                                    <i class="ri-upload-2-line" aria-hidden="true"></i>
                                    {{ __('Pustaka Fon') }}
                                    <i class="ri-external-link-line text-xs opacity-60" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>

                        {{-- Undo for bulk changes (copy to all / reset all / reset one) --}}
                        <div
                            x-show="typoUndo"
                            x-transition.opacity
                            x-cloak
                            role="status"
                            aria-live="polite"
                            class="flex items-center justify-between gap-3 rounded-lg border border-border/70 bg-muted/40 px-3 py-2 text-sm"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <i class="ri-checkbox-circle-line shrink-0 text-emerald-600" aria-hidden="true"></i>
                                <span class="truncate" x-text="typoUndo?.message"></span>
                            </span>
                            <span class="flex shrink-0 items-center gap-1">
                                <button type="button" class="rounded-md px-2 py-1 text-sm font-medium text-primary hover:bg-primary/10" @click="undoTypo()">
                                    {{ __('Buat asal') }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                                    @click="dismissTypoUndo()"
                                    aria-label="{{ __('Tutup') }}"
                                >
                                    <i class="ri-close-line" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>

                        <div
                            x-show="libraryFonts.length === 0"
                            x-cloak
                            class="flex items-start gap-3 rounded-xl border border-dashed border-primary/30 bg-primary/5 px-4 py-3 text-sm"
                        >
                            <i class="ri-font-family mt-0.5 text-lg text-primary" aria-hidden="true"></i>
                            <div>
                                <p class="font-medium text-foreground">{{ __('Belum ada fon dimuat naik') }}</p>
                                <p class="text-muted-foreground">
                                    {{ __('Semua bahagian guna fon lalai (Inter). Ketebalan, gaya dan jarak huruf tetap boleh diubah.') }}
                                    <a href="{{ route('media.kawalan.fon.index') }}" target="_blank" rel="noopener" class="font-medium text-primary hover:underline">{{ __('Muat naik fon') }}</a>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            @foreach ($typoParts as $part => $partMeta)
                                <div
                                    id="typo-part-{{ $part }}"
                                    class="scroll-mt-6 overflow-hidden rounded-xl border bg-card shadow-sm transition-colors"
                                    :class="openPart === '{{ $part }}' ? 'border-primary/40 ring-1 ring-primary/15' : 'border-border/70 hover:border-border'"
                                    x-show="isTypoPartShown('{{ $part }}')"
                                    @mouseenter="hoverPart = '{{ $part }}'"
                                    @mouseleave="hoverPart = null"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring"
                                        :aria-expanded="openPart === '{{ $part }}'"
                                        aria-controls="typo-body-{{ $part }}"
                                        @click="togglePart('{{ $part }}')"
                                    >
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors"
                                            :class="openPart === '{{ $part }}' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                                        >
                                            <i class="{{ $partMeta['icon'] }}" aria-hidden="true"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-center gap-1.5 text-sm font-semibold text-foreground">
                                                {{ $partMeta['label'] }}
                                                <span
                                                    x-show="isPartCustomised('{{ $part }}')"
                                                    x-cloak
                                                    class="rounded-full bg-primary/10 px-1.5 py-px text-[10px] font-medium text-primary"
                                                >{{ __('Diubah suai') }}</span>
                                                @if ($part !== 'name')
                                                    <span
                                                        x-show="!isFieldVisible('{{ $part }}')"
                                                        x-cloak
                                                        class="inline-flex items-center gap-1 rounded-full bg-muted px-1.5 py-px text-[10px] font-medium text-muted-foreground"
                                                    ><i class="ri-eye-off-line" aria-hidden="true"></i>{{ __('Tidak dipaparkan') }}</span>
                                                @endif
                                                <span
                                                    x-show="isPartDirty('{{ $part }}')"
                                                    x-cloak
                                                    class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                                    title="{{ __('Perubahan belum disimpan') }}"
                                                ></span>
                                            </span>
                                            <span class="mt-0.5 block truncate text-xs text-muted-foreground" x-text="partSummary('{{ $part }}')"></span>
                                        </span>
                                        <span
                                            x-show="openPart !== '{{ $part }}'"
                                            class="hidden max-w-[40%] truncate text-lg uppercase leading-tight text-foreground sm:block"
                                            :style="typoStyle('{{ $part }}')"
                                            x-text="typoSpecimenText('{{ $part }}')"
                                            aria-hidden="true"
                                        ></span>
                                        <i
                                            class="ri-arrow-down-s-line shrink-0 text-lg text-muted-foreground transition-transform duration-200"
                                            :class="openPart === '{{ $part }}' ? 'rotate-180' : ''"
                                            aria-hidden="true"
                                        ></i>
                                    </button>

                                    <div id="typo-body-{{ $part }}" x-show="openPart === '{{ $part }}'" x-collapse x-cloak>
                                        <div class="space-y-5 border-t border-border/60 px-4 pb-4 pt-4">
                                            {{-- Specimen on the slide background --}}
                                            <div class="relative overflow-hidden rounded-lg bg-cover bg-center" :style="specimenBackdropStyle()">
                                                <div class="absolute inset-0 bg-black/25" aria-hidden="true"></div>
                                                <p
                                                    class="relative break-words px-5 py-7 text-center text-3xl uppercase leading-tight text-white [text-shadow:0_1px_8px_rgb(0_0_0/0.35)]"
                                                    :style="typoStyle('{{ $part }}')"
                                                    x-text="typoSpecimenText('{{ $part }}')"
                                                ></p>
                                            </div>

                                            {{-- Font --}}
                                            <fieldset id="typo_{{ $part }}_font">
                                                <legend class="mb-2 flex w-full items-center justify-between gap-2 text-xs font-medium text-muted-foreground">
                                                    <span>{{ __('Fon') }}</span>
                                                    <span class="truncate text-foreground" x-text="fontName(values.typo_{{ $part }}_font)"></span>
                                                </legend>
                                                <div class="grid max-h-60 grid-cols-3 gap-2 overflow-y-auto p-0.5 sm:grid-cols-4">
                                                    <label class="font-tile group">
                                                        <input
                                                            type="radio"
                                                            class="sr-only"
                                                            name="typo-{{ $part }}-font"
                                                            value=""
                                                            :checked="values.typo_{{ $part }}_font === null"
                                                            @change="setTypoFont('{{ $part }}', null)"
                                                        >
                                                        <span class="text-2xl leading-none" :style="{ fontFamily: typo.defaultStack }">Aa</span>
                                                        <span class="w-full truncate text-[11px]">{{ __('Lalai (Inter)') }}</span>
                                                        <i class="ri-checkbox-circle-fill font-tile-check" aria-hidden="true"></i>
                                                    </label>
                                                    <template x-for="font in libraryFonts" :key="font.id">
                                                        <label class="font-tile group" :title="font.name">
                                                            <input
                                                                type="radio"
                                                                class="sr-only"
                                                                name="typo-{{ $part }}-font"
                                                                :value="font.id"
                                                                :checked="values.typo_{{ $part }}_font === font.id"
                                                                @change="setTypoFont('{{ $part }}', font.id)"
                                                            >
                                                            <span class="text-2xl leading-none" :style="{ fontFamily: fontFamily(font.id) }">Aa</span>
                                                            <span class="w-full truncate text-[11px]" x-text="font.name"></span>
                                                            <i class="ri-checkbox-circle-fill font-tile-check" aria-hidden="true"></i>
                                                        </label>
                                                    </template>
                                                    <a
                                                        href="{{ route('media.kawalan.fon.index') }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="flex min-h-[4.5rem] flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-border px-2 py-2 text-muted-foreground transition-colors hover:border-primary/50 hover:bg-primary/5 hover:text-primary"
                                                    >
                                                        <i class="ri-add-line text-xl leading-none" aria-hidden="true"></i>
                                                        <span class="text-[11px] font-medium">{{ __('Tambah fon') }}</span>
                                                    </a>
                                                </div>
                                                <template x-if="errors.typo_{{ $part }}_font">
                                                    <p class="mt-1.5 text-xs text-destructive" x-text="errors.typo_{{ $part }}_font"></p>
                                                </template>
                                            </fieldset>

                                            {{-- Weight --}}
                                            <fieldset id="typo_{{ $part }}_weight">
                                                <legend class="mb-2 flex w-full items-center justify-between gap-2 text-xs font-medium text-muted-foreground">
                                                    <span>{{ __('Ketebalan') }}</span>
                                                    <span class="text-foreground" x-text="weightLabel(values.typo_{{ $part }}_weight)"></span>
                                                </legend>
                                                <div class="grid grid-cols-5 gap-1 rounded-lg bg-muted/50 p-1">
                                                    @foreach ($weightOptions as $weight => $weightLabel)
                                                        <label class="segment-option flex-col py-1.5" title="{{ $weightLabel }}">
                                                            <input
                                                                type="radio"
                                                                class="sr-only"
                                                                name="typo-{{ $part }}-weight"
                                                                value="{{ $weight }}"
                                                                x-model.number="values.typo_{{ $part }}_weight"
                                                            >
                                                            <span
                                                                class="text-lg leading-none"
                                                                :style="{ fontFamily: typoStyle('{{ $part }}').fontFamily, fontWeight: {{ $weight }} }"
                                                                aria-hidden="true"
                                                            >Aa</span>
                                                            <span class="mt-1 text-[10px] tabular-nums">{{ $weight }}</span>
                                                            <span class="sr-only">{{ $weightLabel }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <p
                                                    x-show="values.typo_{{ $part }}_font !== null"
                                                    x-cloak
                                                    class="mt-1.5 flex items-start gap-1.5 text-[11px] text-muted-foreground"
                                                >
                                                    <i class="ri-information-line mt-px" aria-hidden="true"></i>
                                                    {{ __('Kebanyakan fail fon hanya ada satu ketebalan; ketebalan lain dijana oleh pelayar. Muat naik fail Bold berasingan untuk hasil terbaik.') }}
                                                </p>
                                            </fieldset>

                                            <div class="grid gap-5 sm:grid-cols-[auto_minmax(0,1fr)]">
                                                {{-- Italic --}}
                                                <fieldset>
                                                    <legend class="mb-2 text-xs font-medium text-muted-foreground">{{ __('Gaya') }}</legend>
                                                    <div class="grid grid-cols-2 gap-1 rounded-lg bg-muted/50 p-1">
                                                        @foreach ([false => __('Tegak'), true => __('Italik')] as $isItalic => $styleLabel)
                                                            <label class="segment-option flex-col px-4 py-1.5">
                                                                <input
                                                                    type="radio"
                                                                    class="sr-only"
                                                                    name="typo-{{ $part }}-italic"
                                                                    :checked="{{ $isItalic ? '' : '!' }}values.typo_{{ $part }}_italic"
                                                                    @change="values.typo_{{ $part }}_italic = {{ $isItalic ? 'true' : 'false' }}"
                                                                >
                                                                <span
                                                                    class="text-lg leading-none"
                                                                    :style="{ fontFamily: typoStyle('{{ $part }}').fontFamily, fontStyle: '{{ $isItalic ? 'italic' : 'normal' }}' }"
                                                                    aria-hidden="true"
                                                                >Aa</span>
                                                                <span class="mt-1 text-[10px]">{{ $styleLabel }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </fieldset>

                                                {{-- Letter spacing --}}
                                                <fieldset class="min-w-0">
                                                    <legend class="mb-2 text-xs font-medium text-muted-foreground">{{ __('Jarak huruf') }}</legend>
                                                    <div class="grid grid-cols-4 gap-1 rounded-lg bg-muted/50 p-1">
                                                        @foreach ($trackingOptions as $trackingKey => $trackingLabel)
                                                            <label class="segment-option flex-col px-1 py-1.5" title="{{ $trackingLabel }}">
                                                                <input
                                                                    type="radio"
                                                                    class="sr-only"
                                                                    name="typo-{{ $part }}-tracking"
                                                                    value="{{ $trackingKey }}"
                                                                    x-model="values.typo_{{ $part }}_tracking"
                                                                >
                                                                <span
                                                                    class="text-lg leading-none"
                                                                    :style="{ fontFamily: typoStyle('{{ $part }}').fontFamily, letterSpacing: '{{ $trackingCss[$trackingKey] }}' }"
                                                                    aria-hidden="true"
                                                                >ABC</span>
                                                                <span class="mt-1 max-w-full truncate text-[10px]">{{ $trackingLabel }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-border/60 pt-3">
                                                <button
                                                    type="button"
                                                    class="btn btn-ghost btn-sm gap-1.5"
                                                    :disabled="!isPartCustomised('{{ $part }}')"
                                                    @click="resetPart('{{ $part }}')"
                                                >
                                                    <i class="ri-restart-line" aria-hidden="true"></i>
                                                    {{ __('Set semula ke lalai') }}
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline btn-sm gap-1.5"
                                                    @click="applyStyleToAll('{{ $part }}')"
                                                >
                                                    <i class="ri-file-copy-line" aria-hidden="true"></i>
                                                    {{ __('Salin ke semua bahagian') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Parts not on the slide for the selected mode --}}
                        <div
                            x-show="hiddenTypoParts().length > 0"
                            x-cloak
                            class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-dashed border-border px-3 py-2 text-xs text-muted-foreground"
                        >
                            <span class="flex min-w-0 items-center gap-1.5">
                                <i class="ri-eye-off-line shrink-0" aria-hidden="true"></i>
                                <span x-text="hiddenPartsText()"></span>
                            </span>
                            <button
                                type="button"
                                class="shrink-0 rounded-md px-2 py-1 font-medium text-primary hover:bg-primary/10"
                                @click="showHiddenTypo = !showHiddenTypo"
                                x-text="showHiddenTypo ? @js(__('Sembunyikan')) : @js(__('Tunjuk juga'))"
                            ></button>
                        </div>
                    </section>
                </div>

                {{-- Sticky action bar — position/font tabs only; Data Dipaparkan auto-saves --}}
                <div x-show="tab !== 'data'" x-cloak class="sticky bottom-4 z-10 mt-10 lg:order-3 lg:col-span-2">
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
            const clonePlain = (value) => JSON.parse(JSON.stringify(value));

            window.presentationSettings = (init) => ({
                values: clonePlain(init.formValues),
                saved: clonePlain(init.formValues),
                profiles: init.profiles,
                activeProfileId: init.activeProfileId,
                loadedProfileId: init.activeProfileId,
                max: init.max,
                routes: init.routes,
                errors: {},
                busy: false,

                // Data lines — each event mode keeps its own selection; the picker follows the selected tab
                defs: init.fieldDefs,
                fieldDefaults: init.fieldDefaults,
                currentMode: init.currentMode,
                fieldMode: init.currentMode,
                sampleValues: Object.fromEntries(Object.entries(init.fieldDefs).map(([key, def]) => [key, def.sample])),
                noDataText: @json(__('Tiada data pada pegawai ini')),
                tabs: ['data', 'kedudukan', 'fon', 'fonstyle'],
                typo: init.typo,
                libraryFonts: init.libraryFonts,
                openPart: 'name',
                hoverPart: null,
                showHiddenTypo: false,
                typoUndo: null,
                typoUndoTimer: null,
                tab: 'data',
                fieldSaveState: 'idle',
                fieldSaving: false,
                fieldSaveQueued: false,
                fieldSaveTimer: null,
                fieldSavedTimer: null,

                // Live preview — Desktop (md ≥ 768px) only, virtual stage 1280×720
                stage: { w: 1280, h: 720 },
                previewWidth: 0,
                previewSource: init.previewOfficer ? 'real' : 'sample',
                preview: init.previewOfficer,
                sampleName: init.sampleName,
                ptjPx: init.ptjPx,
                backdropUrl: init.backdropUrl,

                init() {
                    window.addEventListener('beforeunload', (e) => {
                        if (this.isDirty() || this.fieldSaving || this.fieldSaveState === 'pending') {
                            e.preventDefault();
                            e.returnValue = '';
                        }
                    });

                    const hashTab = window.location.hash.replace('#', '');
                    if (this.tabs.includes(hashTab)) {
                        this.tab = hashTab;
                    }

                    // Fonts may be uploaded/deleted in the Pustaka Fon tab — pick them up on return
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.refreshLibrary();
                        }
                    });

                    window.addEventListener('keydown', (e) => {
                        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                            e.preventDefault();
                            this.save();
                        }
                    });
                },

                setTab(key, focus = false) {
                    this.tab = key;
                    history.replaceState(null, '', '#' + key);

                    if (focus) {
                        this.$nextTick(() => document.getElementById('tab-' + key)?.focus());
                    }
                },

                focusTab(step) {
                    const last = this.tabs.length - 1;
                    const current = this.tabs.indexOf(this.tab);
                    const next = step === 'first' ? 0 : step === 'last' ? last : (current + step + last + 1) % (last + 1);

                    this.setTab(this.tabs[next], true);
                },

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
                        ...this.typoVars(),
                    };
                },

                // Same variables the slideshow gets from PresentationDisplayConfigService::typographyVars()
                typoVars() {
                    const vars = {};
                    this.typo.parts.forEach((part) => {
                        const style = this.typoStyle(part);
                        vars[`--officer-${part}-font-family`] = style.fontFamily;
                        vars[`--officer-${part}-font-weight`] = String(style.fontWeight);
                        vars[`--officer-${part}-font-style`] = style.fontStyle;
                        vars[`--officer-${part}-letter-spacing`] = style.letterSpacing;
                    });

                    return vars;
                },

                typoStyle(part) {
                    const v = this.values;
                    const fontId = v[`typo_${part}_font`];

                    return {
                        fontFamily: this.fontFamily(fontId),
                        fontWeight: Number(v[`typo_${part}_weight`]) || 500,
                        fontStyle: v[`typo_${part}_italic`] ? 'italic' : 'normal',
                        letterSpacing: this.typo.tracking[v[`typo_${part}_tracking`]] ?? '0em',
                    };
                },

                typoSpecimenText(part) {
                    if (part === 'name') {
                        return this.previewName();
                    }

                    return this.sampleValue(part) ?? this.sampleValues[part] ?? this.defs[part]?.label ?? '';
                },

                fontFamily(fontId) {
                    return fontId ? `"apcfont-${Number(fontId)}", ${this.typo.defaultStack}` : this.typo.defaultStack;
                },

                fontName(fontId) {
                    if (fontId === null || fontId === undefined) {
                        return @json(__('Lalai (Inter)'));
                    }

                    return this.libraryFonts.find((font) => font.id === fontId)?.name ?? @json(__('Fon tidak ditemui'));
                },

                weightLabel(weight) {
                    return `${this.typo.weightLabels[weight] ?? ''} (${weight})`;
                },

                setTypoFont(part, fontId) {
                    this.values[`typo_${part}_font`] = fontId === null ? null : Number(fontId);
                },

                // "Jawatan: Georgia · Tebal · Italik · Renggang"
                partSummary(part) {
                    const v = this.values;
                    const bits = [
                        this.fontName(v[`typo_${part}_font`]),
                        this.typo.weightLabels[v[`typo_${part}_weight`]] ?? v[`typo_${part}_weight`],
                    ];
                    if (v[`typo_${part}_italic`]) {
                        bits.push(@json(__('Italik')));
                    }
                    if (v[`typo_${part}_tracking`] !== 'normal') {
                        bits.push(this.typo.trackingLabels[v[`typo_${part}_tracking`]]);
                    }

                    return bits.join(' · ');
                },

                partStyle(part, source = this.values) {
                    return {
                        font: source[`typo_${part}_font`] ?? null,
                        weight: Number(source[`typo_${part}_weight`]),
                        italic: !!source[`typo_${part}_italic`],
                        tracking: source[`typo_${part}_tracking`],
                    };
                },

                defaultPartStyle(part) {
                    const d = this.typo.defaults[part];

                    return { font: d.font_id, weight: d.weight, italic: d.italic, tracking: d.tracking };
                },

                setPartStyle(part, style) {
                    this.values[`typo_${part}_font`] = style.font;
                    this.values[`typo_${part}_weight`] = style.weight;
                    this.values[`typo_${part}_italic`] = style.italic;
                    this.values[`typo_${part}_tracking`] = style.tracking;
                },

                isPartCustomised(part) {
                    return JSON.stringify(this.partStyle(part)) !== JSON.stringify(this.defaultPartStyle(part));
                },

                isPartDirty(part) {
                    return JSON.stringify(this.partStyle(part)) !== JSON.stringify(this.partStyle(part, this.saved));
                },

                customisedParts() {
                    return this.typo.parts.filter((part) => this.isPartCustomised(part));
                },

                customisedText() {
                    const count = this.customisedParts().length;

                    return count + ' ' + @json(__('bahagian diubah suai'));
                },

                libraryCountText() {
                    const count = this.libraryFonts.length;

                    return count === 0 ? @json(__('Tiada fon dalam pustaka')) : count + ' ' + @json(__('fon dalam pustaka'));
                },

                isTypoPartShown(part) {
                    return part === 'name' || this.showHiddenTypo || this.isFieldVisible(part);
                },

                hiddenTypoParts() {
                    return this.typo.parts.filter((part) => part !== 'name' && !this.isFieldVisible(part));
                },

                hiddenPartsText() {
                    const parts = this.hiddenTypoParts().map((part) => this.typo.labels[part]);
                    const mode = this.typo.modeLabels[this.fieldMode] ?? this.fieldMode;

                    return @json(__('Tidak dipaparkan dalam mod')) + ` ${mode}: ${parts.join(', ')}`;
                },

                togglePart(part) {
                    this.openPart = this.openPart === part ? null : part;
                },

                // Clicking a line in the preview opens that part (Gaya Fon tab only)
                pickPreviewPart(part) {
                    if (this.tab !== 'fonstyle' || !this.typo.parts.includes(part)) {
                        return;
                    }
                    this.openPart = part;
                    this.$nextTick(() => {
                        document.getElementById('typo-part-' + part)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    });
                },

                previewPartClass(part) {
                    if (this.tab !== 'fonstyle') {
                        return '';
                    }

                    return (this.hoverPart ?? this.openPart) === part ? 'typo-target is-active' : 'typo-target';
                },

                specimenBackdropStyle() {
                    return {
                        backgroundImage: this.backdropUrl
                            ? `url("${this.backdropUrl}")`
                            : 'linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%)',
                    };
                },

                // Bulk edits keep a snapshot so they can be undone from the inline bar
                rememberTypo(message, change) {
                    const snapshot = Object.fromEntries(this.typo.parts.map((part) => [part, this.partStyle(part)]));
                    change();
                    clearTimeout(this.typoUndoTimer);
                    this.typoUndo = { message, snapshot };
                    this.typoUndoTimer = setTimeout(() => this.dismissTypoUndo(), 10000);
                },

                undoTypo() {
                    if (!this.typoUndo) {
                        return;
                    }
                    Object.entries(this.typoUndo.snapshot).forEach(([part, style]) => this.setPartStyle(part, style));
                    this.dismissTypoUndo();
                },

                dismissTypoUndo() {
                    clearTimeout(this.typoUndoTimer);
                    this.typoUndo = null;
                },

                applyStyleToAll(source) {
                    const style = this.partStyle(source);
                    const targets = this.typo.parts.filter((part) => part !== source);
                    const message = @json(__('Gaya :part disalin ke :count bahagian lain.'))
                        .replace(':part', this.typo.labels[source])
                        .replace(':count', targets.length);

                    this.rememberTypo(message, () => targets.forEach((part) => this.setPartStyle(part, { ...style })));
                },

                resetPart(part) {
                    const message = @json(__('Gaya :part diset semula ke lalai.')).replace(':part', this.typo.labels[part]);

                    this.rememberTypo(message, () => this.setPartStyle(part, this.defaultPartStyle(part)));
                },

                resetAllTypo() {
                    this.rememberTypo(@json(__('Semua bahagian diset semula ke fon lalai.')), () => {
                        this.typo.parts.forEach((part) => this.setPartStyle(part, this.defaultPartStyle(part)));
                    });
                },

                async refreshLibrary() {
                    try {
                        const { data } = await window.axios.get(this.routes.fontList);
                        const before = new Set(this.libraryFonts.map((font) => font.id));
                        const fonts = data.fonts.map((font) => ({ id: font.id, name: font.name, family: font.family }));
                        const ids = new Set(fonts.map((font) => font.id));

                        const style = document.getElementById('apc-library-font-faces');
                        if (style) {
                            style.textContent = data.font_face_css;
                        }
                        this.libraryFonts = fonts;

                        // A deleted font was already reset to the default on the server (live + profiles)
                        const scrub = (state) => this.typo.parts.forEach((part) => {
                            const key = `typo_${part}_font`;
                            if (state[key] !== null && !ids.has(state[key])) {
                                state[key] = null;
                            }
                        });
                        scrub(this.values);
                        scrub(this.saved);
                        this.profiles.forEach((profile) => scrub(profile.form_values));

                        const added = fonts.filter((font) => !before.has(font.id));
                        if (added.length > 0) {
                            this.toast('success', @json(__('Fon baharu tersedia')) + ': ' + added.map((font) => font.name).join(', '));
                        }
                    } catch (e) {
                        // Keep the current list; the next return to this tab retries.
                    }
                },

                usingRealData() {
                    return this.preview !== null && this.previewSource === 'real';
                },

                previewName() {
                    return this.usingRealData() ? this.preview.nama : this.sampleName;
                },

                previewValues() {
                    return this.usingRealData() ? this.preview.values : this.sampleValues;
                },

                sampleValue(key) {
                    const raw = this.previewValues()[key];

                    return raw === null || raw === undefined || String(raw).trim() === '' ? null : String(raw);
                },

                previewLines() {
                    const hide = !!this.values.hide_empty;

                    return this.currentFields()
                        .filter((field) => field.visible)
                        .map((field) => {
                            const value = this.sampleValue(field.key);

                            return { field, value };
                        })
                        .filter(({ value }) => value !== null || !hide)
                        .map(({ field, value }) => {
                            const text = value ?? '—';

                            return { key: field.key, text: field.label ? `${this.defs[field.key].label}: ${text}` : text };
                        });
                },

                dataCaption() {
                    return this.usingRealData() ? @json(__('Data')) + ': ' + this.preview.nama : @json(__('Data contoh'));
                },

                sourceToggleText() {
                    return this.previewSource === 'real' ? @json(__('Guna data contoh')) : @json(__('Guna data sebenar'));
                },

                currentFields() {
                    return this.values['fields_' + this.fieldMode];
                },

                visibleCount() {
                    return this.currentFields().filter((field) => field.visible).length;
                },

                countText() {
                    return this.visibleCount() + ' / ' + this.currentFields().length + ' ' + @json(__('dipaparkan'));
                },

                isFieldVisible(key) {
                    return this.currentFields().some((field) => field.key === key && field.visible);
                },

                toggleField(index) {
                    const field = this.currentFields()[index];
                    field.visible = !field.visible;
                    this.queueFieldSave();
                },

                toggleLabel(index) {
                    const field = this.currentFields()[index];
                    field.label = !field.label;
                    this.queueFieldSave();
                },

                moveField(index, direction) {
                    const list = this.currentFields();
                    const target = index + direction;

                    if (target < 0 || target >= list.length) return;

                    list.splice(target, 0, list.splice(index, 1)[0]);
                    this.queueFieldSave();
                },

                resetFields() {
                    this.values['fields_' + this.fieldMode] = clonePlain(this.fieldDefaults[this.fieldMode]);
                    this.queueFieldSave();
                },

                // Data Dipaparkan auto-save: debounce rapid toggles, never run two requests at once
                queueFieldSave() {
                    this.fieldSaveState = 'pending';
                    clearTimeout(this.fieldSaveTimer);
                    this.fieldSaveTimer = setTimeout(() => this.persistFields(), 400);
                },

                async persistFields() {
                    if (this.fieldSaving) {
                        this.fieldSaveQueued = true;
                        return;
                    }

                    this.fieldSaving = true;
                    this.fieldSaveState = 'saving';

                    const snapshot = {};
                    this.autoKeys().forEach((key) => {
                        snapshot[key] = clonePlain(this.values[key]);
                    });

                    try {
                        const fields = {};
                        Object.keys(this.fieldDefaults).forEach((mode) => {
                            fields[mode] = snapshot['fields_' + mode];
                        });

                        const { data } = await window.axios.put(this.routes.fieldsUpdate, {
                            fields,
                            hide_empty: !!snapshot.hide_empty,
                        });

                        Object.assign(this.saved, snapshot);
                        this.activeProfileId = data.active_profile_id;
                        this.loadedProfileId = data.active_profile_id;
                        this.fieldSaveState = 'saved';

                        clearTimeout(this.fieldSavedTimer);
                        this.fieldSavedTimer = setTimeout(() => {
                            if (this.fieldSaveState === 'saved') this.fieldSaveState = 'idle';
                        }, 2000);
                    } catch (e) {
                        this.fieldSaveState = 'error';
                        this.toastFromException(e);
                    } finally {
                        this.fieldSaving = false;

                        if (this.fieldSaveQueued) {
                            this.fieldSaveQueued = false;
                            this.persistFields();
                        }
                    }
                },

                fieldSaveText() {
                    return {
                        pending: @json(__('Menyimpan…')),
                        saving: @json(__('Menyimpan…')),
                        saved: @json(__('Disimpan')),
                        error: @json(__('Gagal disimpan')),
                    }[this.fieldSaveState] ?? @json(__('Simpan automatik'));
                },

                fieldSaveIcon() {
                    return {
                        pending: 'ri-loader-4-line animate-spin',
                        saving: 'ri-loader-4-line animate-spin',
                        saved: 'ri-check-line',
                        error: 'ri-error-warning-line',
                    }[this.fieldSaveState] ?? 'ri-cloud-line';
                },

                fieldSaveClass() {
                    return {
                        saved: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
                        error: 'bg-destructive/10 text-destructive',
                    }[this.fieldSaveState] ?? 'bg-muted text-muted-foreground';
                },

                labelTitle(field) {
                    return @json(__('Papar awalan')) + ' "' + this.defs[field.key].label + ':"';
                },

                moveLabel(field, direction) {
                    return (direction < 0 ? @json(__('Naikkan')) : @json(__('Turunkan'))) + ' ' + this.defs[field.key].label;
                },

                nudge(key, delta, min, max) {
                    const v = Number(this.values[key]) || 0;
                    this.values[key] = Math.min(max, Math.max(min, v + delta));
                },

                autoKeys() {
                    return Object.keys(this.fieldDefaults).map((mode) => 'fields_' + mode).concat('hide_empty');
                },

                withoutAutoKeys(state) {
                    const copy = { ...state };
                    this.autoKeys().forEach((key) => delete copy[key]);

                    return copy;
                },

                // Data Dipaparkan saves itself, so only position/font edits count as unsaved
                isGroupDirty(prefix) {
                    return Object.keys(this.values).some(
                        (key) => key.startsWith(prefix) && JSON.stringify(this.values[key]) !== JSON.stringify(this.saved[key])
                    );
                },

                isDirty() {
                    return JSON.stringify(this.withoutAutoKeys(this.values)) !== JSON.stringify(this.withoutAutoKeys(this.saved));
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
                    const current = this.values;
                    this.values = clonePlain(this.saved);
                    this.autoKeys().forEach((key) => {
                        this.values[key] = clonePlain(current[key]);
                    });
                    this.errors = {};
                },

                buildConfigPayload() {
                    const v = this.values;
                    const fonts = {};

                    ['name', 'jawatan', 'tarikh', 'bersara', 'tempoh'].forEach((row) => {
                        ['base', 'sm', 'md'].forEach((bp) => {
                            fonts[`${row}_${bp}`] = v[`fonts_${row}_${bp}`];
                        });
                    });
                    fonts.ptj_base = v.fonts_ptj_base;
                    fonts.ptj_sm = v.fonts_ptj_sm;

                    const fields = {};
                    Object.keys(this.fieldDefaults).forEach((mode) => {
                        fields[mode] = clonePlain(v['fields_' + mode]);
                    });

                    const typography = {};
                    this.typo.parts.forEach((part) => {
                        const fontId = v[`typo_${part}_font`];
                        typography[part] = {
                            font_id: fontId ? Number(fontId) : null,
                            weight: Number(v[`typo_${part}_weight`]),
                            italic: !!v[`typo_${part}_italic`],
                            tracking: v[`typo_${part}_tracking`],
                        };
                    });

                    return {
                        position: {
                            mt_base: v.position_mt_base,
                            mt_sm: v.position_mt_sm,
                            mt_md: v.position_mt_md,
                            translate_y: v.position_translate_y,
                        },
                        fonts,
                        fields,
                        hide_empty: !!v.hide_empty,
                        typography,
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

                        this.saved = clonePlain(this.values);
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

                        this.values = clonePlain(data.form_values);
                        this.saved = clonePlain(data.form_values);
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
                            const typo = key.match(/^(?:config\.)?typography\.(\w+)\.(font_id|weight|italic|tracking)$/);
                            const flatKey = typo
                                ? `typo_${typo[1]}_${typo[2] === 'font_id' ? 'font' : typo[2]}`
                                : key.replace('.', '_');
                            flat[flatKey] = e.response.data.errors[key][0];
                        });
                        this.errors = flat;
                        this.showFirstErrorTab(Object.keys(flat));
                        this.toast('error', @json(__('Sila semak semula nilai yang dimasukkan.')));
                        return;
                    }

                    this.toastFromException(e);
                },

                // A validation error can sit on a hidden tab — jump to the one holding it
                showFirstErrorTab(keys) {
                    const first = keys[0] ?? '';

                    if (first.startsWith('position_')) this.setTab('kedudukan');
                    else if (first.startsWith('fonts_')) this.setTab('fon');
                    else if (first.startsWith('typo_')) {
                        this.setTab('fonstyle');
                        this.openPart = first.split('_')[1];
                    }
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
