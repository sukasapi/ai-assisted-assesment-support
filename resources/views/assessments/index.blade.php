@extends('layouts.app')

@section('title', 'Asesmen — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Asesmen">
        <x-slot:actions>
            <x-ui.button href="{{ route('asesmen.create') }}" variant="primary">
                <span class="material-symbols-outlined text-lg">add</span>
                Buat asesmen
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table :colspan="5" empty="Belum ada asesmen. Buat dari tombol di atas.">
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">Peserta</th>
                <th class="px-4 py-3">Matriks</th>
                <th class="px-4 py-3">Tujuan</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
        </x-slot:head>
        @forelse ($daftar as $row)
            <tr class="transition-colors hover:bg-surface-container-low/50">
                <td class="px-4 py-3 font-semibold text-on-surface">{{ $row->participant?->nama_lengkap ?? '—' }}</td>
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

    <div class="mt-4">
        {{ $daftar->links() }}
    </div>
@endsection
