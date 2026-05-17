@php
    $bolehMaster = in_array(auth()->user()->role, ['admin', 'konsultan'], true);
    $iconBase = 'material-symbols-outlined flex size-10 items-center justify-center rounded-xl transition-all p-2';
    $iconActive = 'bg-primary-fixed/40 text-primary';
    $iconIdle = 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary';
@endphp

<aside class="fixed left-0 top-0 z-50 flex h-full w-sidebar-width flex-col border-r border-outline-variant/40 bg-surface-container-lowest py-4" aria-label="Menu samping">
    <div class="flex flex-col items-center gap-8 px-2">
        <a href="{{ route('dashboard') }}" class="text-primary" title="{{ config('app.name') }}">
            <span class="material-symbols-outlined text-[32px]" style="font-variation-settings: 'FILL' 1;">analytics</span>
        </a>
        <nav class="flex w-full flex-col items-center gap-2" aria-label="Utama">
            <a href="{{ route('dashboard') }}" title="Dasbor" class="group relative flex w-full justify-center">
                <span class="{{ $iconBase }} {{ request()->routeIs('dashboard') ? $iconActive : $iconIdle }}">dashboard</span>
                @if (request()->routeIs('dashboard'))
                    <span class="absolute -left-2 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-primary"></span>
                @endif
            </a>
            @if ($bolehMaster)
                <a href="{{ route('asesmen.index') }}" title="Asesmen" class="group relative flex w-full justify-center">
                    <span class="{{ $iconBase }} {{ request()->routeIs('asesmen.*') ? $iconActive : $iconIdle }}">assessment</span>
                    @if (request()->routeIs('asesmen.*'))
                        <span class="absolute -left-2 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-primary"></span>
                    @endif
                </a>
                <a href="{{ route('master.index') }}" title="Master data" class="group relative flex w-full justify-center">
                    <span class="{{ $iconBase }} {{ request()->routeIs(['master.*', 'peserta.impor-csv*']) ? $iconActive : $iconIdle }}">database</span>
                    @if (request()->routeIs(['master.*', 'peserta.impor-csv*']))
                        <span class="absolute -left-2 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-primary"></span>
                    @endif
                </a>
            @endif
        </nav>
    </div>
    <div class="mt-auto flex flex-col items-center gap-4 px-2 pb-2">
        <div class="flex size-9 items-center justify-center rounded-full bg-primary-fixed text-xs font-bold text-primary" title="{{ auth()->user()->name }}">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Keluar" class="{{ $iconBase }} {{ $iconIdle }} border-0 bg-transparent">
                <span class="material-symbols-outlined">logout</span>
            </button>
        </form>
    </div>
</aside>
