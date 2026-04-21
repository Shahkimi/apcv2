<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="font-sans antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-10 sm:px-6">
        <div class="mb-6">
            <a href="/" class="text-xl font-bold text-foreground">{{ config('app.name', 'Laravel') }}</a>
        </div>

        <div class="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
