@extends('layouts.app')

@section('title', 'Kompetensi — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Kompetensi</h1>
        </div>
        @if (auth()->user()->role === 'admin')
            <a href="{{ route('master.kompetensi.create') }}" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800">Tambah</a>
        @endif
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Kelompok</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Aktif</th>
                    @if (auth()->user()->role === 'admin')
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach ($items as $row)
                    <tr class="hover:bg-zinc-50/80">
                        <td class="px-4 py-3 font-mono text-zinc-800">{{ $row->kode_kompetensi }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $row->group?->kode ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-700">{{ $row->nama }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        @if (auth()->user()->role === 'admin')
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.kompetensi.edit', $row) }}" class="text-zinc-700 underline">Ubah</a>
                                <form action="{{ route('master.kompetensi.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Hapus kompetensi dan semua tingkat terkait?');">
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
