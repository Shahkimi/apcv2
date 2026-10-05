@props(['actions', 'bare' => false])

<div @class(['rounded-xl border border-border bg-card p-4 shadow-sm' => ! $bare])>
    <h3 class="mb-3 text-sm font-semibold text-card-foreground">{{ __('Quick actions') }}</h3>
    <div @class(['grid gap-2', 'grid-cols-1 sm:grid-cols-3' => ! $bare, 'grid-cols-2' => $bare])>
        @foreach ($actions as $action)
            @php
                $actionClass = 'flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-left text-sm font-medium text-foreground transition-colors hover:bg-muted';
            @endphp
            @if (! empty($action['href']))
                <a href="{{ $action['href'] }}" class="{{ $actionClass }}">
                    <i class="{{ $action['icon'] }} text-lg text-primary"></i>
                    {{ $action['label'] }}
                </a>
            @else
                <button type="button" class="{{ $actionClass }}">
                    <i class="{{ $action['icon'] }} text-lg text-primary"></i>
                    {{ $action['label'] }}
                </button>
            @endif
        @endforeach
    </div>
</div>
