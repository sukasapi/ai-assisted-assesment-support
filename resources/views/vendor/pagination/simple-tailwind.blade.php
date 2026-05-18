@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="mt-6 flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span class="inline-flex cursor-not-allowed items-center rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-400">
                Sebelumnya
            </span>
        @else
            <a
                href="{{ $paginator->previousPageUrl() }}"
                rel="prev"
                class="inline-flex items-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-800 shadow-sm transition hover:bg-zinc-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900"
            >
                Sebelumnya
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a
                href="{{ $paginator->nextPageUrl() }}"
                rel="next"
                class="inline-flex items-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-800 shadow-sm transition hover:bg-zinc-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900"
            >
                Berikutnya
            </a>
        @else
            <span class="inline-flex cursor-not-allowed items-center rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-400">
                Berikutnya
            </span>
        @endif
    </nav>
@endif
