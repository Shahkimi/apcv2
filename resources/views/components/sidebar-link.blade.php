@props(['href', 'icon', 'label', 'active' => false, 'tooltip' => true])

<a
    href="{{ $href }}"
    data-sb-link
    @if ($active) aria-current="page" @endif
    @if ($tooltip) aria-label="{{ $label }}" data-tooltip="{{ $label }}" @endif
    x-on:click="sidebarOpen = false"
    {{ $attributes->class([
        'sb-item relative flex items-center gap-3 rounded-lg px-3.5 py-2 text-sm font-medium transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring',
        'bg-primary/10 font-semibold text-primary' => $active,
        'text-sidebar-foreground/75 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' => ! $active,
    ]) }}
>
    <i class="{{ $icon }} text-lg shrink-0"></i>
    <span class="sb-label truncate">{{ $label }}</span>
</a>
