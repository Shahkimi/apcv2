@props(['title', 'id'])

<div class="rounded-xl border border-border bg-card shadow-sm">
    <div class="border-b border-border px-4 py-3">
        <h3 class="text-sm font-semibold text-card-foreground">{{ $title }}</h3>
    </div>
    <div class="p-4">
        <div id="{{ $id }}" class="min-h-[260px] w-full"></div>
    </div>
</div>
