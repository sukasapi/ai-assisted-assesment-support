@props(['paginator'])

@if ($paginator->total() > 0)
    <div {{ $attributes->merge(['class' => 'mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
        <p class="text-sm text-on-surface-variant">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
            @if ($paginator->lastPage() > 1)
                · Halaman {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            @endif
        </p>
        @if ($paginator->hasPages())
            {{ $paginator->withQueryString()->links() }}
        @endif
    </div>
@endif
