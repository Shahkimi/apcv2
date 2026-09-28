<x-guest-layout>
    <div class="mb-6">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 ring-1 ring-indigo-500/20 dark:text-indigo-400" aria-hidden="true">
            <i class="ri-login-circle-line text-xl leading-none"></i>
        </span>
        <h1 class="mt-4 text-xl font-semibold text-foreground">{{ __('Log masuk') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">{{ __('Masukkan nama pengguna dan kata laluan anda.') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form
        method="POST"
        action="{{ route('login') }}"
        x-data="{ submitting: false }"
        x-on:submit="submitting = true"
    >
        @csrf

        <div class="group">
            <x-input-label for="username" :value="__('Nama pengguna')" />
            <div class="relative mt-1">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-400" aria-hidden="true">
                    <i class="ri-user-line text-lg leading-none"></i>
                </span>
                <x-text-input
                    id="username"
                    class="block w-full py-2 pl-10 pr-3"
                    type="text"
                    name="username"
                    :value="old('username')"
                    required
                    autofocus
                    autocomplete="username"
                />
            </div>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div class="group mt-4" x-data="{ show: false }">
            <x-input-label for="password" :value="__('Kata laluan')" />
            <div class="relative mt-1">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors group-focus-within:text-indigo-600 dark:group-focus-within:text-indigo-400" aria-hidden="true">
                    <i class="ri-lock-line text-lg leading-none"></i>
                </span>
                <x-text-input
                    id="password"
                    class="block w-full py-2 pl-10 pr-10"
                    x-bind:type="show ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                />
                <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    x-on:click="show = !show"
                    x-bind:aria-label="show ? '{{ __('Sembunyikan kata laluan') }}' : '{{ __('Papar kata laluan') }}'"
                >
                    <i class="text-lg leading-none" x-bind:class="show ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4 flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input id="remember_me" type="checkbox" class="rounded border-input text-indigo-600 shadow-sm focus:ring-2 focus:ring-indigo-500/40" name="remember">
                <span class="text-sm text-muted-foreground">{{ __('Ingat saya') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-indigo-600 hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background rounded-md dark:text-indigo-400" href="{{ route('password.request') }}">
                    {{ __('Lupa kata laluan?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn mt-6 w-full bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/25 hover:opacity-95" x-bind:disabled="submitting">
            <i class="ri-loader-4-line animate-spin" x-show="submitting" x-cloak></i>
            <span x-text="submitting ? '{{ __('Log masuk...') }}' : '{{ __('Log masuk') }}'"></span>
        </button>
    </form>
</x-guest-layout>
