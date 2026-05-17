@extends('layouts.app')

@section('title', 'Peserta — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Peserta</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (auth()->user()->role === 'admin')
                <a href="{{ route('master.peserta.create') }}" class="rounded-md border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm font-medium text-on-surface hover:bg-surface-container-low">Tambah</a>
                <a href="{{ route('peserta.impor-csv') }}" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Impor CSV</a>
            @endif
        </div>
    </div>
    <div class="overflow-x-auto overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Surel</th>
                    <th class="px-4 py-3">Matriks</th>
                    <th class="px-4 py-3">Aktif</th>
                    @if (auth()->user()->role === 'admin')
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @foreach ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-mono text-on-surface">{{ $row->kode_peserta }}</td>
                        <td class="px-4 py-3 font-medium text-on-surface">{{ $row->nama_lengkap }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->alamat_surel ?? '—' }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->matrixVersion?->kode_versi ?? '—' }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        @if (auth()->user()->role === 'admin')
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.peserta.edit', $row) }}" class="text-on-surface-variant underline">Ubah</a>
                                <form action="{{ route('master.peserta.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Hapus peserta ini?');">
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
