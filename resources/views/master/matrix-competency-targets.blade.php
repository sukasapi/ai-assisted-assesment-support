@extends('layouts.app')

@section('title', 'Target kompetensi — ' . $versiMatriks->kode_versi . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.versi-matriks.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Versi matriks</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Target kompetensi (profil jabatan)</h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Versi: <span class="font-mono text-on-surface">{{ $versiMatriks->kode_versi }}</span>
            <span class="text-on-surface-variant/70">·</span>
            {{ $versiMatriks->nama_versi }}
        </p>
        <p class="mt-2 max-w-3xl text-xs text-on-surface-variant">
            Tetapkan <strong>tingkat target</strong> per kompetensi sesuai jabatan tujuan. Bila diisi (1–6), nilai ini dipakai sebagai
            target GAP/Job Fit — menggantikan heuristik (Promosi = capaian+1, Talenta = 4). Kosong / 0 = pakai heuristik.
            Target dibekukan ke <strong>snapshot</strong> saat asesmen dibuat; mengubahnya tidak menggeser asesmen lama.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
    @endif

    @if ($kompetensi->isEmpty())
        <p class="text-sm text-on-surface-variant">Belum ada kompetensi yang dipetakan pada matriks ini. Atur dulu lewat «Pemetaan».</p>
    @else
        <form method="POST" action="{{ route('master.versi-matriks.target-kompetensi.store', $versiMatriks) }}" class="space-y-6">
            @csrf
            <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-container-low text-xs uppercase text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3">Kompetensi</th>
                            <th class="px-4 py-3">Kelompok</th>
                            <th class="px-4 py-3 text-center" style="width:160px">Target tingkat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/15">
                        @foreach ($kompetensi as $k)
                            @php $maks = max(1, (int) ($k->tingkat_maksimum ?? 6)); @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs font-semibold text-on-surface">{{ $k->kode_kompetensi }}</span>
                                    <span class="ml-1 text-on-surface">{{ $k->nama }}</span>
                                </td>
                                <td class="px-4 py-3 text-on-surface-variant">{{ $k->group?->nama ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if (auth()->user()?->isAdmin())
                                        <input
                                            type="number" min="0" max="{{ $maks }}" step="1"
                                            name="target[{{ $k->id }}]"
                                            value="{{ old('target.' . $k->id, $target[$k->id] ?? '') }}"
                                            placeholder="heuristik"
                                            class="w-24 rounded-md border border-outline-variant px-3 py-1.5 text-center text-sm"
                                        >
                                        <span class="ml-1 text-xs text-on-surface-variant/70">/ {{ $maks }}</span>
                                    @else
                                        <span class="font-bold text-on-surface">{{ $target[$k->id] ?? '—' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (auth()->user()?->isAdmin())
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-bold text-on-primary shadow-sm hover:opacity-90">Simpan target</button>
                    <a href="{{ route('master.versi-matriks.pemetaan.index', $versiMatriks) }}" class="text-sm text-on-surface-variant hover:text-primary">Buka Pemetaan</a>
                </div>
            @endif
        </form>
    @endif
@endsection
