@props(['wide' => false])

<div
    {{ $attributes->merge([
        'class' =>
            'kawalan-shell mx-auto w-full space-y-8 '
            . ($wide ? 'max-w-[100rem]' : 'max-w-[min(100%,88rem)]'),
    ]) }}
>
    {{ $slot }}
</div>
