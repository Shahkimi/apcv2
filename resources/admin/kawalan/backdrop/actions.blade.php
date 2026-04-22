<div class="flex items-center justify-end gap-2">
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm js-toggle-active-backdrop"
        data-id="{{ $backdrop->id }}"
        title="{{ $backdrop->is_active ? __('Nyahaktifkan') : __('Aktifkan') }}"
    >
        <i class="{{ $backdrop->is_active ? 'ri-eye-line' : 'ri-eye-off-line' }}"></i>
    </button>
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm js-edit-backdrop"
        data-id="{{ $backdrop->id }}"
        data-backdrop-name="{{ $backdrop->backdrop_name }}"
        data-image-url="{{ $backdrop->image_url }}"
        title="{{ __('Edit') }}"
    >
        <i class="ri-edit-line"></i>
    </button>
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm text-destructive js-delete-backdrop"
        data-id="{{ $backdrop->id }}"
        title="{{ __('Delete') }}"
    >
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
