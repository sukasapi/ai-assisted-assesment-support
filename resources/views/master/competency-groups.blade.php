@extends('layouts.app')

@section('title', 'Kelompok kompetensi — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Kelompok kompetensi</h1>
        </div>
        @if (auth()->user()->role === 'admin')
            <a href="{{ route('master.kelompok-kompetensi.create') }}" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</a>
        @endif
    </div>
    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    @if (auth()->user()->role === 'admin')
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @foreach ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-mono text-on-surface">{{ $row->kode }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->nama }}</td>
                        @if (auth()->user()->role === 'admin')
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.kelompok-kompetensi.edit', $row) }}" class="text-on-surface-variant underline">Ubah</a>
                                <form
                                    action="{{ route('master.kelompok-kompetensi.destroy', $row) }}"
                                    method="POST"
                                    class="inline"
                                    data-swal-confirm="Kelompok kompetensi yang dihapus tidak dapat dipulihkan."
                                    data-swal-confirm-title="Hapus kelompok?"
                                    data-swal-confirm-yes="Ya, hapus"
                                    data-swal-confirm-danger="1"
                                >
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
