<button
    type="button"
    class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    }"
    x-on:click="toggle()"
    aria-label="{{ __('Toggle theme') }}"
>
    <i
        class="ri-moon-line absolute text-xl"
        x-show="!dark"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -rotate-90 scale-75"
        x-transition:enter-end="opacity-100 rotate-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 rotate-0 scale-100"
        x-transition:leave-end="opacity-0 rotate-90 scale-75"
    ></i>
    <i
        class="ri-sun-line absolute text-xl"
        x-show="dark"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 rotate-90 scale-75"
        x-transition:enter-end="opacity-100 rotate-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 rotate-0 scale-100"
        x-transition:leave-end="opacity-0 -rotate-90 scale-75"
    ></i>
</button>
