@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('safifx.platform.name', 'SafiFX')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-xl bg-emerald-600 font-extrabold text-white">
            FX
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('safifx.platform.name', 'SafiFX')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-xl bg-emerald-600 font-extrabold text-white">
            FX
        </x-slot>
    </flux:brand>
@endif
