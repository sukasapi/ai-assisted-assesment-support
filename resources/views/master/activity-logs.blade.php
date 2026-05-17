@extends('layouts.app')

@section('title', 'Log aktivitas — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Log aktivitas</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Riwayat aksi penting (asesmen dibuat, sinkron pemetaan matriks, dll.).</p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Pengguna</th>
                    <th class="px-4 py-3">Aksi</th>
                    <th class="px-4 py-3">Subjek</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="whitespace-nowrap px-4 py-3 text-on-surface-variant">{{ $row->dibuat_pada?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->user?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-on-surface">{{ $row->aksi }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-xs text-on-surface-variant" title="{{ $row->subjek_tipe }} #{{ $row->subjek_id }}">
                            @if ($row->subjek_tipe)
                                <span class="font-mono">{{ class_basename($row->subjek_tipe) }}</span>
                                @if ($row->subjek_id)
                                    <span class="text-on-surface-variant/70">#{{ $row->subjek_id }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-on-surface-variant">{{ $row->alamat_ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-on-surface-variant">Belum ada entri log.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
