@extends('layouts.app')

@section('title', 'Asesmen — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Asesmen">
        <x-slot:actions>
            @if (auth()->user()->role === 'konsultan' && $penugasanKonsultan)
                <form method="POST" action="{{ route('asesmen.token.clear') }}" class="inline">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Ganti token</x-ui.button>
                </form>
            @endif
            @can('create', App\Models\Assessment::class)
                <x-ui.button type="button" variant="primary" data-open-modal="modal-pilih-sesi-asesmen">
                    <span class="material-symbols-outlined text-lg">add</span>
                    Buat asesmen
                </x-ui.button>
            @endcan
            @if (auth()->user()->role === 'admin')
                <x-ui.button href="{{ route('sesi-asesmen.index') }}" variant="secondary">
                    <span class="material-symbols-outlined text-lg">event</span>
                    Sesi assessment
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($penugasanKonsultan ?? null)
        <p class="mb-4 text-sm text-on-surface-variant">
            Token aktif · {{ $penugasanKonsultan->assessments->count() }} asesmen ditugaskan
            @if ($penugasanKonsultan->session)
                · Sesi {{ $penugasanKonsultan->session->kode_sesi }}
            @endif
        </p>
    @endif

    <x-ui.table-toolbar placeholder="Cari peserta, sesi, atau matriks..." />

    <x-ui.data-table :colspan="6" empty="Belum ada asesmen yang dapat Anda akses.">
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">Peserta</th>
                <th class="px-4 py-3">Sesi</th>
                <th class="px-4 py-3">Matriks</th>
                <th class="px-4 py-3">Tujuan</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
        </x-slot:head>
        @forelse ($daftar as $row)
            <tr class="transition-colors hover:bg-surface-container-low/50">
                <td class="px-4 py-3 font-semibold text-on-surface">{{ $row->participant?->nama_lengkap ?? '—' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-on-surface-variant">{{ $row->session?->kode_sesi ?? '—' }}</td>
                <td class="px-4 py-3 font-mono text-sm text-on-surface-variant">{{ $row->matrixVersion?->kode_versi ?? '—' }}</td>
                <td class="px-4 py-3 text-on-surface-variant">
                    @if ($row->tujuan?->value === 'promosi')
                        Promosi
                    @elseif ($row->tujuan?->value === 'pemetaan_talenta')
                        Pemetaan talenta
                    @else
                        {{ $row->tujuan?->value ?? '—' }}
                    @endif
                </td>
                <td class="px-4 py-3">
                    @switch($row->status?->value)
                        @case('selesai_final')
                            <x-ui.badge tone="success">Selesai final</x-ui.badge>
                            @break
                        @case('berlangsung')
                            <x-ui.badge tone="primary">Berlangsung</x-ui.badge>
                            @break
                        @case('terintegrasi')
                            <x-ui.badge tone="primary">Terintegrasi</x-ui.badge>
                            @break
                        @default
                            <x-ui.badge tone="draft">Draf</x-ui.badge>
                    @endswitch
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('asesmen.show', $row) }}" class="text-sm font-semibold text-primary hover:underline">Detail</a>
                </td>
            </tr>
        @empty
        @endforelse
    </x-ui.data-table>

    <x-ui.table-pagination :paginator="$daftar" />

    @can('create', App\Models\Assessment::class)
        @include('assessments.partials.pilih-sesi-modal', ['daftarSesi' => $daftarSesi])
        @include('assessments.partials.asesmen-form-modals')
    @endcan
@endsection
