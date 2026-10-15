@php
    $card = 'flex flex-col rounded-2xl border border-border/70 bg-card p-4 shadow-sm sm:p-5';
    $items = collect($order)->map(fn (string $key) => [
        'key' => $key,
        'label' => $options[$key]['label'],
        'short' => $options[$key]['short'],
        'description' => $options[$key]['description'],
        'icon' => $options[$key]['icon'],
        'count' => $counts[$key],
    ])->values();
@endphp

<x-dashboard-layout :title="__('Susunan Panggilan')" role="admin">
    <x-kawalan-shell>
        <x-crud-header
            :title="__('Susunan Panggilan')"
            :description="__('Tetapkan kategori pegawai yang dipanggil dahulu. Terpakai untuk semua mod acara.')"
            :show-create="false"
        />

        <div
            x-data="callingPriority(@js($items), @js($defaultOrder))"
            class="grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]"
        >
            {{-- Priority list --}}
            <section class="{{ $card }}" aria-labelledby="priority-title">
                <header class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary" aria-hidden="true">
                            <i class="ri-list-ordered-2 text-lg"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 id="priority-title" class="text-sm font-semibold text-foreground">{{ __('Keutamaan panggilan') }}</h2>
                            <p class="text-xs text-muted-foreground">{{ __('Seret atau guna butang anak panah untuk menyusun semula.') }}</p>
                        </div>
                    </div>
                    <span
                        x-show="dirty"
                        x-cloak
                        class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                    >{{ __('Belum disimpan') }}</span>
                </header>

                <ol class="mt-4 space-y-2" aria-label="{{ __('Susunan kategori pegawai') }}">
                    <template x-for="(item, index) in items" :key="item.key">
                        <li
                            draggable="true"
                            x-on:dragstart="dragIndex = index; $event.dataTransfer.effectAllowed = 'move'"
                            x-on:dragend="dragIndex = null; overIndex = null"
                            x-on:dragover.prevent="overIndex = index"
                            x-on:drop.prevent="drop(index)"
                            class="group flex items-center gap-3 rounded-xl border bg-background px-3 py-3 shadow-sm transition"
                            :class="{
                                'border-primary ring-2 ring-primary/30': overIndex === index && dragIndex !== null && dragIndex !== index,
                                'opacity-50': dragIndex === index,
                                'border-border/70': overIndex !== index || dragIndex === null,
                            }"
                        >
                            <span class="hidden cursor-grab text-muted-foreground active:cursor-grabbing sm:block" aria-hidden="true">
                                <i class="ri-draggable text-lg"></i>
                            </span>
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold tabular-nums text-primary-foreground"
                                x-text="index + 1"
                            ></span>
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-muted text-foreground" aria-hidden="true">
                                <i :class="item.icon" class="text-lg"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-foreground" x-text="item.label"></p>
                                <p class="text-xs text-muted-foreground" x-text="item.description"></p>
                            </div>
                            <span
                                class="hidden shrink-0 rounded-full bg-muted/60 px-2.5 py-1 text-xs font-medium tabular-nums text-muted-foreground sm:inline-flex"
                                x-show="item.count !== null"
                                x-text="item.count + ' {{ __('hadir') }}'"
                                title="{{ __('Pegawai hadir dalam sesi on air') }}"
                            ></span>
                            <div class="flex shrink-0 flex-col gap-0.5">
                                <button
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-30"
                                    :disabled="index === 0 || saving"
                                    x-on:click="move(index, -1)"
                                    :aria-label="'{{ __('Naikkan') }} ' + item.short"
                                >
                                    <i class="ri-arrow-up-s-line text-lg" aria-hidden="true"></i>
                                </button>
                                <button
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-30"
                                    :disabled="index === items.length - 1 || saving"
                                    x-on:click="move(index, 1)"
                                    :aria-label="'{{ __('Turunkan') }} ' + item.short"
                                >
                                    <i class="ri-arrow-down-s-line text-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </li>
                    </template>
                </ol>

                <p class="sr-only" aria-live="polite" x-text="announcement"></p>

                <footer class="mt-5 flex flex-col-reverse gap-2 border-t border-border/60 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-border bg-background px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted/60 disabled:opacity-50"
                        :disabled="isDefault || saving"
                        x-on:click="resetDefault()"
                    >
                        <i class="ri-restart-line" aria-hidden="true"></i>
                        {{ __('Set semula lalai') }}
                    </button>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-muted-foreground transition hover:text-foreground disabled:opacity-50"
                            x-show="dirty"
                            x-cloak
                            :disabled="saving"
                            x-on:click="undo()"
                        >
                            {{ __('Batal perubahan') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary inline-flex items-center gap-2"
                            :disabled="!dirty || saving"
                            x-on:click="save()"
                        >
                            <i class="ri-loader-4-line animate-spin" x-show="saving" x-cloak aria-hidden="true"></i>
                            <i class="ri-save-3-line" x-show="!saving" aria-hidden="true"></i>
                            {{ __('Simpan susunan') }}
                        </button>
                    </div>
                </footer>
            </section>

            {{-- Preview + where it applies --}}
            <div class="flex flex-col gap-4">
                <section class="{{ $card }}" aria-labelledby="preview-title">
                    <h2 id="preview-title" class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Pratonton giliran') }}</h2>
                    <div class="mt-3 flex flex-wrap items-center gap-1.5" aria-live="polite">
                        <template x-for="(item, index) in items" :key="'preview-' + item.key">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-muted/60 px-2.5 py-1.5 text-sm font-semibold text-foreground">
                                    <span class="tabular-nums text-muted-foreground" x-text="(index + 1) + '.'"></span>
                                    <i :class="item.icon" aria-hidden="true"></i>
                                    <span x-text="item.short"></span>
                                </span>
                                <i class="ri-arrow-right-line text-muted-foreground" x-show="index < items.length - 1" aria-hidden="true"></i>
                            </span>
                        </template>
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-muted-foreground">
                        {{ __('Dalam setiap kategori: pegawai tepat masa ikut no. kerusi, kemudian pegawai lewat ikut no. panggilan lewat.') }}
                        <span x-text="okuNote"></span>
                    </p>
                    @if ($activeSesi)
                        <p class="mt-2 text-xs text-muted-foreground">
                            <i class="ri-broadcast-line text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                            {{ __('Kiraan hadir untuk sesi on air: :sesi', ['sesi' => $activeSesi->sesi]) }}
                        </p>
                    @endif
                </section>

                <section class="{{ $card }}" aria-labelledby="applies-title">
                    <h2 id="applies-title" class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Digunakan di') }}</h2>
                    <ul class="mt-3 space-y-2 text-sm text-foreground">
                        <li class="flex items-center gap-2"><i class="ri-slideshow-3-line text-primary" aria-hidden="true"></i>{{ __('Layar Utama (slaid pengumuman)') }}</li>
                        <li class="flex items-center gap-2"><i class="ri-bug-line text-primary" aria-hidden="true"></i>{{ __('Debug paparan') }}</li>
                        <li class="flex items-center gap-2"><i class="ri-file-pdf-line text-primary" aria-hidden="true"></i>{{ __('Laporan & Senarai MC') }}</li>
                    </ul>
                    <div class="mt-4 flex items-start gap-2 rounded-xl border border-amber-200/80 bg-amber-50/90 px-3 py-2.5 text-xs text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100">
                        <i class="ri-alarm-warning-line mt-0.5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                        <span>{{ __('Menukar susunan semasa majlis berjalan akan mengalihkan pegawai di Layar Utama; kedudukan slaid semasa kekal pada nombor yang sama.') }}</span>
                    </div>
                </section>
            </div>
        </div>
    </x-kawalan-shell>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', function() {
                window.Alpine.data('callingPriority', function(initialItems, defaultOrder) {
                    return {
                        items: initialItems.slice(),
                        savedOrder: initialItems.map(function(item) { return item.key; }),
                        defaultOrder: defaultOrder,
                        dragIndex: null,
                        overIndex: null,
                        saving: false,
                        announcement: '',

                        get currentOrder() {
                            return this.items.map(function(item) { return item.key; });
                        },
                        get dirty() {
                            return this.currentOrder.join() !== this.savedOrder.join();
                        },
                        get isDefault() {
                            return this.currentOrder.join() === this.defaultOrder.join();
                        },
                        get okuNote() {
                            const order = this.currentOrder;
                            return order.indexOf('rsvp') < order.indexOf('walkin')
                                ? @json(__('Dalam kategori OKU: RSVP dahulu, kemudian walk-in.'))
                                : @json(__('Dalam kategori OKU: walk-in dahulu, kemudian RSVP.'));
                        },

                        move(index, step) {
                            const target = index + step;
                            if (target < 0 || target >= this.items.length) {
                                return;
                            }
                            const moved = this.items.splice(index, 1)[0];
                            this.items.splice(target, 0, moved);
                            this.announcement = moved.short + ' ' + @json(__('kini di kedudukan')) + ' ' + (target + 1);
                        },
                        drop(index) {
                            if (this.dragIndex !== null && this.dragIndex !== index) {
                                this.move(this.dragIndex, index - this.dragIndex);
                            }
                            this.dragIndex = null;
                            this.overIndex = null;
                        },
                        sortBy(order) {
                            this.items.sort(function(a, b) {
                                return order.indexOf(a.key) - order.indexOf(b.key);
                            });
                        },
                        resetDefault() {
                            this.sortBy(this.defaultOrder);
                        },
                        undo() {
                            this.sortBy(this.savedOrder);
                        },
                        save() {
                            const self = this;
                            const order = this.currentOrder;
                            const labels = this.items.map(function(item) { return item.short; }).join(' → ');

                            window.kawalanConfirmDelete({
                                title: @json(__('Simpan susunan panggilan?')),
                                text: labels + '. ' + @json(__('Perubahan terpakai serta-merta untuk semua pengguna dan semua mod acara.')),
                                confirmButtonText: @json(__('Simpan')),
                                cancelButtonText: @json(__('Batal')),
                            }).then(function(ok) {
                                if (!ok) {
                                    return;
                                }
                                self.saving = true;
                                $.ajax({
                                        url: @json(route('admin.kawalan.susunan-panggilan.update')),
                                        method: 'PUT',
                                        data: { _token: @json(csrf_token()), order: order },
                                        headers: { Accept: 'application/json' },
                                    })
                                    .done(function(response) {
                                        self.savedOrder = response.order;
                                        self.sortBy(response.order);
                                        window.Swal?.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: 'success',
                                            title: @json(__('Susunan panggilan dikemas kini')),
                                            showConfirmButton: false,
                                            timer: 2200,
                                            background: 'var(--popover)',
                                            color: 'var(--popover-foreground)',
                                        });
                                    })
                                    .fail(function(xhr) {
                                        const errors = xhr.responseJSON?.errors;
                                        window.Swal?.fire({
                                            icon: 'error',
                                            title: @json(__('Ralat menyimpan susunan')),
                                            html: errors ? Object.values(errors).flat().join('<br>') : @json(__('Sila cuba lagi.')),
                                            confirmButtonText: @json(__('OK')),
                                            background: 'var(--popover)',
                                            color: 'var(--popover-foreground)',
                                            buttonsStyling: false,
                                            customClass: {
                                                popup: 'kawalan-swal2-popup',
                                                htmlContainer: 'kawalan-swal2-text',
                                                actions: 'kawalan-swal2-actions',
                                                confirmButton: 'kawalan-swal2-confirm',
                                            },
                                        });
                                    })
                                    .always(function() {
                                        self.saving = false;
                                    });
                            });
                        },
                    };
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
