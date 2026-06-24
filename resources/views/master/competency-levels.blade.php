@extends('layouts.app')

@section('title', 'Tingkat kompetensi — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Tingkat kompetensi</h1>
        </div>
        @if (auth()->user()?->isAdmin())
            <a href="{{ route('master.tingkat-kompetensi.create') }}" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</a>
        @endif
    </div>
    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Kompetensi</th>
                    <th class="px-4 py-3">Tingkat</th>
                    <th class="px-4 py-3">Indikator</th>
                    @if (auth()->user()?->isAdmin())
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @foreach ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-mono text-on-surface">{{ $row->competency?->kode_kompetensi ?? '—' }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->tingkat }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ \Illuminate\Support\Str::limit($row->indikator_perilaku, 120) }}</td>
                        @if (auth()->user()?->isAdmin())
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.tingkat-kompetensi.edit', $row) }}" class="text-on-surface-variant underline">Ubah</a>
                                <form
                                    action="{{ route('master.tingkat-kompetensi.destroy', $row) }}"
                                    method="POST"
                                    class="inline"
                                    data-swal-confirm="Tingkat kompetensi yang dihapus tidak dapat dipulihkan."
                                    data-swal-confirm-title="Hapus tingkat?"
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
