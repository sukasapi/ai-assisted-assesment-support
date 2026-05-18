@php
    $bolehMaster = in_array(auth()->user()->role, ['admin', 'konsultan'], true);
    $iconBase = 'material-symbols-outlined flex size-10 shrink-0 items-center justify-center rounded-xl transition-all p-2';
    $iconActive = 'bg-primary-fixed/40 text-primary';
    $iconIdle = 'text-on-surface-variant group-hover:bg-surface-container-low group-hover:text-primary';
    $labelBase = 'hidden truncate text-sm font-medium md:block';
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
        $navItems[] = [
            'href' => route('asesmen.index'),
            'active' => request()->routeIs('asesmen.*'),
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
    class="fixed left-0 top-0 z-50 flex h-full w-sidebar-width flex-col border-r border-outline-variant/40 bg-surface-container-lowest py-4 md:w-sidebar-expanded"
    aria-label="Menu samping"
>
    <div class="flex flex-col gap-8 px-2 md:px-3">
        <a
            href="{{ route('dashboard') }}"
            class="group flex w-full items-center justify-center gap-3 rounded-xl text-primary md:justify-start md:px-2"
            title="{{ config('app.name') }}"
        >
            <span class="material-symbols-outlined shrink-0 text-[32px]" style="font-variation-settings: 'FILL' 1;">analytics</span>
            <span class="font-display hidden truncate text-sm font-semibold text-on-surface md:block">{{ config('app.name') }}</span>
        </a>

        <nav class="flex w-full flex-col gap-1" aria-label="Utama">
            @foreach ($navItems as $item)
                <a
                    href="{{ $item['href'] }}"
                    title="{{ $item['label'] }}"
                    class="group relative flex w-full items-center justify-center gap-3 rounded-xl py-0.5 md:justify-start md:px-2"
                >
                    @if ($item['active'])
                        <span class="absolute -left-2 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-primary md:left-0"></span>
                    @endif
                    <span class="{{ $iconBase }} {{ $item['active'] ? $iconActive : $iconIdle }}">{{ $item['icon'] }}</span>
                    <span class="{{ $labelBase }} {{ $item['active'] ? $labelActive : $labelIdle }}">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="mt-auto flex flex-col gap-3 px-2 pb-2 md:px-3">
        <div class="flex items-center justify-center gap-3 md:justify-start md:px-2">
            <div
                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-fixed text-xs font-bold text-primary"
                title="{{ auth()->user()->name }}"
            >
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="hidden min-w-0 md:block">
                <p class="truncate text-sm font-medium text-on-surface">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-on-surface-variant">{{ auth()->user()->role }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button
                type="submit"
                title="Keluar"
                class="group flex w-full items-center justify-center gap-3 rounded-xl border-0 bg-transparent py-0.5 md:justify-start md:px-2"
            >
                <span class="{{ $iconBase }} {{ $iconIdle }}">
                    <span class="material-symbols-outlined">logout</span>
                </span>
                <span class="{{ $labelBase }} {{ $labelIdle }}">Keluar</span>
            </button>
        </form>
    </div>
</aside>
