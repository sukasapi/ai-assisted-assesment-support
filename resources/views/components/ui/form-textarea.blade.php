@props([
    'label',
    'name',
    'error' => null,
    'hint' => null,
    'rows' => 3,
])

<div class="wysiwyg">
    <label for="{{ $name }}" class="block text-sm font-semibold text-on-surface">{{ $label }}</label>
    @if ($hint)
        <p class="mt-0.5 text-xs text-on-surface-variant">{{ $hint }}</p>
    @endif
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->merge([
            'class' => 'mt-1 w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm text-on-surface shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
        ]) }}
    >{{ $slot }}</textarea>
    @if ($error)
        <p class="mt-1 text-xs text-error">{{ $error }}</p>
    @endif
</div>
