@props([
    'label',
    'name',
    'type' => 'text',
    'error' => null,
    'hint' => null,
])

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $name }}" class="block text-sm font-semibold text-on-surface">{{ $label }}</label>
    @if ($hint)
        <p class="mt-0.5 text-xs text-on-surface-variant">{{ $hint }}</p>
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->except('class')->merge([
            'class' => 'mt-1 w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm text-on-surface shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:bg-surface-container-low disabled:text-on-surface-variant',
        ]) }}
    />
    @if ($error)
        <p class="mt-1 text-xs text-error">{{ $error }}</p>
    @endif
</div>
