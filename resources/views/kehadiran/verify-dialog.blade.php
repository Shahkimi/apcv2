<script>
    window.kehadiranVerifyDialog = function ({ pegawai, isAttend, isJasamu, showTableNumber, sesiDisplayName, title }) {
        function esc(value) {
            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        const isAttendBool = Boolean(isAttend);
        const headerIcon = isAttendBool ? 'ri-close-circle-line' : 'ri-checkbox-circle-line';
        const chipClass = isAttendBool
            ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
            : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
        const pillClass = isAttendBool
            ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300'
            : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
        const actionLabel = isAttendBool ? '{{ __('Batalkan hadir') }}' : '{{ __('Sahkan hadir') }}';

        const initials = String(pegawai.nama ?? '')
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((w) => w[0])
            .join('')
            .toUpperCase();

        const isRsvpYes = Number(pegawai.rsvp) === 1;
        const hasLateNumber = pegawai.no_panggilan_lewat !== '-' && Number(pegawai.no_panggilan_lewat) > 0;

        const tints = {
            emerald: {
                border: 'border-emerald-200/70 dark:border-emerald-800/40',
                bg: 'bg-emerald-50 dark:bg-emerald-950/30',
                text: 'text-emerald-700 dark:text-emerald-300',
                label: 'text-emerald-700/80 dark:text-emerald-400/80',
                icon: 'text-emerald-600/70 dark:text-emerald-400/70',
            },
            indigo: {
                border: 'border-indigo-200/70 dark:border-indigo-800/40',
                bg: 'bg-indigo-50 dark:bg-indigo-950/30',
                text: 'text-indigo-700 dark:text-indigo-300',
                label: 'text-indigo-700/80 dark:text-indigo-400/80',
                icon: 'text-indigo-600/70 dark:text-indigo-400/70',
            },
            amber: {
                border: 'border-amber-200/70 dark:border-amber-800/40',
                bg: 'bg-amber-50 dark:bg-amber-950/30',
                text: 'text-amber-700 dark:text-amber-200',
                label: 'text-amber-700/80 dark:text-amber-300/80',
                icon: 'text-amber-600/70 dark:text-amber-400/70',
            },
        };

        function renderCard(c) {
            const t = tints[c.tint];

            return `
                <div class="rounded-lg border ${t.border} ${t.bg} px-2.5 py-2">
                    <p class="truncate text-[9px] font-semibold uppercase tracking-widest ${t.label}">${esc(c.label)}</p>
                    <div class="mt-0.5 flex items-baseline gap-1">
                        <i class="${c.icon} text-xs ${t.icon}"></i>
                        <span class="text-lg font-black tabular-nums leading-none ${t.text}">${esc(c.value)}</span>
                    </div>
                </div>
            `;
        }

        const cards = [];
        if (isRsvpYes) {
            cards.push({
                tint: 'emerald',
                icon: 'ri-armchair-line',
                label: '{{ __('No. Kerusi') }}',
                value: pegawai.no_kerusi,
            });
            if (showTableNumber) {
                cards.push({
                    tint: 'indigo',
                    icon: 'ri-table-line',
                    label: '{{ __('No. Meja') }}',
                    value: pegawai.no_meja,
                });
            }
            if (hasLateNumber) {
                cards.push({
                    tint: 'amber',
                    icon: 'ri-phone-line',
                    label: '{{ __('No. Panggilan Lewat') }}',
                    value: pegawai.no_panggilan_lewat,
                });
            }
        } else {
            cards.push({
                tint: 'amber',
                icon: 'ri-phone-line',
                label: '{{ __('No. Panggilan Lewat') }}',
                value: Number(pegawai.no_panggilan_lewat) > 0 ? pegawai.no_panggilan_lewat : '-',
            });
        }

        const gridClass = cards.length === 3 ? 'grid-cols-3' : cards.length === 2 ? 'grid-cols-2' : 'grid-cols-1';

        const jasamuCells = isJasamu
            ? `
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ __('Tarikh Bersara') }}</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-foreground">${esc(pegawai.tarikh_bersara ?? '-')}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ __('Jenis Persaraan') }}</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-foreground">${esc(pegawai.bersara_name ?? '-')}</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ __('Tempoh Berkhidmat') }}</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-foreground">${pegawai.tempoh_berkhidmat === '-' ? '-' : esc(pegawai.tempoh_berkhidmat) + ' {{ __('tahun') }}'}</p>
                </div>
            `
            : '';

        const html = `
            <div class="text-left">
                <div class="mb-3 flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${chipClass}">
                        <i class="${headerIcon} text-lg"></i>
                    </span>
                    <h3 class="flex-1 min-w-0 truncate text-base font-semibold text-foreground">${esc(title)}</h3>
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold ${pillClass}">
                        <i class="${headerIcon} text-[10px]"></i>
                        ${actionLabel}
                    </span>
                </div>

                <div class="mb-3 flex items-center gap-3 rounded-xl border border-border/70 bg-muted/20 p-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">${esc(initials)}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-foreground">${esc(pegawai.nama)}</p>
                        <p class="font-mono text-xs text-muted-foreground">${esc(pegawai.no_kp)}</p>
                        <p class="mt-0.5 flex items-center gap-1 truncate text-xs text-muted-foreground">
                            <i class="ri-building-2-line text-[11px]"></i>
                            <span class="truncate">${esc(pegawai.ptj_name ?? '-')}</span>
                        </p>
                    </div>
                </div>

                <div class="mb-3 grid grid-cols-2 gap-x-4 gap-y-2.5 rounded-xl border border-border/70 bg-card px-3.5 py-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ __('Sesi') }}</p>
                        <p class="mt-0.5 truncate text-sm font-semibold text-foreground">${esc(sesiDisplayName)}</p>
                    </div>
                    ${jasamuCells}
                </div>

                <div>
                    <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ __('Ringkasan Penempatan') }}</p>
                    <div class="grid ${gridClass} gap-2">
                        ${cards.map(renderCard).join('')}
                    </div>
                </div>
            </div>
        `;

        return { html };
    };
</script>
