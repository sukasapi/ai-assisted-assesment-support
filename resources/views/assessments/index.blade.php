@extends('layouts.app')

@section('title', 'Asesmen — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-zinc-900">Asesmen</h1>
        <a href="{{ route('asesmen.create') }}" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">Buat asesmen</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Peserta</th>
                    <th class="px-4 py-3">Matriks</th>
                    <th class="px-4 py-3">Tujuan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($daftar as $row)
                    <tr class="hover:bg-zinc-50/80">
                        <td class="px-4 py-3 font-medium text-zinc-900">{{ $row->participant?->nama_lengkap ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $row->matrixVersion?->kode_versi ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">
                            @if ($row->tujuan?->value === 'promosi')
                                Promosi
                            @elseif ($row->tujuan?->value === 'pemetaan_talenta')
                                Pemetaan talenta
                            @else
                                {{ $row->tujuan?->value ?? '—' }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600">
                            @switch($row->status?->value)
                                @case('draf')
                                    Draf
                                    @break
                                @case('berlangsung')
                                    Berlangsung
                                    @break
                                @case('terintegrasi')
                                    Terintegrasi
                                    @break
                                @case('selesai_final')
                                    Selesai (final)
                                    @break
                                @default
                                    {{ $row->status?->value ?? '—' }}
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('asesmen.show', $row) }}" class="font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-600">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">Belum ada asesmen. Buat dari tombol di atas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $daftar->links() }}
    </div>
@endsection
