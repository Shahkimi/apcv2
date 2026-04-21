@props([
    'title',
    'description' => '',
    'createLabel' => __('Add New'),
    'createModalId' => 'create-modal',
    'showCreate' => true,
])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-foreground">{{ $title }}</h1>
        @if ($description !== '')
            <p class="text-sm text-muted-foreground">{{ $description }}</p>
        @endif
    </div>
    @if ($showCreate)
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="btn btn-primary flex items-center gap-2"
                data-modal-target="{{ $createModalId }}"
            >
                <i class="ri-add-line"></i>
                <span>{{ $createLabel }}</span>
            </button>
        </div>
    @endif
</div>
