@php
    use App\Services\DatabaseImportService;

    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';

    $mappingDefaults = array_fill_keys($fillableFields, '');
    $policyDefaults = array_fill_keys($optionalPolicyFields, DatabaseImportService::POLICY_ZERO);

    $fieldLabels = [
        'nama' => __('Nama'),
        'no_kp' => __('No. kad pengenalan'),
        'ptj_id' => __('PTJ (ID)'),
        'jawatan_id' => __('Jawatan (ID)'),
        'gred_id' => __('Gred (ID)'),
        'sesi_majlis_id' => __('Sesi majlis (ID)'),
        'rsvp' => __('RSVP'),
        'no_kerusi' => __('No. kerusi'),
        'no_sijil' => __('No. sijil'),
        'no_meja' => __('No. meja'),
        'no_panggilan_lewat' => __('No. panggilan lewat'),
        'is_attend' => __('Hadir'),
        'is_late' => __('Lewat'),
        's_kehadiran' => __('Sesi kehadiran (0=pagi, 1=petang)'),
        'tarikh_bersara' => __('Tarikh bersara (ddmmyyyy)'),
        'bersara_id' => __('Jenis persaraan (ID)'),
        'tempoh_berkhidmat' => __('Tempoh berkhidmat (cth. 25 tahun)'),
    ];

    $isLookup = $lookup !== null;
    if ($isLookup) {
        $fieldLabels = [$lookup['column'] => $lookup['columnLabel']];
    }
    $hasPolicyColumn = $optionalPolicyFields !== [];

    $tabs = [
        'pegawai' => ['label' => __('Pegawai'), 'icon' => 'ri-team-line'],
        'jawatan' => ['label' => __('Jawatan'), 'icon' => 'ri-briefcase-line'],
        'bersara' => ['label' => __('Bersara'), 'icon' => 'ri-user-heart-line'],
    ];
@endphp

