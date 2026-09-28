<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 font-sans text-slate-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>SafiFX &ndash; Cross-border Mobile Money Made Simple</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-full flex flex-col bg-slate-50 dark:bg-zinc-950">
        <!-- Main Navigation Header -->
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/90">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="SafiFX" class="h-8 block dark:hidden" />
                    <img src="{{ asset('images/logo-dark.png') }}" alt="SafiFX" class="h-8 hidden dark:block" />
                </a>

                <!-- Nav Links & Auth CTA -->
                <nav class="flex items-center gap-4">
                    <a href="#how-it-works" class="hidden text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white sm:inline-block">
                        How It Works
                    </a>
                    <a href="#countries" class="hidden text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white sm:inline-block">
                        Supported Countries
                    </a>
                    <a href="{{ route('transfer.track') }}" wire:navigate class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white">
                        Track Transfer
                    </a>

                    @auth
                        <a href="/admin" class="inline-flex">
                            <flux:button variant="primary" icon="user-circle">
                                Admin Dashboard
                            </flux:button>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex">
                            <flux:button variant="subtle" icon="arrow-right-end-on-rectangle">
                                Sign In
                            </flux:button>
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Hero & Livewire Calculator Section -->
            <section class="py-12 sm:py-16 lg:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
                        <!-- Left Hero Column -->
                        <div class="space-y-6 lg:col-span-7">
                            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <flux:icon.sparkles class="size-3.5" />
                                Instant Cross-Border Payouts
                            </div>
                            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-5xl lg:text-6xl">
                                Move money across Africa with confidence.
                            </h1>
                            <p class="max-w-2xl text-base text-slate-600 dark:text-zinc-400 sm:text-lg">
                                Fast, reliable mobile-money currency exchange across Kenya, Uganda, Tanzania, Rwanda, and global USD transfers.
                            </p>
                            <div class="flex flex-wrap items-center gap-4 pt-2">
                                <a href="{{ route('transfer.track') }}" wire:navigate>
                                    <flux:button variant="subtle" icon="magnifying-glass" class="border border-slate-200 dark:border-zinc-800">
                                        Track an Existing Transfer
                                    </flux:button>
                                </a>
                                <a href="/admin" class="text-xs text-slate-500 hover:text-slate-900 dark:text-zinc-500 dark:hover:text-zinc-300">
                                    Staff Portal Login &rarr;
                                </a>
                            </div>
                        </div>

                        <!-- Right Column: Livewire 4 Calculator SFC -->
                        <div class="lg:col-span-5">
                            <livewire:pages.⚡calculator />
                        </div>
                    </div>
                </div>
            </section>

            <!-- How It Works Section -->
            <section id="how-it-works" class="border-t border-slate-200 bg-white py-16 dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Simple Process</span>
                        <h2 class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white sm:text-3xl">How SafiFX Works in 4 Steps</h2>
                    </div>

                    <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Step 1 -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 dark:border-zinc-800 dark:bg-zinc-950/50">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-bold text-sm">
                                01
                            </div>
                            <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">Calculate</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">Select your sending and receiving countries and enter the exchange amount.</p>
                        </div>

                        <!-- Step 2 -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 dark:border-zinc-800 dark:bg-zinc-950/50">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-bold text-sm">
                                02
                            </div>
                            <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">Pay Deposit</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">Send money using the official mobile money deposit number provided.</p>
                        </div>

                        <!-- Step 3 -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 dark:border-zinc-800 dark:bg-zinc-950/50">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-bold text-sm">
                                03
                            </div>
                            <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">Submit Reference</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">Enter your payment transaction code to register the transfer for verification.</p>
                        </div>

                        <!-- Step 4 -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 dark:border-zinc-800 dark:bg-zinc-950/50">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-bold text-sm">
                                04
                            </div>
                            <h3 class="mt-4 text-base font-bold text-slate-900 dark:text-white">Recipient Payout</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">SafiFX processes the direct payout to your recipient’s mobile money account.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Supported Countries Section -->
            <section id="countries" class="py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Coverage</span>
                        <h2 class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white sm:text-3xl">Supported Corridors</h2>
                    </div>

                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-900">
                            <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Kenya &bull; M-PESA / Airtel</span>
                        </div>
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-900">
                            <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Uganda &bull; MTN / Airtel</span>
                        </div>
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-900">
                            <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Tanzania &bull; Vodacom / Tigo</span>
                        </div>
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-900">
                            <flux:icon.globe-alt class="size-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Rwanda &bull; MTN MoMo</span>
                        </div>
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-900">
                            <flux:icon.currency-dollar class="size-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-sm font-medium text-slate-900 dark:text-white">United States &bull; USD</span>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-8 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 sm:flex-row sm:px-6 lg:px-8">
                <p class="text-xs text-slate-500 dark:text-zinc-500">
                    &copy; {{ date('Y') }} {{ config('safifx.platform.name', 'SafiFX') }}. All rights reserved.
                </p>
                <div class="flex items-center gap-6 text-xs font-medium text-slate-600 dark:text-zinc-400">
                    <a href="{{ route('transfer.track') }}" wire:navigate class="hover:text-slate-900 dark:hover:text-white">Track Transfer</a>
                    <a href="/admin" class="hover:text-slate-900 dark:hover:text-white">Staff Portal</a>
                </div>
            </div>
        </footer>
    </body>
</html>
