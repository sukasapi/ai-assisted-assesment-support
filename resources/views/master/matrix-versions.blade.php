@extends('layouts.app')

@section('title', 'Versi matriks — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Versi matriks</h1>
        </div>
        @if (auth()->user()->role === 'admin')
            <a href="{{ route('master.versi-matriks.create') }}" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</a>
        @endif
    </div>
    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Aktif</th>
                    <th class="px-4 py-3">Bawaan</th>
                    <th class="px-4 py-3">Pemetaan</th>
                    @if (auth()->user()->role === 'admin')
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @foreach ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-mono text-on-surface">{{ $row->kode_versi }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->nama_versi }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->bawaan ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('master.versi-matriks.pemetaan.index', $row) }}" class="text-on-surface underline">Buka</a>
                        </td>
                        @if (auth()->user()->role === 'admin')
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.versi-matriks.edit', $row) }}" class="text-on-surface-variant underline">Ubah</a>
                                <form action="{{ route('master.versi-matriks.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Hapus versi matriks ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-error underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
