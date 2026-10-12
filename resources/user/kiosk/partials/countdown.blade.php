{{-- Auto-return countdown ring for kiosk result screens. $ring = Tailwind stroke colour class. --}}
<div class="mt-8 flex items-center justify-center gap-4 text-slate-500">
    <div class="relative h-14 w-14 shrink-0">
        <svg viewBox="0 0 36 36" class="h-14 w-14 -rotate-90" aria-hidden="true">
            <circle cx="18" cy="18" r="16" fill="none" class="stroke-slate-200" stroke-width="3" />
            <circle
                cx="18"
                cy="18"
                r="16"
                fill="none"
                class="{{ $ring }}"
                stroke-width="3"
                stroke-linecap="round"
                stroke-dasharray="100.53"
                :stroke-dashoffset="ringOffset()"
            />
        </svg>
        <span class="absolute inset-0 grid place-items-center text-base font-bold tabular-nums text-slate-700" x-text="secondsLeft()"></span>
    </div>
    <p class="text-left text-sm leading-snug">
        {{ __('Kembali ke skrin utama secara automatik.') }}<br>
        <span class="font-medium text-slate-700" x-text="returnHint()"></span>
    </p>
</div>