<x-dashboard-layout :title="__('Import pangkalan — Pegawai')" role="admin">
    <style>
        @keyframes iw-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
        @keyframes iw-slide { 0% { transform: translateX(-100%); } 100% { transform: translateX(300%); } }
        @keyframes iw-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes iw-pop { 0% { transform: scale(0.4); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        @keyframes iw-draw { to { stroke-dashoffset: 0; } }
        @keyframes iw-shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
        .iw-float { animation: iw-float 3s ease-in-out infinite; }
        .iw-indeterminate { animation: iw-slide 1.1s ease-in-out infinite; }
        .iw-row { animation: iw-in 0.35s ease-out both; animation-delay: calc(min(var(--i, 0), 14) * 30ms); }
        .iw-pop { animation: iw-pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        .iw-shake { animation: iw-shake 0.35s ease-in-out; }
        .iw-check-circle { stroke-dasharray: 166; stroke-dashoffset: 166; animation: iw-draw 0.6s ease-out 0.1s forwards; }
        .iw-check-mark { stroke-dasharray: 48; stroke-dashoffset: 48; animation: iw-draw 0.4s ease-out 0.65s forwards; }
        @media (prefers-reduced-motion: reduce) {
            .iw-float, .iw-indeterminate, .iw-row, .iw-pop, .iw-shake { animation: none; }
            .iw-check-circle, .iw-check-mark { animation: none; stroke-dashoffset: 0; }
        }
    </style>

    <x-crud-header
        :title="__('Import fail — :name', ['name' => $isLookup ? $lookup['label'] : __('Pegawai')])"
        :description="__('Muat naik CSV atau Excel (.xlsx), pilih pemetaan lajur, pratonton, kemudian import.')"
        :show-create="false"
    />

    <nav class="mb-6 inline-grid grid-cols-3 gap-1 rounded-xl bg-muted/40 p-1" aria-label="{{ __('Jenis data') }}">
        @foreach ($tabs as $tabKey => $tab)
            <a
                href="{{ route('admin.kawalan.database.index', $tabKey === 'pegawai' ? [] : ['dataset' => $tabKey]) }}"
                @if ($dataset === $tabKey) aria-current="page" @endif
                @class([
                    'flex items-center justify-center gap-1.5 rounded-lg px-4 py-1.5 text-sm font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                    'bg-card text-foreground shadow-sm ring-1 ring-border/60' => $dataset === $tabKey,
                    'text-muted-foreground hover:text-foreground' => $dataset !== $tabKey,
                ])
            >
                <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>{{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    @if ($isJasamu && ! $isLookup)
        <div class="iw-row mb-6 flex items-start gap-3 rounded-xl border border-violet-500/30 bg-violet-500/10 p-4 text-sm text-violet-900 dark:text-violet-200">
            <i class="ri-information-line mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold">{{ __('Mod acara: Jasamu Dikenang — hanya medan majlis persaraan dipaparkan') }}</p>
                <p class="mt-1 text-violet-900/80 dark:text-violet-200/80">
                    {{ __('Tarikh bersara, jenis persaraan (ID daripada Kawalan > Bersara) dan tempoh berkhidmat mesti dipetakan dan diisi untuk setiap baris. Tempoh berkhidmat ialah teks bebas, cth. "25 tahun 3 bulan".') }}
                </p>
                <p class="mt-1 text-violet-900/80 dark:text-violet-200/80">
                    {{ __('RSVP, no. kerusi dan no. meja adalah pilihan. Medan lain (sesi, no. sijil, kehadiran) diisi dengan nilai lalai.') }}
                </p>
            </div>
        </div>
    @endif

    <div class="space-y-6" x-cloak x-data="importWizard">
        {{-- Stepper --}}
        <ol class="relative flex items-start justify-between" aria-label="{{ __('Langkah import') }}">
            <div class="absolute inset-x-[12.5%] top-4 h-0.5 rounded-full bg-border" aria-hidden="true">
                <div
                    class="h-full rounded-full bg-primary transition-[width] duration-500 ease-out"
                    :style="`width: ${(step - 1) / 3 * 100}%`"
                ></div>
            </div>
            <template x-for="(label, i) in steps" :key="i">
                <li class="relative z-10 flex w-1/4 flex-col items-center gap-2 text-center" :aria-current="step === i + 1 ? 'step' : null">
                    <button
                        type="button"
                        class="grid h-8 w-8 place-items-center rounded-full border-2 text-sm font-semibold transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-default"
                        :class="step > i + 1 || step === 4
                            ? 'border-primary bg-primary text-primary-foreground'
                            : step === i + 1
                                ? 'scale-110 border-primary bg-background text-primary ring-4 ring-primary/15'
                                : 'border-border bg-background text-muted-foreground'"
                        :disabled="!(i + 1 < step && step < 4)"
                        :aria-label="label"
                        @click="go(i + 1)"
                    >
                        <i x-show="step > i + 1 || step === 4" class="ri-check-line iw-pop"></i>
                        <span x-show="step <= i + 1 && step !== 4" x-text="i + 1"></span>
                    </button>
                    <span
                        class="text-xs font-medium transition-colors duration-300 sm:text-sm"
                        :class="step >= i + 1 ? 'text-foreground' : 'text-muted-foreground'"
                        x-text="label"
                    ></span>
                </li>
            </template>
        </ol>

        {{-- Error banner --}}
        <div
            x-show="message"
            x-transition.opacity.duration.200ms
            role="alert"
            class="flex items-start gap-2.5 rounded-xl border border-destructive/40 bg-destructive/10 p-3.5 text-sm text-destructive"
            :class="message ? 'iw-shake' : ''"
        >
            <i class="ri-error-warning-line mt-0.5 text-lg" aria-hidden="true"></i>
            <p x-text="message"></p>
        </div>

        <div class="relative">
            {{-- Step 1: upload --}}
            <section
                x-show="step === 1"
                x-transition:enter="transition duration-300 ease-out delay-100"
                x-transition:enter-start="translate-y-3 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition duration-100 ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="-translate-y-1 opacity-0"
                class="relative overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-sm"
            >
                <div x-show="loading" class="absolute inset-x-0 top-0 h-0.5 overflow-hidden" aria-hidden="true">
                    <div class="iw-indeterminate h-full w-1/3 bg-primary"></div>
                </div>
                <h2 class="mb-1 text-lg font-semibold">{{ __('1. Muat naik fail') }}</h2>
                <p class="mb-4 text-sm text-muted-foreground">
                    {{ __('Seret dan lepas fail .csv / .xlsx di sini, atau klik untuk pilih.') }}
                    @if ($isLookup)
                        <a href="{{ $lookup['template'] }}" class="ml-1 inline-flex items-center gap-1 font-medium text-primary hover:underline">
                            <i class="ri-download-2-line" aria-hidden="true"></i>{{ __('Muat turun templat CSV') }}
                        </a>
                    @endif
                </p>
                <label
                    class="group flex min-h-[12rem] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-8 text-center transition-all duration-200 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                    :class="dragging
                        ? 'scale-[1.01] border-primary bg-primary/5'
                        : 'border-muted-foreground/30 bg-muted/30 hover:border-primary/50 hover:bg-primary/[0.03]'"
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; uploadFile($event.dataTransfer.files[0])"
                >
                    <input
                        type="file"
                        accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="sr-only"
                        :disabled="loading"
                        @change="uploadFile($event.target.files[0]); $event.target.value = ''"
                    />
                    <span
                        class="mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-primary/10 text-primary transition-transform duration-300 group-hover:-translate-y-1"
                        :class="dragging ? '-translate-y-2 scale-110' : (loading ? '' : 'iw-float')"
                    >
                        <i class="text-3xl" :class="loading ? 'ri-loader-4-line animate-spin' : 'ri-upload-cloud-2-line'"></i>
                    </span>

                    <template x-if="!loading">
                        <span class="block">
                            <span class="block text-sm font-medium text-foreground" x-text="dragging ? @js(__('Lepaskan fail di sini')) : @js(__('Pilih fail'))"></span>
                            <span class="mt-1 block text-xs text-muted-foreground">.csv, .txt, .xlsx — maks 10MB</span>
                        </span>
                    </template>
                    <template x-if="loading">
                        <span class="block w-full max-w-xs">
                            <span class="block truncate text-sm font-medium text-foreground" x-text="fileName"></span>
                            <span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-muted">
                                <span class="block h-full rounded-full bg-primary transition-[width] duration-200" :style="`width: ${uploadPct}%`"></span>
                            </span>
                            <span class="mt-1 block text-xs tabular-nums text-muted-foreground">
                                <span x-text="uploadPct < 100 ? @js(__('Memuat naik…')) : @js(__('Memproses…'))"></span>
                                <span x-text="uploadPct + '%'"></span>
                            </span>
                        </span>
                    </template>
                </label>
            </section>

            {{-- Step 2: mapping --}}
            <section
                x-show="step === 2"
                x-transition:enter="transition duration-300 ease-out delay-100"
                x-transition:enter-start="translate-y-3 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition duration-100 ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="-translate-y-1 opacity-0"
                class="relative overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-sm"
            >
                <div x-show="loading" class="absolute inset-x-0 top-0 h-0.5 overflow-hidden" aria-hidden="true">
                    <div class="iw-indeterminate h-full w-1/3 bg-primary"></div>
                </div>

                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold">{{ __('2. Pemetaan lajur') }}</h2>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium">
                        <span class="inline-flex max-w-[16rem] items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-muted-foreground">
                            <i class="ri-file-text-line"></i><span class="truncate" x-text="fileName"></span>
                        </span>
                        <span class="rounded-full bg-muted px-2.5 py-1 tabular-nums text-muted-foreground">
                            <span x-text="rowCount"></span> {{ __('baris data') }}
                        </span>
                    </div>
                </div>

                <div
                    x-show="autoMapped > 0"
                    x-transition
                    class="mb-4 flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-2.5 text-sm text-emerald-800 dark:text-emerald-300"
                >
                    <i class="ri-magic-line text-lg" aria-hidden="true"></i>
                    <span><b class="tabular-nums" x-text="autoMapped"></b> {{ __('lajur dipadankan secara automatik. Semak dan ubah jika perlu.') }}</span>
                </div>

                <div class="mb-4">
                    <div class="mb-1 flex items-center justify-between text-xs text-muted-foreground">
                        <span>{{ __('Kemajuan pemetaan') }}</span>
                        <span class="tabular-nums"><span x-text="mappedCount"></span> / <span x-text="fillableFields.length"></span></span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-primary transition-[width] duration-500 ease-out" :style="`width: ${mappedCount / fillableFields.length * 100}%`"></div>
                    </div>
                </div>

                <p class="mb-4 text-sm text-muted-foreground">
                    @if ($isLookup)
                        {{ __('Pilih lajur fail yang mengandungi nama. ID baharu akan diberikan secara berurutan (1, 2, 3…) mengikut susunan fail.') }}
                    @else
                        {{ __('Medan bertanda * mesti dipetakan. Untuk medan pilihan: jika lajur kosong atau tiada pemetaan, pilih sama ada storan 0 atau NULL (mengikut jenis medan).') }}
                    @endif
                </p>
                <div class="overflow-x-auto rounded-xl border border-border">
                    <table class="w-full min-w-[40rem] border-collapse text-sm">
                        <thead class="bg-muted/50">
                            <tr class="text-left">
                                <th class="px-3 py-2.5 font-medium">{{ $isLookup ? __('Medan :name', ['name' => $lookup['label']]) : __('Medan Pegawai') }}</th>
                                <th class="px-3 py-2.5 font-medium">{{ __('Lajur fail') }}</th>
                                @if ($hasPolicyColumn)
                                    <th class="px-3 py-2.5 font-medium">{{ __('Jika kosong / tiada lajur') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fillableFields as $field)
                                <tr
                                    class="iw-row border-t border-border/60 transition-colors duration-300"
                                    style="--i: {{ $loop->index }}"
                                    :class="mapping['{{ $field }}'] ? 'bg-primary/[0.04]' : ''"
                                >
                                    <td class="px-3 py-3 align-top">
                                        <span class="flex items-center gap-2">
                                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-xs transition-all duration-300"
                                                :class="mapping['{{ $field }}']
                                                    ? 'bg-primary text-primary-foreground'
                                                    : @js(in_array($field, $requiredMapped, true)) ? 'bg-destructive/15 text-destructive' : 'bg-muted text-muted-foreground'"
                                            >
                                                <i x-show="mapping['{{ $field }}']" class="ri-check-line iw-pop"></i>
                                                <i x-show="!mapping['{{ $field }}']" class="ri-subtract-line"></i>
                                            </span>
                                            <span>
                                                <span class="font-medium">{{ $fieldLabels[$field] ?? $field }}</span>
                                                @if (in_array($field, $requiredMapped, true))
                                                    <span class="text-destructive" title="{{ __('Wajib') }}">*</span>
                                                @endif
                                                <code class="ml-1 text-xs text-muted-foreground">{{ $field }}</code>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 align-top">
                                        <select
                                            class="{{ $inputClass }} transition-colors"
                                            :class="@js(in_array($field, $requiredMapped, true)) && !mapping['{{ $field }}'] ? 'border-destructive/60' : ''"
                                            x-model="mapping['{{ $field }}']"
                                        >
                                            <option value="">{{ __('— Tiada —') }}</option>
                                            <template x-for="h in headers" :key="h">
                                                <option :value="h" x-text="h"></option>
                                            </template>
                                        </select>
                                    </td>
                                    @if ($hasPolicyColumn)
                                    <td class="px-3 py-3 align-top">
                                        @if (in_array($field, $optionalPolicyFields, true))
                                            <div class="inline-grid grid-cols-2 gap-1 rounded-lg bg-muted/40 p-1">
                                                @foreach ([[DatabaseImportService::POLICY_ZERO, '0 / false'], [DatabaseImportService::POLICY_NULL, 'NULL']] as [$pv, $pl])
                                                    <label class="cursor-pointer">
                                                        <input
                                                            type="radio"
                                                            class="peer sr-only"
                                                            :name="'policy_{{ $field }}'"
                                                            value="{{ $pv }}"
                                                            x-model="emptyPolicy['{{ $field }}']"
                                                        />
                                                        <span class="block rounded-md px-3 py-1 text-xs font-medium text-muted-foreground transition peer-checked:bg-card peer-checked:text-foreground peer-checked:shadow-sm peer-checked:ring-1 peer-checked:ring-border/60 peer-focus-visible:ring-2 peer-focus-visible:ring-ring">{{ $pl }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <p class="mt-1 text-xs text-muted-foreground">
                                                @if ($field === 'sesi_majlis_id')
                                                    {{ __('FK: kosong sentiasa NULL. Boolean: kedua-dua pilihan disimpan sebagai tidak aktif.') }}
                                                @elseif (in_array($field, ['rsvp', 'is_attend', 'is_late'], true))
                                                    {{ __('Boolean NOT NULL — nilai kosong disimpan sebagai tidak (0).') }}
                                                @elseif ($field === 's_kehadiran')
                                                    {{ __('NOT NULL — lalai 0 (pagi).') }}
                                                @endif
                                            </p>
                                        @elseif ($field === 'tarikh_bersara')
                                            <span class="text-xs text-muted-foreground">
                                                {{ __('Format: ddmmyyyy (cth. 31122026), dd/mm/yyyy atau yyyy-mm-dd.') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-muted-foreground">—</span>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <button type="button" class="btn btn-outline" @click="step = 1">
                        <i class="ri-arrow-left-line"></i>{{ __('Kembali') }}
                    </button>
                    <button type="button" class="btn disabled:cursor-not-allowed disabled:opacity-50" :disabled="loading || missingRequired.length > 0" @click="runPreview()">
                        <i class="ri-loader-4-line animate-spin" x-show="loading"></i>
                        <span>{{ __('Pratonton') }}</span>
                        <i class="ri-arrow-right-line" x-show="!loading"></i>
                    </button>
                    <p x-show="missingRequired.length > 0" x-transition.opacity class="text-xs text-destructive">
                        {{ __('Petakan medan wajib:') }} <span x-text="missingRequired.join(', ')"></span>
                    </p>
                </div>
            </section>

            {{-- Step 3: preview --}}
            <section
                x-show="step === 3"
                x-transition:enter="transition duration-300 ease-out delay-100"
                x-transition:enter-start="translate-y-3 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition duration-100 ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="-translate-y-1 opacity-0"
                class="relative overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-sm"
            >
                <div x-show="loading" class="absolute inset-x-0 top-0 h-0.5 overflow-hidden" aria-hidden="true">
                    <div class="iw-indeterminate h-full w-1/3 bg-primary"></div>
                </div>
                <h2 class="mb-4 text-lg font-semibold">{{ __('3. Pratonton & sahkan') }}</h2>

                <div class="mb-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-border/70 bg-muted/20 p-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Jumlah baris') }}</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums" x-text="rowCount"></p>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-muted/20 p-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Dipratonton') }}</p>
                        <p class="mt-1 text-2xl font-bold tabular-nums" x-text="preview.length"></p>
                    </div>
                    <div
                        class="rounded-xl border p-3.5 transition-colors duration-300"
                        :class="errors.length ? 'border-destructive/40 bg-destructive/10' : 'border-emerald-500/30 bg-emerald-500/10'"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Ralat') }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-2xl font-bold tabular-nums" :class="errors.length ? 'text-destructive' : 'text-emerald-700 dark:text-emerald-300'">
                            <span x-text="errors.length"></span>
                            <i class="iw-pop text-xl" :class="errors.length ? 'ri-error-warning-line' : 'ri-checkbox-circle-line'"></i>
                        </p>
                    </div>
                </div>

                <template x-if="errors.length">
                    <div class="mb-4 max-h-48 overflow-y-auto rounded-xl border border-destructive/40 bg-destructive/10 p-4 text-sm">
                        <p class="mb-2 font-medium text-destructive">{{ __('Ralat (import tidak akan diteruskan sehingga betul):') }}</p>
                        <ul class="list-inside list-disc space-y-1">
                            <template x-for="(err, i) in errors" :key="i">
                                <li class="iw-row text-destructive" :style="`--i: ${i}`" x-text="err"></li>
                            </template>
                        </ul>
                    </div>
                </template>
                <template x-if="errors.length === 0">
                    <p class="iw-row mb-4 flex items-center gap-2 rounded-xl border border-green-600/30 bg-green-600/10 p-3 text-sm text-green-800 dark:text-green-200">
                        <i class="ri-checkbox-circle-line text-lg"></i>
                        {{ __('Tiada ralat pada pratonton. Anda boleh mengimport.') }}
                    </p>
                </template>

                @if ($isLookup)
                    <div class="iw-row mb-4 rounded-xl border border-destructive/40 bg-destructive/10 p-4 text-sm" role="alert">
                        <p class="flex items-center gap-2 font-semibold text-destructive">
                            <i class="ri-alarm-warning-line text-lg" aria-hidden="true"></i>
                            {{ __('Import ini akan MENGGANTI seluruh jadual :name', ['name' => $lookup['label']]) }}
                        </p>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-destructive/90">
                            <li>
                                {{ __('Jadual dikosongkan dahulu (') }}<b class="tabular-nums" x-text="impact.existing"></b>{{ __(' rekod sedia ada dipadam), kemudian rekod fail dimasukkan dengan ID baharu 1, 2, 3…') }}
                            </li>
                            @if ($lookup['impact']['deletes_pegawai'])
                                <li>
                                    <b class="tabular-nums" x-text="impact.pegawai"></b> {{ __('pegawai akan DIPADAM bersama (setiap pegawai terikat kepada jawatan), termasuk senarai pengumuman.') }}
                                </li>
                            @else
                                <li>
                                    <b class="tabular-nums" x-text="impact.pegawai"></b> {{ __('pegawai akan dinyahpautkan daripada jenis persaraan (dikekalkan, tetapi medan jenis persaraan menjadi kosong).') }}
                                </li>
                            @endif
                            <li>{{ __('Pegawai yang diimport kemudian mesti menggunakan ID baharu ini.') }}</li>
                            @if ($lookup['activeSesi'])
                                <li class="font-semibold">
                                    {{ __('Sesi ":sesi" sedang aktif — import semasa majlis berlangsung amat tidak disyorkan.', ['sesi' => $lookup['activeSesi']]) }}
                                </li>
                            @endif
                        </ul>
                        <label class="mt-3 block text-sm font-medium text-foreground" for="iw-confirm">
                            {{ __('Taip') }} <code class="rounded bg-background px-1.5 py-0.5 font-bold text-destructive">RESET</code> {{ __('untuk mengesahkan:') }}
                        </label>
                        <input
                            id="iw-confirm"
                            type="text"
                            autocomplete="off"
                            spellcheck="false"
                            x-model="confirmText"
                            class="{{ $inputClass }} mt-1 max-w-xs uppercase tracking-widest"
                            placeholder="RESET"
                        />
                    </div>
                @endif

                <p class="mb-2 text-sm text-muted-foreground">
                    {{ __('Menunjukkan sehingga 100 baris pertama selepas pemetaan:') }}
                </p>
                <div class="max-h-96 overflow-auto rounded-xl border border-border">
                    <table class="w-full min-w-[48rem] text-left text-xs" id="import-preview-table">
                        <thead class="sticky top-0 z-10 bg-muted">
                            <tr>
                                @if ($isLookup)
                                    <th class="w-20 whitespace-nowrap px-2 py-2 font-medium">ID</th>
                                    <th class="whitespace-nowrap px-2 py-2 font-medium">{{ $lookup['column'] }}</th>
                                @else
                                    @foreach ($fillableFields as $col)
                                        <th class="whitespace-nowrap px-2 py-2 font-medium">{{ $col }}</th>
                                    @endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, ri) in preview" :key="ri">
                                <tr class="iw-row border-t border-border/60 transition-colors hover:bg-muted/40" :style="`--i: ${ri}`">
                                    @if ($isLookup)
                                        <td class="px-2 py-1 tabular-nums text-muted-foreground" x-text="row.id"></td>
                                        <td class="px-2 py-1" x-text="row.name"></td>
                                    @else
                                        @foreach ($fillableFields as $col)
                                            <td class="whitespace-nowrap px-2 py-1" x-text="row['{{ $col }}'] ?? ''"></td>
                                        @endforeach
                                    @endif
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline" @click="step = 2">
                        <i class="ri-arrow-left-line"></i>{{ __('Kembali ke pemetaan') }}
                    </button>
                    <button
                        type="button"
                        class="btn disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canImport"
                        @click="runImport()"
                    >
                        <i class="ri-loader-4-line animate-spin" x-show="loading"></i>
                        <i class="ri-database-2-line" x-show="!loading"></i>
                        <span x-text="loading ? @js(__('Mengimport…')) : @js(__('Import'))"></span>
                    </button>
                </div>
            </section>

            {{-- Step 4: done --}}
            <section
                x-show="step === 4"
                x-transition:enter="transition duration-300 ease-out delay-100"
                x-transition:enter-start="translate-y-3 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                class="rounded-2xl border border-border bg-card p-8 text-center shadow-sm"
            >
                <svg class="mx-auto h-20 w-20 text-emerald-500" viewBox="0 0 56 56" fill="none" aria-hidden="true">
                    <circle class="iw-check-circle" cx="28" cy="28" r="26" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    <path class="iw-check-mark" d="M16 29l8 8 16-17" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h2 class="mt-4 text-xl font-semibold text-green-700 dark:text-green-300">{{ __('Import selesai') }}</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ __('Berjaya mengimport') }}
                    <strong class="text-2xl tabular-nums text-foreground" x-text="displayCount"></strong>
                    {{ $isLookup ? __('rekod :name.', ['name' => strtolower($lookup['label'])]) : __('rekod pegawai.') }}
                </p>
                @if ($isLookup)
                    <p class="mt-1 text-xs text-muted-foreground" x-show="result.replaced !== undefined">
                        <span x-text="result.replaced"></span> {{ __('rekod lama diganti.') }}
                        <template x-if="result.pegawai > 0">
                            <span>
                                <span x-text="result.pegawai"></span>
                                <span x-text="result.deletes_pegawai ? @js(__('pegawai dipadam.')) : @js(__('pegawai dinyahpautkan.'))"></span>
                            </span>
                        </template>
                    </p>
                @endif
                <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                    <button type="button" class="btn" @click="resetAll()">
                        <i class="ri-upload-2-line"></i>{{ __('Import fail lain') }}
                    </button>
                    <a href="{{ route('admin.kehadiran.index') }}" class="btn btn-outline">
                        <i class="ri-user-follow-line"></i>{{ __('Lihat kehadiran') }}
                    </a>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('importWizard', () => ({
                    steps: @js([__('Muat naik'), __('Pemetaan'), __('Pratonton'), __('Selesai')]),
                    step: 1,
                    headers: [],
                    rowCount: 0,
                    mapping: @js($mappingDefaults),
                    emptyPolicy: @js($policyDefaults),
                    preview: [],
                    errors: [],
                    loading: false,
                    message: '',
                    importedCount: null,
                    displayCount: 0,
                    dragging: false,
                    fileName: '',
                    uploadPct: 0,
                    autoMapped: 0,
                    requiredMapped: @js($requiredMapped),
                    optionalPolicyFields: @js($optionalPolicyFields),
                    fillableFields: @js($fillableFields),
                    fieldLabels: @js($fieldLabels),
                    isLookup: @js($isLookup),
                    impact: @js($lookup['impact'] ?? ['existing' => 0, 'pegawai' => 0]),
                    aliases: @js($lookup['aliases'] ?? []),
                    confirmText: '',
                    result: {},

                    get mappedCount() {
                        return this.fillableFields.filter((f) => this.mapping[f]).length;
                    },
                    get canImport() {
                        return !this.loading && this.errors.length === 0 && (!this.isLookup || this.confirmText.trim().toUpperCase() === 'RESET');
                    },
                    get missingRequired() {
                        return this.requiredMapped.filter((f) => !this.mapping[f]);
                    },
                    go(n) {
                        if (n < this.step && this.step < 4) {
                            this.message = '';
                            this.step = n;
                        }
                    },
                    norm(s) {
                        return String(s ?? '').toLowerCase().replace(/[^a-z0-9]/g, '');
                    },
                    autoMap() {
                        let n = 0;
                        this.fillableFields.forEach((f) => {
                            const keys = [this.norm(f), this.norm(f.replace(/_id$/, '')), this.norm(this.fieldLabels[f]), ...this.aliases.map((a) => this.norm(a))];
                            const match = this.headers.find((h) => keys.includes(this.norm(h)));
                            if (match) {
                                this.mapping[f] = match;
                                n++;
                            }
                        });
                        this.autoMapped = n;
                    },
                    normalizeErrorList(raw) {
                        if (Array.isArray(raw)) {
                            return raw.filter((x) => x != null && String(x).trim() !== '');
                        }
                        if (raw && typeof raw === 'object') {
                            return Object.values(raw)
                                .flat(Infinity)
                                .filter((x) => x != null && String(x).trim() !== '')
                                .map((x) => String(x));
                        }
                        return [];
                    },
                    animateCount(target) {
                        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        if (reduce || !target) {
                            this.displayCount = target ?? 0;
                            return;
                        }
                        this.displayCount = 0;
                        const start = performance.now();
                        const dur = 900;
                        const tick = (t) => {
                            const p = Math.min(1, (t - start) / dur);
                            this.displayCount = Math.round(target * (1 - Math.pow(1 - p, 3)));
                            if (p < 1) requestAnimationFrame(tick);
                        };
                        requestAnimationFrame(tick);
                    },
                    async uploadFile(file) {
                        if (!file || this.loading) return;
                        this.loading = true;
                        this.message = '';
                        this.fileName = file.name;
                        this.uploadPct = 0;
                        try {
                            const fd = new FormData();
                            fd.append('file', file);
                            const { data } = await window.axios.post(@js($urls['upload']), fd, {
                                headers: { 'Content-Type': 'multipart/form-data' },
                                onUploadProgress: (e) => {
                                    this.uploadPct = e.total ? Math.round((e.loaded / e.total) * 100) : 0;
                                },
                            });
                            this.headers = data.headers;
                            this.rowCount = data.row_count;
                            if (data.impact) this.impact = data.impact;
                            this.mapping = @js($mappingDefaults);
                            this.autoMap();
                            this.step = 2;
                        } catch (e) {
                            this.message =
                                e.response?.data?.message ||
                                (e.response?.data?.errors?.file?.[0] ?? @js(__('Ralat muat naik.')));
                        } finally {
                            this.loading = false;
                        }
                    },
                    async runPreview() {
                        this.loading = true;
                        this.message = '';
                        this.errors = [];
                        try {
                            const { data } = await window.axios.post(@js($urls['preview']), {
                                mapping: this.mapping,
                                empty_policy: this.emptyPolicy,
                            });
                            this.preview = Array.isArray(data.preview) ? data.preview : [];
                            this.errors = this.normalizeErrorList(data.errors);
                            this.step = 3;
                        } catch (e) {
                            if (e.response?.status === 422) {
                                const d = e.response.data;
                                this.message = d.message || JSON.stringify(d.errors || {});
                            } else {
                                this.message = @js(__('Ralat pratonton.'));
                            }
                        } finally {
                            this.loading = false;
                        }
                    },
                    async runImport() {
                        this.loading = true;
                        this.message = '';
                        try {
                            const { data } = await window.axios.post(@js($urls['import']), {
                                mapping: this.mapping,
                                empty_policy: this.emptyPolicy,
                                confirm: this.confirmText.trim().toUpperCase(),
                            });
                            this.result = data;
                            this.importedCount = data.imported;
                            this.step = 4;
                            this.animateCount(data.imported);
                        } catch (e) {
                            if (e.response?.status === 422) {
                                this.errors = this.normalizeErrorList(e.response.data.errors);
                                this.preview = Array.isArray(e.response.data.preview)
                                    ? e.response.data.preview
                                    : this.preview;
                                this.message = @js(__('Import ditolak — semak ralat di bawah.'));
                            } else {
                                this.message = @js(__('Ralat import.'));
                            }
                        } finally {
                            this.loading = false;
                        }
                    },
                    resetAll() {
                        this.step = 1;
                        this.headers = [];
                        this.rowCount = 0;
                        this.mapping = @js($mappingDefaults);
                        this.emptyPolicy = @js($policyDefaults);
                        this.preview = [];
                        this.errors = [];
                        this.message = '';
                        this.importedCount = null;
                        this.displayCount = 0;
                        this.fileName = '';
                        this.autoMapped = 0;
                        this.confirmText = '';
                        this.result = {};
                    },
                }));
            });
        </script>
    @endpush
</x-dashboard-layout>
