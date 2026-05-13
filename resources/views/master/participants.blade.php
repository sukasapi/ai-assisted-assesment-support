@extends('layouts.app')

@section('title', 'Peserta — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Peserta</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (auth()->user()->role === 'admin')
                <a href="{{ route('master.peserta.create') }}" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50">Tambah</a>
                <a href="{{ route('peserta.impor-csv') }}" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800">Impor CSV</a>
            @endif
        </div>
    </div>
    <div class="overflow-x-auto overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase text-zinc-500">
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
            <tbody class="divide-y divide-zinc-100">
                @foreach ($items as $row)
                    <tr class="hover:bg-zinc-50/80">
                        <td class="px-4 py-3 font-mono text-zinc-800">{{ $row->kode_peserta }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-900">{{ $row->nama_lengkap }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $row->alamat_surel ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $row->matrixVersion?->kode_versi ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        @if (auth()->user()->role === 'admin')
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.peserta.edit', $row) }}" class="text-zinc-700 underline">Ubah</a>
                                <form action="{{ route('master.peserta.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Hapus peserta ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-red-600 underline">Hapus</button>
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
