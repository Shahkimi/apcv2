<div class="flex items-center justify-end gap-2">
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm js-edit-sesi-majlis"
        data-id="{{ $sesiMajlis->id }}"
        data-sesi="{{ $sesiMajlis->sesi }}"
        data-is_active="{{ $sesiMajlis->is_active ? 1 : 0 }}"
        data-is_on_air="{{ $sesiMajlis->is_on_air ? 1 : 0 }}"
        data-is_late="{{ $sesiMajlis->is_late ? 1 : 0 }}"
        data-countdown_start="{{ $sesiMajlis->countdown_start }}"
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
