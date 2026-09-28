@props([
    'sidebar' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <img src="{{ asset('images/logo.png') }}" alt="{{ config('safifx.platform.name', 'SafiFX') }}" class="h-8 block dark:hidden" />
    <img src="{{ asset('images/logo-dark.png') }}" alt="{{ config('safifx.platform.name', 'SafiFX') }}" class="h-8 hidden dark:block" />
</div>
