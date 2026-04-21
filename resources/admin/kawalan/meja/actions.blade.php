<div class="flex items-center justify-end gap-1">
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm rounded-xl ring-1 ring-transparent transition hover:bg-muted/80 hover:ring-border/60 js-edit-meja"
        data-id="{{ $meja->id }}"
        data-sizing="{{ $meja->sizing }}"
        title="{{ __('Edit') }}"
    >
        <i class="ri-edit-line"></i>
    </button>
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm rounded-xl text-destructive ring-1 ring-transparent transition hover:bg-destructive/10 hover:ring-destructive/20 js-delete-meja"
        data-id="{{ $meja->id }}"
        title="{{ __('Delete') }}"
    >
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
