@php
    /** @var \App\Models\User $rowUser */
@endphp

<div class="flex items-center justify-end gap-2">
    <button
        type="button"
        class="btn btn-ghost btn-icon btn-sm js-edit-user"
        data-id="{{ $rowUser->id }}"
        data-name="{{ $rowUser->name }}"
        data-username="{{ $rowUser->username }}"
        data-role="{{ $rowUser->role }}"
        title="{{ __('Edit') }}"
    >
        <i class="ri-edit-line"></i>
    </button>
    @if ($rowUser->id !== auth()->id())
        <button
            type="button"
            class="btn btn-ghost btn-icon btn-sm text-destructive js-delete-user"
            data-id="{{ $rowUser->id }}"
            title="{{ __('Delete') }}"
        >
            <i class="ri-delete-bin-line"></i>
        </button>
    @endif
</div>
