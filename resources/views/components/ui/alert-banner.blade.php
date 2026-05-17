@props(['type' => 'warning'])

@php
    $types = [
        'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
        'error' => 'border-error/30 bg-error-container text-on-error-container',
        'info' => 'border-outline-variant bg-secondary-container text-on-surface',
    ];
    $classes = 'rounded-lg border px-4 py-3 text-sm '.($types[$type] ?? $types['warning']);
@endphp

<div {{ $attributes->merge(['class' => $classes]) }} role="alert">
    {{ $slot }}
</div>
