<div class="flex items-center justify-end gap-2">
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm js-edit-jawatan"
        data-id="{{ $jawatan->id }}"
        data-desc_jawatan="{{ $jawatan->desc_jawatan }}"
        title="{{ __('Edit') }}"
    >
        <i class="ri-edit-line"></i>
    </button>
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm text-destructive js-delete-jawatan"
        data-id="{{ $jawatan->id }}"
        title="{{ __('Delete') }}"
    >
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
