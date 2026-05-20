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

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Total asesmen</p>
            <p class="mt-2 font-display text-3xl font-bold text-primary">{{ $totalAsesmen }}</p>
        </div>
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Draf</p>
            <p class="mt-2 font-display text-3xl font-bold text-on-surface">{{ $draf }}</p>
        </div>
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Terintegrasi (pratinjau)</p>
            <p class="mt-2 font-display text-3xl font-bold text-secondary">{{ $terintegrasi }}</p>
        </div>
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Selesai final</p>
            <p class="mt-2 font-display text-3xl font-bold text-emerald-700">{{ $selesaiFinal }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Job Fit pratinjau (rata-rata)</p>
            <p class="mt-2 font-display text-3xl font-bold text-primary">
                {{ $rataJobFit !== null ? number_format($rataJobFit, 1).'%' : '—' }}
            </p>
            <p class="mt-2 text-xs text-on-surface-variant">Hanya asesmen yang sudah pernah dihitung pratinjau integrasinya.</p>
        </div>
        <div class="card-depth p-6">
            <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Belum ada pratinjau integrasi</p>
            <p class="mt-2 font-display text-3xl font-bold text-amber-700">{{ $asesmenPerluIntegrasi }}</p>
            <p class="mt-2 text-xs text-on-surface-variant">Asesmen aktif (draf/berlangsung) tanpa hitungan pratinjau.</p>
        </div>
    </div>

    @if ($gapTerbesar->isNotEmpty())
        <section class="mt-6 card-depth p-6">
            <h2 class="font-display text-sm font-bold uppercase tracking-wide text-on-surface-variant">GAP terbesar (pratinjau)</h2>
            <ul class="mt-4 space-y-3">
                @foreach ($gapTerbesar as $baris)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-outline-variant/30 bg-surface-container-low px-4 py-3 text-sm">
                        <span>
                            <strong>{{ $baris->competency?->kode_kompetensi }}</strong>
                            — {{ $baris->assessment?->participant?->nama_lengkap ?? 'Peserta' }}
                        </span>
                        <span class="font-mono font-bold text-rose-600">+{{ $baris->selisih_gap }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="mt-8 card-depth p-6">
        <h2 class="font-display text-sm font-bold uppercase tracking-wide text-on-surface-variant">Akses cepat</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <x-ui.button href="{{ route('asesmen.index') }}" variant="secondary">Daftar asesmen</x-ui.button>
            @if (in_array(auth()->user()->role, ['admin', 'konsultan'], true))
                <x-ui.button href="{{ route('master.index') }}" variant="secondary">Master data</x-ui.button>
            @endif
        </div>
    </div>
@endsection
