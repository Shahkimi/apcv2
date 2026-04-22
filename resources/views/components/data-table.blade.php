@props([
    'tableId',
    'columns',
    'columnHeaderClasses' => null,
])

<div {{ $attributes->merge(['class' => 'kawalan-dt-card']) }}>
    <table id="{{ $tableId }}" class="kawalan-dt-table display w-full">
        <thead class="border-b border-border bg-muted/30 dark:bg-muted/10">
            <tr>
                @foreach ($columns as $index => $column)
                    @php
                        $headerAlign = is_array($columnHeaderClasses) && array_key_exists($index, $columnHeaderClasses)
                            ? $columnHeaderClasses[$index]
                            : 'text-left';
                    @endphp
                    <th class="px-4 py-3 text-sm font-medium text-muted-foreground {{ $headerAlign }}">
                        {{ $column }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
