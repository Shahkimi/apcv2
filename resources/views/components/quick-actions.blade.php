@props(['actions'])

<div class="rounded-xl border border-border bg-card p-4 shadow-sm">
    <h3 class="mb-3 text-sm font-semibold text-card-foreground">{{ __('Quick actions') }}</h3>
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
        @foreach ($actions as $action)
            <button
                type="button"
                class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-left text-sm font-medium text-foreground transition-colors hover:bg-muted"
            >
                <i class="{{ $action['icon'] }} text-lg text-primary"></i>
                {{ $action['label'] }}
            </button>
        @endforeach
    </div>
</div>
