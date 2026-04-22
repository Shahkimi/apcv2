<div class="flex items-center justify-end gap-2">
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm js-edit-sesi-majlis"
        data-id="{{ $sesiMajlis->id }}"
        data-sesi="{{ $sesiMajlis->sesi }}"
        data-is_active="{{ $sesiMajlis->is_active ? 1 : 0 }}"
        data-is_late="{{ $sesiMajlis->is_late ? 1 : 0 }}"
        data-countdown-start-late="{{ $sesiMajlis->countdown_start_late }}"
        data-seat-offset="{{ $sesiMajlis->seat_offset ?? 0 }}"
        data-s-kehadiran="{{ (int) ($sesiMajlis->s_kehadiran ?? 0) }}"
        title="{{ __('Edit') }}"
    >
        <i class="ri-edit-line"></i>
    </button>
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm text-destructive js-delete-sesi-majlis"
        data-id="{{ $sesiMajlis->id }}"
        title="{{ __('Delete') }}"
    >
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
