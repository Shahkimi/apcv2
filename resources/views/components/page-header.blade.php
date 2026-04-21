@props(['title', 'subtitle' => null])

<div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-2xl font-bold tracking-tight text-foreground">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
