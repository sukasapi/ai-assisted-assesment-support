@extends('layouts.app')

@section('title', 'Dasbor — '.config('app.name'))

@section('content')
    @php
        $statusBadge = fn (?string $s): array => match ($s) {
            'selesai_final' => ['Final', 'border-emerald-200 bg-emerald-50 text-emerald-800'],
            'terintegrasi' => ['Terintegrasi', 'border-sky-200 bg-sky-50 text-sky-800'],
            'berlangsung' => ['Berlangsung', 'border-amber-200 bg-amber-50 text-amber-800'],
            default => ['Draf', 'border-outline-variant/40 bg-surface-container text-on-surface-variant'],
        };
    @endphp

    <x-ui.page-header title="Dasbor">
        <x-slot:description>
            Anda masuk sebagai <strong>{{ auth()->user()?->nama }}</strong> ({{ auth()->user()?->peran }}).
        </x-slot:description>
        <x-slot:actions>
            @if (($peran ?? 'admin') === 'admin')
                <x-ui.button href="{{ route('sesi-asesmen.index') }}" variant="primary">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Buat asesmen (via sesi)
                </x-ui.button>
            @else
                <x-ui.button href="{{ route('asesmen.index') }}" variant="primary">
                    <span class="material-symbols-outlined text-lg">assignment</span>
                    Buka asesmen saya
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if (($peran ?? 'admin') === 'konsultan')
        {{-- ============ DASBOR KONSULTAN ============ --}}
        @if ($totalAsesmen === 0)
            <div class="card-depth flex flex-col items-center gap-3 p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-on-surface-variant/40">inbox</span>
                <p class="text-lg font-semibold text-on-surface">Belum ada asesmen ditugaskan</p>
                <p class="max-w-md text-sm text-on-surface-variant">Anda belum punya penugasan aktif. Hubungi admin untuk mendapatkan token penugasan, lalu buka menu Asesmen.</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card-depth p-6">
                    <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Asesmen saya</p>
                    <p class="mt-2 font-display text-3xl font-bold text-primary">{{ $totalAsesmen }}</p>
                    <p class="mt-1 text-xs text-on-surface-variant">dari {{ $jumlahPenugasan }} penugasan aktif</p>
                </div>
                <div class="card-depth p-6">
                    <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Perlu dinilai</p>
                    <p class="mt-2 font-display text-3xl font-bold text-amber-700">{{ $belumDinilai }}</p>
                    <p class="mt-1 text-xs text-on-surface-variant">belum ada pratinjau integrasi</p>
                </div>
                <div class="card-depth p-6">
                    <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Terintegrasi</p>
                    <p class="mt-2 font-display text-3xl font-bold text-secondary">{{ $terintegrasi }}</p>
                    <p class="mt-1 text-xs text-on-surface-variant">siap ditinjau / difinalisasi</p>
                </div>
                <div class="card-depth p-6">
                    <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Selesai final</p>
                    <p class="mt-2 font-display text-3xl font-bold text-emerald-700">{{ $selesaiFinal }}</p>
                </div>
            </div>

            <section class="mt-6 card-depth overflow-hidden">
                <div class="border-b border-outline-variant/30 px-6 py-4">
                    <h2 class="text-section-header uppercase text-on-surface-variant">Perlu tindakan</h2>
                </div>
                @if ($perluTindakan->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-on-surface-variant">Semua asesmen Anda sudah final. 🎉</p>
                @else
                    <table class="min-w-full text-sm">
                        <thead class="bg-surface-container-low text-xs uppercase text-on-surface-variant">
                            <tr>
                                <th class="px-6 py-3 text-left">Peserta</th>
                                <th class="px-6 py-3 text-left">Sesi</th>
                                <th class="px-6 py-3 text-left">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            @foreach ($perluTindakan as $a)
                                @php [$stLabel, $stKelas] = $statusBadge($a->status?->value); @endphp
                                <tr>
                                    <td class="px-6 py-4 font-medium text-on-surface">{{ $a->participant?->nama_lengkap ?? '—' }}</td>
                                    <td class="px-6 py-4 text-on-surface-variant">{{ $a->session?->nama ?? '—' }}</td>
                                    <td class="px-6 py-4"><span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $stKelas }}">{{ $stLabel }}</span></td>
                                    <td class="px-6 py-4 text-right"><a href="{{ route('asesmen.show', $a) }}" class="font-semibold text-primary hover:underline">Kerjakan</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endif
    @else
        {{-- ============ DASBOR ADMIN ============ --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card-depth p-6">
                <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Total asesmen</p>
                <p class="mt-2 font-display text-3xl font-bold text-primary">{{ $totalAsesmen }}</p>
                <p class="mt-1 text-xs text-on-surface-variant">{{ $totalSesi }} sesi assessment</p>
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
                <p class="mt-2 font-display text-3xl font-bold text-primary">{{ $rataJobFit !== null ? number_format($rataJobFit, 1).'%' : '—' }}</p>
                <p class="mt-2 text-xs text-on-surface-variant">Hanya asesmen yang sudah pernah dihitung pratinjau.</p>
            </div>
            <div class="card-depth p-6">
                <p class="text-xs font-bold uppercase tracking-wide text-on-surface-variant">Belum ada pratinjau integrasi</p>
                <p class="mt-2 font-display text-3xl font-bold text-amber-700">{{ $asesmenPerluIntegrasi }}</p>
                <p class="mt-2 text-xs text-on-surface-variant">Asesmen aktif tanpa hitungan pratinjau.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="card-depth overflow-hidden">
                <div class="border-b border-outline-variant/30 px-6 py-4">
                    <h2 class="text-section-header uppercase text-on-surface-variant">Siap ditinjau / difinalisasi</h2>
                </div>
                @if ($siapFinalisasi->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-on-surface-variant">Tidak ada asesmen terintegrasi yang menunggu.</p>
                @else
                    <ul class="divide-y divide-outline-variant/15">
                        @foreach ($siapFinalisasi as $a)
                            <li class="flex items-center justify-between gap-2 px-6 py-3 text-sm">
                                <span class="min-w-0">
                                    <span class="font-medium text-on-surface">{{ $a->participant?->nama_lengkap ?? '—' }}</span>
                                    <span class="block text-xs text-on-surface-variant">{{ $a->session?->nama ?? '—' }}</span>
                                </span>
                                <a href="{{ route('asesmen.show', $a) }}" class="shrink-0 font-semibold text-primary hover:underline">Tinjau</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card-depth overflow-hidden">
                <div class="border-b border-outline-variant/30 px-6 py-4">
                    <h2 class="text-section-header uppercase text-on-surface-variant">GAP terbesar (pratinjau)</h2>
                </div>
                @if ($gapTerbesar->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-on-surface-variant">Belum ada data GAP.</p>
                @else
                    <ul class="divide-y divide-outline-variant/15">
                        @foreach ($gapTerbesar as $baris)
                            <li class="flex items-center justify-between gap-2 px-6 py-3 text-sm">
                                <span><strong>{{ $baris->competency?->kode_kompetensi }}</strong> — {{ $baris->assessment?->participant?->nama_lengkap ?? 'Peserta' }}</span>
                                <span class="font-mono font-bold text-rose-600">+{{ $baris->selisih_gap }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        @if ($sesiTerbaru->isNotEmpty())
            <section class="mt-6 card-depth overflow-hidden">
                <div class="border-b border-outline-variant/30 px-6 py-4">
                    <h2 class="text-section-header uppercase text-on-surface-variant">Sesi terbaru</h2>
                </div>
                <ul class="divide-y divide-outline-variant/15">
                    @foreach ($sesiTerbaru as $s)
                        <li class="flex items-center justify-between gap-2 px-6 py-3 text-sm">
                            <span class="min-w-0">
                                <span class="font-medium text-on-surface">{{ $s->nama }}</span>
                                <span class="ml-2 font-mono text-xs text-on-surface-variant">{{ $s->kode_sesi }}</span>
                            </span>
                            <span class="flex items-center gap-4">
                                <span class="text-xs text-on-surface-variant">{{ $s->assessments_count }} asesmen</span>
                                <a href="{{ route('sesi-asesmen.show', $s) }}" class="font-semibold text-primary hover:underline">Buka</a>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endif

    <div class="mt-8 card-depth p-6">
        <h2 class="font-display text-sm font-bold uppercase tracking-wide text-on-surface-variant">Akses cepat</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <x-ui.button href="{{ route('asesmen.index') }}" variant="secondary">Daftar asesmen</x-ui.button>
            @if (($peran ?? 'admin') === 'admin')
                <x-ui.button href="{{ route('sesi-asesmen.index') }}" variant="secondary">Sesi assessment</x-ui.button>
            @endif
            @if (auth()->user()?->hasPeran('admin', 'konsultan'))
                <x-ui.button href="{{ route('master.index') }}" variant="secondary">Master data</x-ui.button>
            @endif
        </div>
    </div>
@endsection
