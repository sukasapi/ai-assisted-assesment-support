@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'card-depth p-6']) }}>
    @if ($title)
        <div class="mb-4">
            <h2 class="font-display text-sm font-bold uppercase tracking-wide text-on-surface-variant">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 text-sm text-on-surface-variant">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
