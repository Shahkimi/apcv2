@props(['tableId', 'columns'])

<div {{ $attributes->merge(['class' => 'kawalan-dt-card']) }}>
    <table id="{{ $tableId }}" class="kawalan-dt-table display w-full">
        <thead class="border-b border-border bg-muted/30 dark:bg-muted/10">
            <tr>
                @foreach ($columns as $column)
                    <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">
                        {{ $column }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
