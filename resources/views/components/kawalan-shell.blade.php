@props(['wide' => false, 'fill' => false])

<div
    {{ $attributes->merge([
        'class' => $fill
            ? 'kawalan-shell flex w-full flex-col gap-4 lg:h-full lg:min-h-0 lg:overflow-hidden'
            : 'kawalan-shell mx-auto w-full space-y-8 '
                . ($wide ? 'max-w-[100rem]' : 'max-w-[min(100%,88rem)]'),
    ]) }}
>
    {{ $slot }}
</div>
