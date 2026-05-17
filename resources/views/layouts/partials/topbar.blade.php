<header class="sticky top-0 z-40 flex h-16 w-full items-center justify-between border-b border-outline-variant/30 bg-surface-container-lowest/90 px-gutter backdrop-blur-md">
    <div class="flex min-w-0 items-center gap-4">
        @hasSection('topbar_back')
            @yield('topbar_back')
        @endif
    </div>
    <div class="flex items-center gap-4">
        <span class="hidden text-sm text-on-surface-variant sm:inline">{{ auth()->user()->name }}</span>
        <span class="rounded-lg bg-surface-container-low px-2 py-0.5 text-xs font-medium text-on-surface-variant">{{ auth()->user()->role }}</span>
        <div class="flex size-9 items-center justify-center rounded-full bg-primary-fixed font-display text-sm font-bold text-primary">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
        </div>
    </div>
</header>
