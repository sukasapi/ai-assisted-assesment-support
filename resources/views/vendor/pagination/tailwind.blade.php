@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="mt-6">
        <div class="flex items-center justify-between gap-3 sm:hidden">
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
        </div>

        <div class="hidden flex-1 flex-col gap-4 sm:flex sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm leading-relaxed text-zinc-600">
                    Menampilkan
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-zinc-900">{{ $paginator->firstItem() }}</span>
                        sampai
                        <span class="font-semibold text-zinc-900">{{ $paginator->lastItem() }}</span>
                    @else
                        <span class="font-semibold text-zinc-900">{{ $paginator->count() }}</span>
                    @endif
                    dari
                    <span class="font-semibold text-zinc-900">{{ $paginator->total() }}</span>
                    hasil
                </p>
            </div>

            <div>
                <span class="inline-flex overflow-hidden rounded-lg shadow-sm ring-1 ring-zinc-200/80">
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Halaman sebelumnya">
                            <span
                                class="inline-flex cursor-not-allowed items-center bg-zinc-50 px-2.5 py-2 text-zinc-400"
                                aria-hidden="true"
                            >
                                <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path
                                        fill-rule="evenodd"
                                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a
                            href="{{ $paginator->previousPageUrl() }}"
                            rel="prev"
                            class="inline-flex items-center bg-white px-2.5 py-2 text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-900 focus-visible:z-10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-zinc-900"
                            aria-label="Halaman sebelumnya"
                        >
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path
                                    fill-rule="evenodd"
                                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </a>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span
                                    class="inline-flex cursor-default items-center border-l border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-500"
                                >{{ $element }}</span>
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span
                                            class="inline-flex cursor-default items-center border-l border-zinc-200 bg-zinc-900 px-3.5 py-2 text-sm font-semibold text-white"
                                        >{{ $page }}</span>
                                    </span>
                                @else
                                    <a
                                        href="{{ $url }}"
                                        class="inline-flex items-center border-l border-zinc-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 focus-visible:z-10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-zinc-900"
                                        aria-label="Ke halaman {{ $page }}"
                                    >{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a
                            href="{{ $paginator->nextPageUrl() }}"
                            rel="next"
                            class="inline-flex items-center border-l border-zinc-200 bg-white px-2.5 py-2 text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-900 focus-visible:z-10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-zinc-900"
                            aria-label="Halaman berikutnya"
                        >
                            <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path
                                    fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Halaman berikutnya">
                            <span
                                class="inline-flex cursor-not-allowed items-center border-l border-zinc-200 bg-zinc-50 px-2.5 py-2 text-zinc-400"
                                aria-hidden="true"
                            >
                                <svg class="size-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path
                                        fill-rule="evenodd"
                                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
