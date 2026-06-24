@php
    $bolehMaster = auth()->user()?->hasPeran('admin', 'konsultan');
    $iconBase = 'material-symbols-outlined flex size-10 shrink-0 items-center justify-center rounded-xl transition-all p-2';
    $iconActive = 'bg-primary-fixed/40 text-primary';
    $iconIdle = 'text-on-surface-variant group-hover:bg-surface-container-low group-hover:text-primary';
    $labelActive = 'text-primary';
    $labelIdle = 'text-on-surface-variant group-hover:text-on-surface';

    $navItems = [
        [
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => 'dashboard',
            'label' => 'Dasbor',
        ],
    ];

    if ($bolehMaster) {
        if (auth()->user()?->isAdmin()) {
            $navItems[] = [
                'href' => route('sesi-asesmen.index'),
                'active' => request()->routeIs('sesi-asesmen.*'),
                'icon' => 'event',
                'label' => 'Sesi assessment',
            ];
        }
        $navItems[] = [
            'href' => route('asesmen.index'),
            'active' => request()->routeIs('asesmen.*') && ! request()->routeIs('asesmen.token*'),
            'icon' => 'assessment',
            'label' => 'Asesmen',
        ];
        $navItems[] = [
            'href' => route('master.index'),
            'active' => request()->routeIs(['master.*', 'peserta.impor-csv*']),
            'icon' => 'database',
            'label' => 'Master data',
        ];
    }
@endphp

<aside
    id="app-sidebar"
    class="app-sidebar fixed left-0 top-0 z-50 flex h-dvh min-h-dvh shrink-0 flex-col border-r border-outline-variant/40 bg-surface-container-lowest md:static"
    aria-label="Menu samping"
>
    <div class="sidebar-body flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto px-2 py-4 md:px-3">
        <div class="sidebar-header">
            <div class="flex items-center justify-between gap-2">
                <span
                    class="material-symbols-outlined shrink-0 text-[32px] text-primary"
                    style="font-variation-settings: 'FILL' 1;"
                    aria-hidden="true"
                >analytics</span>
                <button
                    type="button"
                    id="sidebar-collapse-toggle"
                    class="sidebar-collapse-toggle hidden size-9 shrink-0 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-primary md:inline-flex"
                    aria-expanded="true"
                    aria-controls="app-sidebar"
                    title="Ciutkan menu"
                >
                    <span class="material-symbols-outlined text-xl" id="sidebar-collapse-icon" aria-hidden="true">chevron_left</span>
                </button>
            </div>
            <a
                href="{{ route('dashboard') }}"
                class="sidebar-brand group mt-2 block rounded-xl text-primary"
                title="{{ config('app.name') }}"
            >
                <span class="sidebar-brand-text font-display text-sm font-semibold leading-snug text-on-surface">{{ config('app.name') }}</span>
            </a>
        </div>

        <nav class="flex w-full flex-col gap-1" aria-label="Utama">
            @foreach ($navItems as $item)
                <a
                    href="{{ $item['href'] }}"
                    title="{{ $item['label'] }}"
                    class="sidebar-nav-link group"
                >
                    @if ($item['active'])
                        <span class="sidebar-nav-active-bar absolute -left-2 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-primary md:-left-3"></span>
                    @endif
                    <span class="{{ $iconBase }} {{ $item['active'] ? $iconActive : $iconIdle }}">{{ $item['icon'] }}</span>
                    <span class="sidebar-label whitespace-nowrap {{ $item['active'] ? $labelActive : $labelIdle }}">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <footer class="sidebar-footer mt-auto flex shrink-0 flex-col gap-3 border-t border-outline-variant/40 bg-surface-container-lowest px-2 py-3 md:px-3">
        <div class="sidebar-footer-row md:px-2">
            <div
                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-fixed text-xs font-bold text-primary"
                title="{{ auth()->user()->name }}"
            >
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="sidebar-footer-text min-w-0">
                <p class="text-sm font-medium leading-snug text-on-surface">{{ auth()->user()->name }}</p>
                <p class="text-xs text-on-surface-variant">{{ auth()->user()?->peran }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" title="Keluar" class="sidebar-footer-btn group md:px-2">
                <span class="{{ $iconBase }} {{ $iconIdle }}">
                    <span class="material-symbols-outlined">logout</span>
                </span>
                <span class="sidebar-label whitespace-nowrap {{ $labelIdle }}">Keluar</span>
            </button>
        </form>
    </footer>
</aside>
