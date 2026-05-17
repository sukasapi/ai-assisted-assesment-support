@props([
    'title',
    'backUrl' => null,
    'backLabel' => 'Kembali',
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    @if ($backUrl)
        <a href="{{ $backUrl }}" class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-on-surface-variant transition-colors hover:text-primary">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            {{ $backLabel }}
        </a>
    @endif
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-on-surface md:text-3xl">{{ $title }}</h1>
            @isset($description)
                <p class="mt-1 max-w-2xl text-sm text-on-surface-variant">{{ $description }}</p>
            @endisset
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
    @isset($meta)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            {{ $meta }}
        </div>
    @endisset
</div>
