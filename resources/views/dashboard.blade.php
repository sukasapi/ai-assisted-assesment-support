@extends('layouts.app')

@section('title', 'Dasbor — '.config('app.name'))

@section('content')
    <x-ui.page-header title="Dasbor">
        <x-slot:description>
            Anda masuk sebagai <strong>{{ auth()->user()->name }}</strong> (peran: {{ auth()->user()->role }}).
        </x-slot:description>
        <x-slot:actions>
            @if (in_array(auth()->user()->role, ['admin', 'konsultan'], true))
                <x-ui.button href="{{ route('asesmen.create') }}" variant="primary">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Buat asesmen
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Total asesmen</p>
            <p class="mt-2 font-display text-3xl font-bold text-primary">{{ $totalAsesmen }}</p>
        </div>
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Draf</p>
            <p class="mt-2 font-display text-3xl font-bold text-on-surface">{{ $draf }}</p>
        </div>
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Selesai final</p>
            <p class="mt-2 font-display text-3xl font-bold text-emerald-700">{{ $selesaiFinal }}</p>
        </div>
    </div>

    <div class="mt-8 card-depth p-6">
        <h2 class="font-display text-sm font-bold uppercase tracking-wide text-on-surface-variant">Akses cepat</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <x-ui.button href="{{ route('asesmen.index') }}" variant="secondary">Daftar asesmen</x-ui.button>
            @if (in_array(auth()->user()->role, ['admin', 'konsultan'], true))
                <x-ui.button href="{{ route('master.index') }}" variant="secondary">Master data</x-ui.button>
            @endif
        </div>
        <p class="mt-4 text-sm text-on-surface-variant">Analitik GAP & Job Fit akan tersedia pada fase integrasi berikutnya.</p>
    </div>
@endsection
