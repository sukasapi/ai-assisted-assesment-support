@props(['tone' => 'default'])

@php
    $tones = [
        'default' => 'bg-surface-container-high text-on-surface-variant border-outline-variant/50',
        'draft' => 'bg-amber-50 text-amber-800 border-amber-200',
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
        'error' => 'bg-error-container text-on-error-container border-error/20',
        'primary' => 'bg-primary-fixed text-on-primary-fixed border-primary/20',
        'ai' => 'bg-secondary-container text-primary border-outline-variant',
    ];
    $classes = 'inline-flex items-center gap-1 rounded-lg border px-2.5 py-0.5 text-xs font-semibold '.($tones[$tone] ?? $tones['default']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
