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
    ];
@endphp

<x-dashboard-layout :title="__('Import pangkalan — Pegawai')" role="admin">
    <x-crud-header
        :title="__('Import fail — Pegawai')"
        :description="__('Muat naik CSV atau Excel (.xlsx), pilih pemetaan lajur, pratonton, kemudian import.')"
        :show-create="false"
    />

    <div
        class="space-y-8"
        x-cloak
        x-data="{
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
            requiredMapped: @js($requiredMapped),
            optionalPolicyFields: @js($optionalPolicyFields),
            fillableFields: @js($fillableFields),
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
            async uploadFile(file) {
                if (!file) return;
                this.loading = true;
                this.message = '';
                try {
                    const fd = new FormData();
                    fd.append('file', file);
                    const { data } = await window.axios.post(@js(route('admin.kawalan.database.upload')), fd, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                    });
                    this.headers = data.headers;
                    this.rowCount = data.row_count;
                    this.step = 2;
                } catch (e) {
                    this.message =
                        e.response?.data?.message ||
                        (e.response?.data?.errors?.file?.[0] ?? '{{ __('Ralat muat naik.') }}');
                } finally {
                    this.loading = false;
                }
            },
            async runPreview() {
                this.loading = true;
                this.message = '';
                this.errors = [];
                try {
                    const { data } = await window.axios.post(@js(route('admin.kawalan.database.preview')), {
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
                        this.message = '{{ __('Ralat pratonton.') }}';
                    }
                } finally {
                    this.loading = false;
                }
            },
            async runImport() {
                this.loading = true;
                this.message = '';
                try {
                    const { data } = await window.axios.post(@js(route('admin.kawalan.database.import')), {
                        mapping: this.mapping,
                        empty_policy: this.emptyPolicy,
                    });
                    this.importedCount = data.imported;
                    this.step = 4;
                } catch (e) {
                    if (e.response?.status === 422) {
                        this.errors = this.normalizeErrorList(e.response.data.errors);
                        this.preview = Array.isArray(e.response.data.preview)
                            ? e.response.data.preview
                            : this.preview;
                        this.message = '{{ __('Import ditolak — semak ralat di bawah.') }}';
                    } else {
                        this.message = '{{ __('Ralat import.') }}';
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
            },
        }"
    >
        {{-- Step 1 --}}
        <div x-show="step === 1" class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">{{ __('1. Muat naik fail') }}</h2>
            <p class="mb-4 text-sm text-muted-foreground">
                {{ __('Seret dan lepas fail .csv / .xlsx di sini, atau klik untuk pilih.') }}
            </p>
            <label
                class="flex min-h-[10rem] cursor-pointer flex-col items-center justify-center rounded-md border-2 border-dashed border-muted-foreground/30 bg-muted/30 px-4 py-8 text-center transition hover:border-primary/50"
                @dragover.prevent
                @drop.prevent="uploadFile($event.dataTransfer.files[0])"
            >
                <input
                    type="file"
                    accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    class="hidden"
                    @change="uploadFile($event.target.files[0])"
                />
                <span class="text-sm font-medium text-foreground">{{ __('Pilih fail') }}</span>
                <span class="mt-1 text-xs text-muted-foreground">.csv, .txt, .xlsx — maks 10MB</span>
            </label>
            <p x-show="loading" class="mt-4 text-sm text-muted-foreground">{{ __('Memproses…') }}</p>
            <p x-show="message" class="mt-4 text-sm text-destructive" x-text="message"></p>
        </div>

        {{-- Step 2 --}}
        <div x-show="step === 2" class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">{{ __('2. Pemetaan lajur') }}</h2>
                <span class="text-sm text-muted-foreground">
                    <span x-text="rowCount"></span> {{ __('baris data') }}
                </span>
            </div>
            <p class="mb-4 text-sm text-muted-foreground">
                {{ __('Medan bertanda * mesti dipetakan. Untuk medan pilihan: jika lajur kosong atau tiada pemetaan, pilih sama ada storan 0 atau NULL (mengikut jenis medan).') }}
            </p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border text-left">
                            <th class="py-2 pr-4 font-medium">{{ __('Medan Pegawai') }}</th>
                            <th class="py-2 pr-4 font-medium">{{ __('Lajur fail') }}</th>
                            <th class="py-2 font-medium">{{ __('Jika kosong / tiada lajur') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fillableFields as $field)
                            <tr class="border-b border-border/60">
                                <td class="py-3 pr-4 align-top">
                                    <span class="font-medium">{{ $fieldLabels[$field] ?? $field }}</span>
                                    <code class="ml-1 text-xs text-muted-foreground">{{ $field }}</code>
                                    @if (in_array($field, $requiredMapped, true))
                                        <span class="text-destructive">*</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 align-top">
                                    <select
                                        class="{{ $inputClass }}"
                                        x-model="mapping['{{ $field }}']"
                                    >
                                        <option value="">{{ __('— Tiada —') }}</option>
                                        <template x-for="h in headers" :key="h">
                                            <option :value="h" x-text="h"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="py-3 align-top">
                                    @if (in_array($field, $optionalPolicyFields, true))
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    class="rounded-full border-input"
                                                    :name="'policy_{{ $field }}'"
                                                    value="{{ DatabaseImportService::POLICY_ZERO }}"
                                                    x-model="emptyPolicy['{{ $field }}']"
                                                />
                                                <span>0 / false</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                <input
                                                    type="radio"
                                                    class="rounded-full border-input"
                                                    :name="'policy_{{ $field }}'"
                                                    value="{{ DatabaseImportService::POLICY_NULL }}"
                                                    x-model="emptyPolicy['{{ $field }}']"
                                                />
                                                <span>NULL</span>
                                            </label>
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
                                    @else
                                        <span class="text-xs text-muted-foreground">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline" @click="step = 1">{{ __('Kembali') }}</button>
                <button type="button" class="btn" :disabled="loading" @click="runPreview()">
                    {{ __('Pratonton') }}
                </button>
            </div>
            <p x-show="loading" class="mt-4 text-sm text-muted-foreground">{{ __('Memproses…') }}</p>
            <p x-show="message" class="mt-4 text-sm text-destructive" x-text="message"></p>
        </div>

        {{-- Step 3 --}}
        <div x-show="step === 3" class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">{{ __('3. Pratonton & sahkan') }}</h2>
            <template x-if="Array.isArray(errors) && errors.length">
                <div
                    class="mb-4 max-h-48 overflow-y-auto rounded-md border border-destructive/40 bg-destructive/10 p-4 text-sm"
                >
                    <p class="mb-2 font-medium text-destructive">{{ __('Ralat (import tidak akan diteruskan sehingga betul):') }}</p>
                    <ul class="list-inside list-disc space-y-1">
                        <template x-for="(err, i) in errors" :key="i">
                            <li class="text-destructive" x-text="err"></li>
                        </template>
                    </ul>
                </div>
            </template>
            <template x-if="Array.isArray(errors) && errors.length === 0">
                <p class="mb-4 rounded-md border border-green-600/30 bg-green-600/10 p-3 text-sm text-green-800 dark:text-green-200">
                    {{ __('Tiada ralat pada pratonton. Anda boleh mengimport.') }}
                </p>
            </template>
            <p class="mb-2 text-sm text-muted-foreground">
                {{ __('Menunjukkan sehingga 100 baris pertama selepas pemetaan:') }}
            </p>
            <div class="overflow-x-auto rounded-md border border-border">
                <table class="w-full min-w-[48rem] text-left text-xs" id="import-preview-table">
                    <thead class="bg-muted/50">
                        <tr>
                            @foreach ($fillableFields as $col)
                                <th class="whitespace-nowrap px-2 py-2 font-medium">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, ri) in preview" :key="ri">
                            <tr class="border-t border-border/60">
                                @foreach ($fillableFields as $col)
                                    <td class="px-2 py-1" x-text="row['{{ $col }}'] ?? ''"></td>
                                @endforeach
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline" @click="step = 2">{{ __('Kembali ke pemetaan') }}</button>
                <button
                    type="button"
                    class="btn"
                    :disabled="loading || !Array.isArray(errors) || errors.length > 0"
                    @click="runImport()"
                >
                    {{ __('Import') }}
                </button>
            </div>
            <p x-show="loading" class="mt-4 text-sm text-muted-foreground">{{ __('Mengimport…') }}</p>
            <p x-show="message" class="mt-4 text-sm text-destructive" x-text="message"></p>
        </div>

        {{-- Step 4 --}}
        <div x-show="step === 4" class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-green-700 dark:text-green-300">{{ __('Import selesai') }}</h2>
            <p class="text-sm">
                {{ __('Berjaya mengimport') }}
                <strong x-text="importedCount"></strong>
                {{ __('rekod pegawai.') }}
            </p>
            <button type="button" class="btn mt-6" @click="resetAll()">{{ __('Import fail lain') }}</button>
        </div>
    </div>
</x-dashboard-layout>
