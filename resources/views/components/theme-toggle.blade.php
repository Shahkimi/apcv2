<button
    type="button"
    class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
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
    <i class="ri-moon-line text-xl" x-show="!dark"></i>
    <i class="ri-sun-line text-xl" x-show="dark" x-cloak></i>
</button>
