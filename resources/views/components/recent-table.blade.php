@props(['title', 'columns', 'rows', 'tableKey' => null, 'emptyText' => null, 'bare' => false])

<div @class(['rounded-xl border border-border bg-card shadow-sm' => ! $bare, 'h-full' => $bare])>
    <div class="border-b border-border px-4 py-3">
        <h3 class="text-sm font-semibold text-card-foreground">{{ $title }}</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-border bg-muted/40 text-muted-foreground">
                <tr>
                    @foreach ($columns as $col)
                        <th class="px-4 py-3 font-medium">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-border" @if ($tableKey) data-table-body="{{ $tableKey }}" data-cols="{{ count($columns) }}" data-empty="{{ $emptyText ?? __('Tiada data') }}" @endif>
                @forelse ($rows as $row)
                    <tr class="hover:bg-muted/30">
                        @foreach ($row as $cell)
                            <td class="px-4 py-3 text-card-foreground">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-4 py-6 text-center text-muted-foreground">{{ $emptyText ?? __('Tiada data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
