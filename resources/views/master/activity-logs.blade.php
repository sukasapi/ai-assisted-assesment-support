@extends('layouts.app')

@section('title', 'Log aktivitas — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Master data</a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Log aktivitas</h1>
        <p class="mt-1 text-sm text-zinc-600">Riwayat aksi penting (asesmen dibuat, sinkron pemetaan matriks, dll.).</p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Pengguna</th>
                    <th class="px-4 py-3">Aksi</th>
                    <th class="px-4 py-3">Subjek</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($items as $row)
                    <tr class="hover:bg-zinc-50/80">
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $row->dibuat_pada?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-700">{{ $row->user?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-800">{{ $row->aksi }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-xs text-zinc-600" title="{{ $row->subjek_tipe }} #{{ $row->subjek_id }}">
                            @if ($row->subjek_tipe)
                                <span class="font-mono">{{ class_basename($row->subjek_tipe) }}</span>
                                @if ($row->subjek_id)
                                    <span class="text-zinc-400">#{{ $row->subjek_id }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-500">{{ $row->alamat_ip ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada entri log.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
