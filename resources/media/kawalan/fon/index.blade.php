@php
    $inputClass = 'h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40';
@endphp

<x-dashboard-layout :title="__('Pustaka Fon')" role="media">
    <x-kawalan-shell>
        {{-- Rules are built from font ids and route URLs only (see PresentationFontService::fontFaceCss). --}}
        <style id="apc-font-faces">{!! $fontFaceCss !!}</style>

        <div
            x-data="fontLibrary(@js([
                'fonts' => $fontsPayload,
                'max' => $maxFonts,
                'maxKb' => $maxFileKb,
                'routes' => [
                    'list' => route('media.kawalan.fon.list'),
                    'store' => route('media.kawalan.fon.store'),
                    'item' => route('media.kawalan.fon.update', ['presentationFont' => '__ID__']),
                    'presentation' => route('media.kawalan.presentation.index').'#fonstyle',
                ],
            ]))"
        >
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ __('Pustaka Fon') }}</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ __('Muat naik fon untuk Layar Utama, kemudian pilih fon bagi setiap bahagian di') }}
                        <a :href="routes.presentation" class="font-medium text-primary underline-offset-2 hover:underline">{{ __('Kawalan Presentasi → Gaya Fon') }}</a>.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium tabular-nums text-muted-foreground" x-text="fonts.length + ' / ' + max + ' {{ __('fon') }}'"></span>
                    <button type="button" class="btn btn-primary flex items-center gap-2" x-on:click="openCreate()" :disabled="fonts.length >= max">
                        <i class="ri-upload-2-line" aria-hidden="true"></i>
                        {{ __('Muat naik fon') }}
                    </button>
                </div>
            </div>

            {{-- Specimen controls --}}
            <section x-show="fonts.length" class="mb-5 flex flex-col gap-3 rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:flex-row sm:items-center">
                <label class="flex min-w-0 flex-1 items-center gap-2">
                    <span class="shrink-0 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Teks contoh') }}</span>
                    <input type="text" x-model="sample" maxlength="60" class="{{ $inputClass }}">
                </label>
                <label class="flex items-center gap-3 sm:w-72">
                    <span class="shrink-0 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Saiz') }}</span>
                    <input type="range" min="20" max="96" step="2" x-model.number="size" class="matrix-range w-full">
                    <span class="w-12 text-right text-xs tabular-nums text-muted-foreground" x-text="size + 'px'"></span>
                </label>
            </section>

            {{-- Empty state --}}
            <section x-show="!fonts.length" x-cloak class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-border bg-card px-6 py-16 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <i class="ri-font-family text-2xl" aria-hidden="true"></i>
                </span>
                <h2 class="text-base font-semibold text-foreground">{{ __('Belum ada fon dimuat naik') }}</h2>
                <p class="max-w-md text-sm text-muted-foreground">
                    {{ __('Muat naik fail TTF, OTF, WOFF atau WOFF2. Selepas itu, setiap bahagian slaid (Nama, Jawatan, PTJ, Tarikh Bersara, Bersara, Tempoh Khidmat) boleh guna fon berbeza.') }}
                </p>
                <button type="button" class="btn btn-primary mt-2 flex items-center gap-2" x-on:click="openCreate()">
                    <i class="ri-upload-2-line" aria-hidden="true"></i>{{ __('Muat naik fon pertama') }}
                </button>
            </section>

            {{-- Font cards --}}
            <div x-show="fonts.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="font in fonts" :key="font.id">
                    <article class="flex flex-col overflow-hidden rounded-2xl border border-border/70 bg-card shadow-sm">
                        <div class="flex min-h-[9rem] flex-col justify-center gap-1 overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-900 px-5 py-6 text-white">
                            <p class="break-words uppercase leading-tight" :style="specimenStyle(font, size)" x-text="sample || '{{ __('AHMAD BIN ALI') }}'"></p>
                            <p class="truncate text-white/70" :style="specimenStyle(font, Math.max(14, Math.round(size * 0.4)))">Aa Bb Cc Dd 0123456789</p>
                        </div>
                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-foreground" x-text="font.name" :title="font.name"></h3>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        <span class="rounded bg-muted px-1.5 py-0.5 font-semibold" x-text="font.format_label"></span>
                                        <span class="tabular-nums" x-text="formatSize(font.size)"></span>
                                        · <span x-text="font.updated_human"></span>
                                    </p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <button type="button" class="btn btn-ghost btn-icon btn-sm" x-on:click="openEdit(font)" :title="'{{ __('Ubah') }} ' + font.name">
                                        <i class="ri-edit-line" aria-hidden="true"></i><span class="sr-only">{{ __('Ubah') }}</span>
                                    </button>
                                    <button type="button" class="btn btn-ghost btn-icon btn-sm text-destructive" x-on:click="remove(font)" :title="'{{ __('Padam') }} ' + font.name">
                                        <i class="ri-delete-bin-line" aria-hidden="true"></i><span class="sr-only">{{ __('Padam') }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-auto flex flex-wrap gap-1.5 text-[0.7rem]">
                                <template x-if="!font.usage.parts.length && !font.usage.profiles.length">
                                    <span class="rounded-full bg-muted px-2 py-0.5 text-muted-foreground">{{ __('Belum digunakan') }}</span>
                                </template>
                                <template x-for="part in font.usage.parts" :key="'p-' + part">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                        <i class="ri-slideshow-3-line" aria-hidden="true"></i><span x-text="part"></span>
                                    </span>
                                </template>
                                <template x-for="profile in font.usage.profiles" :key="'f-' + profile">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 font-medium text-primary">
                                        <i class="ri-bookmark-line" aria-hidden="true"></i><span x-text="profile"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

            {{-- Upload / edit modal --}}
            <div class="modal-backdrop"></div>
            <x-crud-modal modal-id="font-modal" :title="__('Fon')" size="modal-lg">
                <form x-on:submit.prevent="submit()" class="space-y-4" novalidate>
                    <p class="-mt-2 text-sm font-semibold text-foreground" x-text="form.mode === 'edit' ? '{{ __('Ubah fon') }}' : '{{ __('Muat naik fon baharu') }}'"></p>

                    <div
                        class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 text-center transition"
                        :class="form.dragOver ? 'border-primary bg-primary/5' : (form.errors.font_file ? 'border-destructive/60 bg-destructive/5' : 'border-border hover:border-primary/50 hover:bg-muted/40')"
                        x-on:click="$refs.fileInput.click()"
                        x-on:dragover.prevent="form.dragOver = true"
                        x-on:dragleave.prevent="form.dragOver = false"
                        x-on:drop.prevent="form.dragOver = false; pickFile($event.dataTransfer.files[0])"
                        role="button"
                        tabindex="0"
                        x-on:keydown.enter.prevent="$refs.fileInput.click()"
                        x-on:keydown.space.prevent="$refs.fileInput.click()"
                    >
                        <input type="file" x-ref="fileInput" class="sr-only" accept=".ttf,.otf,.woff,.woff2" x-on:change="pickFile($event.target.files[0]); $event.target.value = ''">
                        <i class="ri-file-upload-line text-3xl text-muted-foreground" aria-hidden="true"></i>
                        <template x-if="!form.file">
                            <div>
                                <p class="text-sm font-medium text-foreground" x-text="form.mode === 'edit' ? '{{ __('Pilih fail baharu untuk ganti (pilihan)') }}' : '{{ __('Seret fail fon ke sini atau klik untuk pilih') }}'"></p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ __('TTF, OTF, WOFF, WOFF2 · Maks. 10 MB') }}</p>
                            </div>
                        </template>
                        <template x-if="form.file">
                            <p class="text-sm font-medium text-foreground">
                                <span x-text="form.file.name"></span>
                                <span class="text-xs text-muted-foreground" x-text="'(' + formatSize(form.file.size) + ')'"></span>
                            </p>
                        </template>
                    </div>
                    <p x-show="form.errors.font_file" x-text="form.errors.font_file" class="-mt-2 text-xs font-medium text-destructive" role="alert"></p>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-foreground">{{ __('Nama fon') }}</span>
                        <input type="text" x-model="form.name" x-on:input="form.nameTouched = true" maxlength="60" class="{{ $inputClass }}" :class="form.errors.name ? 'border-destructive' : ''" placeholder="{{ __('cth. Montserrat Bold') }}">
                        <p x-show="form.errors.name" x-text="form.errors.name" class="mt-1 text-xs font-medium text-destructive" role="alert"></p>
                    </label>

                    {{-- Live preview of the chosen file (before upload) or the current font --}}
                    <div class="overflow-hidden rounded-xl bg-gradient-to-br from-slate-900 via-indigo-950 to-violet-900 px-5 py-5 text-white">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-white/60">{{ __('Pratonton') }}</p>
                        <template x-if="form.previewFamily">
                            <div class="mt-1 space-y-1">
                                <p class="break-words text-4xl uppercase leading-tight" :style="'font-family: ' + form.previewFamily + ', sans-serif'" x-text="sample || '{{ __('AHMAD BIN ALI') }}'"></p>
                                <p class="truncate text-base text-white/70" :style="'font-family: ' + form.previewFamily + ', sans-serif'">Aa Bb Cc Dd 0123456789</p>
                            </div>
                        </template>
                        <template x-if="!form.previewFamily">
                            <p class="mt-2 text-sm text-white/60" x-text="form.previewLoading ? '{{ __('Memuatkan fon…') }}' : '{{ __('Pilih fail untuk melihat pratonton.') }}'"></p>
                        </template>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ghost" data-modal-close x-on:click="closeForm()">{{ __('Batal') }}</button>
                        <button type="submit" class="btn btn-primary inline-flex items-center gap-2" :disabled="form.busy || (form.mode === 'create' && !form.file)">
                            <i class="ri-loader-4-line animate-spin" x-show="form.busy" x-cloak aria-hidden="true"></i>
                            <span x-text="form.mode === 'edit' ? '{{ __('Simpan') }}' : '{{ __('Muat naik') }}'"></span>
                        </button>
                    </div>
                </form>
            </x-crud-modal>
        </div>
    </x-kawalan-shell>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', function() {
                window.Alpine.data('fontLibrary', function(config) {
                    const exts = ['ttf', 'otf', 'woff', 'woff2'];
                    let previewSeq = 0;

                    const blankForm = () => ({
                        mode: 'create',
                        id: null,
                        name: '',
                        nameTouched: false,
                        file: null,
                        previewFamily: null,
                        previewLoading: false,
                        dragOver: false,
                        busy: false,
                        errors: {},
                    });

                    return {
                        fonts: config.fonts,
                        max: config.max,
                        routes: config.routes,
                        sample: @json(__('AHMAD BIN ALI')),
                        size: 40,
                        form: blankForm(),

                        specimenStyle(font, px) {
                            return `font-family: "${font.family}", sans-serif; font-size: ${px}px;`;
                        },

                        formatSize(bytes) {
                            if (!bytes) {
                                return '';
                            }
                            return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.round(bytes / 1024) + ' KB';
                        },

                        itemUrl(id) {
                            return this.routes.item.replace('__ID__', id);
                        },

                        openCreate() {
                            this.form = blankForm();
                            window.openModal('font-modal');
                        },

                        openEdit(font) {
                            this.form = { ...blankForm(), mode: 'edit', id: font.id, name: font.name, nameTouched: true, previewFamily: `"${font.family}"` };
                            window.openModal('font-modal');
                        },

                        closeForm() {
                            window.closeModal('font-modal');
                        },

                        async pickFile(file) {
                            if (!file) {
                                return;
                            }
                            this.form.errors = {};
                            const ext = (file.name.split('.').pop() || '').toLowerCase();
                            if (!exts.includes(ext)) {
                                this.form.errors = { font_file: @json(__('Hanya fail TTF, OTF, WOFF atau WOFF2 dibenarkan.')) };
                                return;
                            }
                            if (file.size > config.maxKb * 1024) {
                                this.form.errors = { font_file: @json(__('Saiz fail fon maksimum 10 MB.')) };
                                return;
                            }

                            this.form.file = file;
                            if (!this.form.nameTouched) {
                                this.form.name = file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ').trim().slice(0, 60);
                            }

                            // Preview the local file before it is uploaded.
                            const family = 'apc-local-preview-' + (++previewSeq);
                            this.form.previewFamily = null;
                            this.form.previewLoading = true;
                            try {
                                const face = new FontFace(family, await file.arrayBuffer());
                                await face.load();
                                document.fonts.add(face);
                                this.form.previewFamily = `"${family}"`;
                            } catch (error) {
                                this.form.errors = { font_file: @json(__('Fail ini tidak dapat dibaca sebagai fon. Cuba fail lain.')) };
                                this.form.file = null;
                            } finally {
                                this.form.previewLoading = false;
                            }
                        },

                        async refresh() {
                            const { data } = await window.axios.get(this.routes.list);
                            document.getElementById('apc-font-faces').textContent = data.font_face_css;
                            this.fonts = data.fonts;
                        },

                        async submit() {
                            const body = new FormData();
                            body.append('name', this.form.name.trim());
                            if (this.form.file) {
                                body.append('font_file', this.form.file);
                            }
                            if (this.form.mode === 'edit') {
                                body.append('_method', 'PUT');
                            }

                            this.form.busy = true;
                            this.form.errors = {};
                            try {
                                const url = this.form.mode === 'edit' ? this.itemUrl(this.form.id) : this.routes.store;
                                const { data } = await window.axios.post(url, body, { headers: { Accept: 'application/json' } });
                                this.closeForm();
                                await this.refresh();
                                this.toast('success', data.message);
                            } catch (error) {
                                const response = error.response;
                                if (response && response.status === 422 && response.data.errors) {
                                    this.form.errors = Object.fromEntries(
                                        Object.entries(response.data.errors).map(([key, messages]) => [key, messages[0]])
                                    );
                                } else {
                                    this.toast('error', @json(__('Gagal menyimpan fon. Sila cuba lagi.')));
                                }
                            } finally {
                                this.form.busy = false;
                            }
                        },

                        remove(font) {
                            const used = [...font.usage.parts, ...font.usage.profiles.map((name) => @json(__('profil')) + ' "' + name + '"')];
                            const text = used.length
                                ? @json(__('Fon ini digunakan oleh:')) + ' ' + used.join(', ') + '. ' + @json(__('Bahagian tersebut akan kembali ke fon lalai.'))
                                : @json(__('Fon ini belum digunakan.'));

                            window.kawalanConfirmDelete({
                                title: @json(__('Padam fon')) + ' "' + font.name + '"?',
                                text: text,
                                confirmButtonText: @json(__('Padam')),
                                cancelButtonText: @json(__('Batal')),
                            }).then(async (ok) => {
                                if (!ok) {
                                    return;
                                }
                                try {
                                    const { data } = await window.axios.delete(this.itemUrl(font.id), { headers: { Accept: 'application/json' } });
                                    await this.refresh();
                                    this.toast('success', data.message);
                                } catch (error) {
                                    this.toast('error', @json(__('Gagal memadam fon.')));
                                }
                            });
                        },

                        toast(icon, title) {
                            window.Swal?.fire({
                                toast: true,
                                position: 'top-end',
                                icon: icon,
                                title: title,
                                showConfirmButton: false,
                                timer: 2200,
                                background: 'var(--popover)',
                                color: 'var(--popover-foreground)',
                            });
                        },
                    };
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
