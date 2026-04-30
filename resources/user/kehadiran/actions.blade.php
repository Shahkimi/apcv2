@php
    /** @var \App\Models\Pegawai $pegawai */
    $isAttended = (bool) $pegawai->is_attend;
@endphp

<div class="flex w-full items-center justify-center">
    <button
        type="button"
        class="{{ $isAttended
            ? 'btn btn-sm rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-200'
            : 'btn btn-sm rounded-lg bg-primary text-primary-foreground shadow-sm hover:brightness-105' }} js-verify-kehadiran"
        data-id="{{ $pegawai->id }}"
        data-nama="{{ $pegawai->nama }}"
        data-is-attend="{{ $isAttended ? 1 : 0 }}"
        title="{{ $isAttended ? __('Tandakan belum hadir') : __('Sahkan kehadiran') }}"
    >
        <i class="{{ $isAttended ? 'ri-checkbox-circle-line' : 'ri-check-line' }}"></i>
        <span>{{ $isAttended ? __('Hadir') : __('Sahkan') }}</span>
    </button>
</div>
