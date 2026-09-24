<div class="flex items-center justify-end gap-2">
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm js-edit-bersara"
        data-id="{{ $bersara->id }}"
        data-jenis_bersara="{{ $bersara->jenis_bersara }}"
        title="{{ __('Edit') }}"
    >
        <i class="ri-edit-line"></i>
    </button>
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm text-destructive js-delete-bersara"
        data-id="{{ $bersara->id }}"
        title="{{ __('Delete') }}"
    >
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
