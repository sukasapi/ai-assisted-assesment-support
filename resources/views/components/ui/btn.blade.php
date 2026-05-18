@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $base =
        'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50';
    $variants = [
        'primary' => 'bg-zinc-900 text-white shadow-sm hover:bg-zinc-800 focus-visible:outline-zinc-900',
        'secondary' =>
            'border border-zinc-300 bg-white text-zinc-800 shadow-sm hover:bg-zinc-50 focus-visible:outline-zinc-900',
        'danger' =>
            'border border-rose-300 bg-rose-50 text-rose-900 hover:bg-rose-100 focus-visible:outline-rose-600',
        'success' => 'bg-emerald-800 text-white shadow-sm hover:bg-emerald-700 focus-visible:outline-emerald-800',
        'ghost' => 'text-zinc-700 hover:bg-zinc-100 focus-visible:outline-zinc-900',
        'link' =>
            'rounded-md px-1 py-0.5 font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:bg-zinc-50 hover:decoration-zinc-600 focus-visible:outline-zinc-900',
        'linkDanger' =>
            'rounded-md px-1 py-0.5 font-medium text-rose-700 underline decoration-rose-300 underline-offset-2 hover:bg-rose-50 hover:decoration-rose-600 focus-visible:outline-rose-600',
    ];
    $variantClass = $variants[$variant] ?? $variants['primary'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "{$base} {$variantClass}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "{$base} {$variantClass}"]) }}>
        {{ $slot }}
    </button>
@endif
