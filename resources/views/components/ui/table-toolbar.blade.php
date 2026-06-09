@props(['placeholder' => 'Ketik kata kunci...'])

<form method="GET" {{ $attributes->merge(['class' => 'mb-4 flex flex-wrap items-end gap-3']) }}>
    <div class="min-w-[12rem] flex-1 max-w-sm">
        <label for="table-search-q" class="block text-xs font-medium text-on-surface-variant">Cari</label>
        <input
            type="search"
            name="q"
            id="table-search-q"
            value="{{ request('q') }}"
            placeholder="{{ $placeholder }}"
            class="mt-1 w-full rounded-md border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm text-on-surface"
        >
    </div>
    {{ $slot }}
    <button type="submit" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Cari</button>
    @if (filled(request('q')))
        <a href="{{ url()->current() }}" class="rounded-md border border-outline-variant px-3 py-2 text-sm text-on-surface hover:bg-surface-container-low">Reset</a>
    @endif
</form>
