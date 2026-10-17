@php
    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60';
@endphp

<x-dashboard-layout :title="__('Kemas kini RSVP')" role="admin">
    <x-kawalan-shell>
        <x-crud-header :title="__('Kemas kini RSVP')" :description="__('Tukar status RSVP pegawai dan tetapkan no. kerusi. Pegawai yang sudah hadir dikunci.')" :show-create="false" />

        <section id="rsvp-stats-region" class="mb-6" aria-label="{{ __('Ringkasan RSVP') }}" aria-live="polite"
            aria-atomic="false">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stats-card :label="__('Jumlah pegawai')" :value="number_format($counts['total'])" stat-key="total"
                    icon="ri-team-line" />
                <x-stats-card :label="__('RSVP Hadir')" :value="number_format($counts['rsvp_hadir'])" stat-key="rsvp_hadir"
                    icon="ri-calendar-check-line" />
                <x-stats-card :label="__('RSVP Tidak Hadir')" :value="number_format($counts['rsvp_tidak_hadir'])"
                    stat-key="rsvp_tidak_hadir" icon="ri-calendar-close-line" />
                <x-stats-card :label="__('No. kerusi terakhir (RSVP)')" :value="$lastSeat > 0 ? $lastSeat : '-'"
                    :hint="__('Cadangan kerusi baharu bermula selepas nombor ini.')" stat-key="last_seat"
                    icon="ri-hashtag" />
            </div>
        </section>

        <section class="mt-8 space-y-4" aria-labelledby="rsvp-table-heading">
            <div
                class="flex flex-col gap-4 border-b border-border/60 pb-5 sm:flex-row sm:items-end sm:justify-between sm:gap-6 lg:gap-8">
                <div class="min-w-0 flex-1">
                    <h2 id="rsvp-table-heading" class="text-lg font-semibold tracking-tight text-foreground">
                        {{ __('Senarai pegawai') }}
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                        {{ __('Cari mengikut nama atau no. KP. Pegawai RSVP Tidak Hadir dipaparkan dahulu.') }}
                    </p>
                </div>
                <div class="group/toolbar flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:min-w-[13rem] sm:items-end">
                    <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:text-right"
                        for="rsvp-filter">
                        {{ __('Tapis mengikut RSVP') }}
                    </label>
                    <div class="relative w-full sm:w-auto">
                        <span
                            class="pointer-events-none absolute left-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground transition-colors group-focus-within/toolbar:text-primary"
                            aria-hidden="true">
                            <i class="ri-filter-3-line text-lg leading-none"></i>
                        </span>
                        <span
                            class="pointer-events-none absolute right-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true">
                            <i class="ri-arrow-down-s-line text-lg leading-none"></i>
                        </span>
                        <select id="rsvp-filter"
                            class="h-11 w-full min-w-0 cursor-pointer appearance-none rounded-lg border border-input bg-background py-2 pl-10 pr-10 text-sm font-medium text-foreground shadow-sm ring-offset-background transition-all duration-200 hover:border-primary/30 hover:bg-muted/40 hover:shadow-md focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40 focus:ring-offset-2 dark:hover:bg-muted/20 sm:min-w-[14rem]">
                            <option value="">{{ __('Semua') }}</option>
                            <option value="1">{{ __('Hadir') }}</option>
                            <option value="0">{{ __('Tidak Hadir') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <x-data-table table-id="rsvp-table" :columns="['ID', __('Pegawai'), __('PTJ'), __('RSVP'), __('No. Kerusi'), __('Kehadiran'), __('Tindakan')]"
                :column-header-classes="[3 => 'text-center', 4 => 'text-center', 5 => 'text-center', 6 => 'text-center']"
                class="shadow-sm ring-1 ring-border/30" />
        </section>
    </x-kawalan-shell>

    <div class="modal-backdrop"></div>

    <x-crud-modal modal-id="rsvp-modal" :title="__('Kemas kini RSVP pegawai')">
        <form id="rsvp-form" class="space-y-5" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" id="rsvp-id" />

            <section aria-labelledby="rsvp-info-heading" class="rounded-lg border border-border bg-muted/30 p-4 dark:bg-muted/10">
                <h4 id="rsvp-info-heading" class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ __('Maklumat pegawai') }}
                </h4>
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-muted-foreground">{{ __('Nama') }}</dt>
                        <dd class="font-semibold text-foreground" data-info="nama">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('No. KP') }}</dt>
                        <dd class="tabular-nums" data-info="no_kp">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Sesi') }}</dt>
                        <dd data-info="sesi_name">-</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-muted-foreground">{{ __('PTJ') }}</dt>
                        <dd data-info="ptj_name">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Jawatan') }}</dt>
                        <dd data-info="jawatan_name">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">{{ __('Gred') }}</dt>
                        <dd data-info="gred_name">-</dd>
                    </div>
                </dl>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="rsvp-rsvp">{{ __('RSVP') }}</label>
                    <select id="rsvp-rsvp" name="rsvp" class="{{ $inputClass }}">
                        <option value="0">0 = {{ __('Tidak Hadir') }}</option>
                        <option value="1">1 = {{ __('Hadir') }}</option>
                    </select>
                    <p class="hidden text-sm text-destructive" data-error="rsvp"></p>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="rsvp-no_kerusi">{{ __('No. kerusi') }}</label>
                    <div class="flex gap-2">
                        <input id="rsvp-no_kerusi" type="number" name="no_kerusi" min="1" step="1" inputmode="numeric"
                            class="{{ $inputClass }} tabular-nums" />
                        <button type="button" id="rsvp-use-suggestion" class="btn btn-outline btn-sm shrink-0 self-center"
                            title="{{ __('Guna cadangan sistem') }}">
                            <i class="ri-magic-line"></i>
                            <span>{{ __('Guna cadangan') }}</span>
                        </button>
                    </div>
                    <p class="text-xs text-muted-foreground" id="rsvp-seat-hint">
                        {{ __('Kerusi terakhir RSVP') }}: <strong class="tabular-nums text-foreground" data-hint="last_seat">-</strong>
                        · {{ __('Cadangan sistem') }}: <strong class="tabular-nums text-foreground" data-hint="suggested_seat">-</strong>
                    </p>
                    <p class="hidden text-xs text-amber-700 dark:text-amber-300" id="rsvp-seat-cleared">
                        {{ __('No. kerusi akan dikosongkan kerana pegawai tidak hadir.') }}
                    </p>
                    <p class="hidden text-sm text-destructive" data-error="no_kerusi"></p>
                </div>
            </div>

            <section aria-labelledby="rsvp-summary-heading">
                <h4 id="rsvp-summary-heading" class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ __('Ringkasan perubahan') }}
                </h4>
                <div class="overflow-hidden rounded-lg border border-border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40 text-left text-xs text-muted-foreground dark:bg-muted/10">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ __('Medan') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('Sebelum') }}</th>
                                <th class="w-6 px-1 py-2" aria-hidden="true"></th>
                                <th class="px-3 py-2 font-medium">{{ __('Selepas') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-border" data-summary="rsvp">
                                <td class="px-3 py-2 font-medium">{{ __('RSVP') }}</td>
                                <td class="px-3 py-2" data-summary-before>-</td>
                                <td class="px-1 py-2 text-muted-foreground" aria-hidden="true"><i class="ri-arrow-right-line"></i></td>
                                <td class="px-3 py-2" data-summary-after>-</td>
                            </tr>
                            <tr class="border-t border-border" data-summary="no_kerusi">
                                <td class="px-3 py-2 font-medium">{{ __('No. kerusi') }}</td>
                                <td class="px-3 py-2 tabular-nums" data-summary-before>-</td>
                                <td class="px-1 py-2 text-muted-foreground" aria-hidden="true"><i class="ri-arrow-right-line"></i></td>
                                <td class="px-3 py-2 tabular-nums" data-summary-after>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 hidden text-xs text-muted-foreground" id="rsvp-no-change">{{ __('Tiada perubahan.') }}</p>
            </section>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn" id="rsvp-submit">{{ __('Simpan') }}</button>
            </div>
        </form>
    </x-crud-modal>

    @push('scripts')
        <script>
            $(function() {
                const showUrl = @json(route('admin.rsvp.show', ['pegawai' => '__ID__']));
                const updateUrl = @json(route('admin.rsvp.update', ['pegawai' => '__ID__']));
                const labels = {
                    hadir: @json(__('Hadir')),
                    tidakHadir: @json(__('Tidak Hadir')),
                };
                const changedClass = 'bg-amber-50 font-semibold text-amber-900 dark:bg-amber-950/30 dark:text-amber-100';

                const $form = $('#rsvp-form');
                const $rsvp = $('#rsvp-rsvp');
                const $seat = $('#rsvp-no_kerusi');
                const $submit = $('#rsvp-submit');

                // Values loaded from the server for the officer being edited.
                let current = null;
                let suggestedSeat = null;

                const table = $('#rsvp-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ordering: false,
                    ajax: {
                        url: @json(route('admin.rsvp.datatable')),
                        data: function(d) {
                            d.rsvp = $('#rsvp-filter').val() || '';
                        },
                    },
                    columnDefs: [
                        { targets: 0, className: 'tabular-nums text-muted-foreground align-top', width: '3.75rem' },
                        { targets: 1, className: 'align-top py-3 min-w-0', width: '30%' },
                        { targets: 2, className: 'text-muted-foreground align-top min-w-0', width: '28%' },
                        { targets: 3, className: 'text-center align-top min-w-0' },
                        { targets: 4, className: 'text-muted-foreground tabular-nums text-center align-top min-w-0' },
                        { targets: 5, className: 'text-center align-top min-w-0' },
                        { targets: 6, className: 'text-center align-top min-w-0' },
                    ],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'nama', name: 'nama' },
                        { data: 'ptj_name', name: 'ptj.nama_ptj' },
                        { data: 'rsvp_label', name: 'rsvp', searchable: false },
                        { data: 'no_kerusi', name: 'no_kerusi', defaultContent: '-' },
                        { data: 'kehadiran_label', name: 'is_attend', searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                $('#rsvp-filter').on('change', function() {
                    table.ajax.reload();
                });

                function toast(icon, title) {
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
                }

                function seatText(seat) {
                    return seat ? String(seat) : '-';
                }

                function clearErrors() {
                    $form.find('[data-error]').addClass('hidden').text('');
                }

                function showErrors(errors) {
                    Object.entries(errors).forEach(function([field, messages]) {
                        $form.find('[data-error="' + field + '"]').removeClass('hidden').text(messages[0]);
                    });
                }

                function formValues() {
                    const rsvp = $rsvp.val() === '1';
                    const seat = rsvp ? (parseInt($seat.val(), 10) || null) : null;

                    return { rsvp, seat };
                }

                function setSummaryRow(key, before, after, changed) {
                    const $row = $form.find('[data-summary="' + key + '"]');
                    $row.find('[data-summary-before]').text(before);
                    $row.find('[data-summary-after]').text(after).toggleClass(changedClass, changed);
                }

                function renderSummary() {
                    if (!current) {
                        return;
                    }
                    const next = formValues();
                    const rsvpChanged = next.rsvp !== current.rsvp;
                    const seatChanged = next.seat !== current.no_kerusi;

                    setSummaryRow('rsvp',
                        current.rsvp ? labels.hadir : labels.tidakHadir,
                        next.rsvp ? labels.hadir : labels.tidakHadir,
                        rsvpChanged);
                    setSummaryRow('no_kerusi', seatText(current.no_kerusi), seatText(next.seat), seatChanged);

                    $('#rsvp-no-change').toggleClass('hidden', rsvpChanged || seatChanged);
                    $submit.prop('disabled', !rsvpChanged && !seatChanged);
                }

                function syncSeatField() {
                    const hadir = $rsvp.val() === '1';
                    $seat.prop('disabled', !hadir).prop('required', hadir);
                    $('#rsvp-use-suggestion').prop('disabled', !hadir);
                    $('#rsvp-seat-cleared').toggleClass('hidden', hadir || !current?.no_kerusi);
                }

                $rsvp.on('change', function() {
                    if ($rsvp.val() === '1') {
                        // Back to an RSVP officer's own seat; officers new to RSVP get the next free seat.
                        $seat.val(current?.rsvp && current.no_kerusi ? current.no_kerusi : (suggestedSeat ?? ''));
                    } else {
                        $seat.val('');
                    }
                    clearErrors();
                    syncSeatField();
                    renderSummary();
                });

                $seat.on('input', function() {
                    $form.find('[data-error="no_kerusi"]').addClass('hidden').text('');
                    renderSummary();
                });

                $('#rsvp-use-suggestion').on('click', function() {
                    if (suggestedSeat) {
                        $seat.val(suggestedSeat).trigger('input');
                    }
                });

                function updateStats(counts, lastSeat) {
                    const $region = $('#rsvp-stats-region');
                    Object.entries(counts).forEach(function([key, value]) {
                        $region.find('[data-stat="' + key + '"]').text(Number(value).toLocaleString('en-US'));
                    });
                    $region.find('[data-stat="last_seat"]').text(lastSeat > 0 ? lastSeat : '-');
                }

                $('#rsvp-table').on('click', '.js-edit-rsvp', function() {
                    const $btn = $(this);
                    if ($btn.prop('disabled')) {
                        return;
                    }
                    $btn.prop('disabled', true);

                    $.ajax({
                            url: showUrl.replace('__ID__', String($btn.data('id'))),
                            method: 'GET',
                            headers: { Accept: 'application/json' },
                        })
                        .done(function(response) {
                            const pegawai = response.pegawai;
                            if (pegawai.is_attend) {
                                toast('warning', @json(__('Pegawai sudah hadir. Batalkan kehadiran dahulu.')));
                                table.ajax.reload(null, false);
                                return;
                            }

                            current = { rsvp: Boolean(pegawai.rsvp), no_kerusi: pegawai.no_kerusi || null };
                            suggestedSeat = response.suggested_seat;

                            $form.find('[data-info]').each(function() {
                                $(this).text(pegawai[$(this).data('info')] ?? '-');
                            });
                            $form.find('[data-hint="last_seat"]').text(response.last_seat > 0 ? response.last_seat : '-');
                            $form.find('[data-hint="suggested_seat"]').text(seatText(suggestedSeat));

                            $('#rsvp-id').val(pegawai.id);
                            $rsvp.val(current.rsvp ? '1' : '0');
                            $seat.val(current.no_kerusi ?? '');
                            clearErrors();
                            syncSeatField();
                            renderSummary();
                            openModal('rsvp-modal');
                        })
                        .fail(function() {
                            toast('error', @json(__('Ralat mendapatkan maklumat pegawai')));
                        })
                        .always(function() {
                            $btn.prop('disabled', false);
                        });
                });

                $form.on('submit', function(e) {
                    e.preventDefault();
                    clearErrors();

                    const next = formValues();
                    if (next.rsvp && !next.seat) {
                        showErrors({ no_kerusi: [@json(__('No. kerusi diperlukan untuk pegawai RSVP Hadir.'))] });
                        return;
                    }

                    $submit.prop('disabled', true);

                    $.ajax({
                            url: updateUrl.replace('__ID__', String($('#rsvp-id').val())),
                            method: 'POST',
                            data: {
                                _token: @json(csrf_token()),
                                _method: 'PUT',
                                rsvp: next.rsvp ? 1 : 0,
                                no_kerusi: next.seat ?? '',
                            },
                            headers: { Accept: 'application/json' },
                        })
                        .done(function(response) {
                            closeModal('rsvp-modal');
                            updateStats(response.counts, response.last_seat);
                            table.ajax.reload(null, false);
                            toast('success', response.message);
                        })
                        .fail(function(xhr) {
                            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                                showErrors(xhr.responseJSON.errors);
                            } else {
                                toast('error', xhr.responseJSON?.message || @json(__('Ralat')));
                            }
                        })
                        .always(function() {
                            renderSummary();
                        });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
