<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
@guest
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center px-4 py-3">
                <span class="font-semibold text-zinc-800">{{ config('app.name') }}</span>
            </div>
        </header>
        <main class="flex flex-1 flex-col items-center px-4 py-10">
            @yield('content')
        </main>
    </div>
@endguest

@auth
    @php
        $bolehMaster = in_array(auth()->user()->role, ['admin', 'konsultan'], true);
        $navActive = 'flex items-center rounded-md px-3 py-2 text-sm transition-colors';
        $navOn = 'bg-zinc-100 font-medium text-zinc-900';
        $navOff = 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900';
    @endphp
    <div class="flex min-h-screen">
        <aside class="flex w-60 shrink-0 flex-col border-r border-zinc-200 bg-white lg:w-64" aria-label="Menu samping">
            <div class="border-b border-zinc-100 px-4 py-4">
                <a href="{{ route('dashboard') }}" class="text-base font-semibold text-zinc-900 hover:text-zinc-700">{{ config('app.name') }}</a>
            </div>
            <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3 text-sm" aria-label="Utama">
                <a href="{{ route('dashboard') }}" class="{{ $navActive }} {{ request()->routeIs('dashboard') ? $navOn : $navOff }}">Dasbor</a>

                @if ($bolehMaster)
                    <a href="{{ route('asesmen.index') }}" class="{{ $navActive }} {{ request()->routeIs('asesmen.*') ? $navOn : $navOff }}">Asesmen</a>

                    <details class="group mt-1" @if (request()->routeIs('master.*') || (auth()->user()->role === 'admin' && (request()->routeIs('peserta.impor-csv*') || request()->routeIs('master.log-aktivitas.*') || request()->routeIs('master.log-ai.*')))) open @endif>
                        <summary class="{{ $navActive }} cursor-pointer list-none text-zinc-700 marker:content-none [&::-webkit-details-marker]:hidden">
                            <span class="flex w-full items-center justify-between gap-2">
                                <span>Master data</span>
                                <svg class="size-4 shrink-0 text-zinc-400 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </span>
                        </summary>
                        <ul class="mt-1 space-y-0.5 border-l border-zinc-200 py-1 pl-3 ml-2">
                            <li>
                                <a href="{{ route('master.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.index') ? $navOn : $navOff }}">Ringkasan</a>
                            </li>
                            <li>
                                <a href="{{ route('master.kelompok-kompetensi.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.kelompok-kompetensi.*') ? $navOn : $navOff }}">Kelompok kompetensi</a>
                            </li>
                            <li>
                                <a href="{{ route('master.kompetensi.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.kompetensi.*') ? $navOn : $navOff }}">Kompetensi</a>
                            </li>
                            <li>
                                <a href="{{ route('master.tingkat-kompetensi.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.tingkat-kompetensi.*') ? $navOn : $navOff }}">Tingkat kompetensi</a>
                            </li>
                            <li>
                                <a href="{{ route('master.alat-penilaian.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.alat-penilaian.*') ? $navOn : $navOff }}">Alat penilaian</a>
                            </li>
                            <li>
                                <a href="{{ route('master.versi-matriks.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.versi-matriks*') ? $navOn : $navOff }}">Versi matriks</a>
                            </li>
                            <li>
                                <a href="{{ route('master.peserta.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.peserta.*') ? $navOn : $navOff }}">Peserta</a>
                            </li>
                            @if (auth()->user()->role === 'admin')
                                <li>
                                    <a href="{{ route('master.log-aktivitas.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.log-aktivitas.*') ? $navOn : $navOff }}">Log aktivitas</a>
                                </li>
                                <li>
                                    <a href="{{ route('master.log-ai.index') }}" class="{{ $navActive }} {{ request()->routeIs('master.log-ai.*') ? $navOn : $navOff }}">Log AI</a>
                                </li>
                                <li>
                                    <a href="{{ route('peserta.impor-csv') }}" class="{{ $navActive }} {{ request()->routeIs('peserta.impor-csv*') ? $navOn : $navOff }}">Impor peserta (CSV)</a>
                                </li>
                            @endif
                        </ul>
                    </details>
                @endif
            </nav>
            <div class="border-t border-zinc-100 p-3">
                <p class="truncate text-xs text-zinc-500" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</p>
                <p class="text-xs text-zinc-400">{{ auth()->user()->role }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-800 hover:bg-zinc-50">Keluar</button>
                </form>
            </div>
        </aside>
        <div class="flex min-h-0 min-w-0 flex-1 flex-col">
            <main class="flex-1 overflow-y-auto px-4 py-8 lg:px-8">
                <div class="mx-auto max-w-6xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
@endauth

@php
    $alertPayload = [
        'success' => session('status') ? ['text' => session('status')] : null,
        'error' => session('error') ? ['text' => session('error')] : null,
        'validation' => isset($errors) && $errors->any() ? $errors->toArray() : null,
    ];
@endphp
<script id="app-alerts" type="application/json">@json($alertPayload)</script>
</body>
</html>
