@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'accent-gradient text-on-primary shadow-md shadow-primary/20 hover:opacity-95',
        'secondary' => 'border border-outline-variant bg-surface-container-lowest text-on-surface hover:bg-surface-container-low',
        'danger' => 'border border-error/30 bg-error-container text-on-error-container hover:bg-error-container/80',
        'ai' => 'border border-primary/30 bg-secondary-container text-primary hover:bg-primary-fixed/50',
        'ghost' => 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
