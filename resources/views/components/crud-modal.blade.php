@props([
    'modalId',
    'title',
    'size' => 'modal-lg',
])

<div id="{{ $modalId }}" @class(['modal', $size])>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">{{ $title }}</h3>
            <button type="button" class="modal-close" data-modal-close aria-label="{{ __('Close') }}">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="modal-body">
            {{ $slot }}
        </div>
    </div>
</div>
