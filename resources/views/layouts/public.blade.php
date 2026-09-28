<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 font-sans text-slate-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ $title ?? 'SafiFX' }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-full flex flex-col bg-slate-50 dark:bg-zinc-950">
        {{--
            The customer-facing layout for full-page Livewire routes (/send, /track).
            Deliberately separate from layouts/app (the staff sidebar shell, which
            links to /dashboard and /settings): those don't exist for customers, and
            wrapping public money-transfer pages in staff chrome was always wrong,
            not just now that those routes are hidden.
        --}}
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/90">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="SafiFX" class="h-8 block dark:hidden" />
                    <img src="{{ asset('images/logo-dark.png') }}" alt="SafiFX" class="h-8 hidden dark:block" />
                </a>

                <nav class="flex items-center gap-4">
                    <a href="{{ route('transfer.send') }}" wire:navigate class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white">
                        Send Money
                    </a>
                    <a href="{{ route('transfer.track') }}" wire:navigate class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white">
                        Track Transfer
                    </a>
                </nav>
            </div>
        </header>

        <main class="flex-1 py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white py-8 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 sm:flex-row sm:px-6 lg:px-8">
                <p class="text-xs text-slate-500 dark:text-zinc-500">
                    &copy; {{ date('Y') }} {{ config('safifx.platform.name', 'SafiFX') }}. All rights reserved.
                </p>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
