@extends('layouts.app')

@section('title', 'Template prompt AI — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Template prompt AI</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Kerangka analisis (STAR, dll.) yang dapat dipilih per asesmen; berlaku untuk semua alat.</p>
        </div>
        @if (auth()->user()->role === 'admin')
            <a href="{{ route('master.template-prompt-ai.create') }}" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</a>
        @endif
    </div>
    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Alat (default)</th>
                    <th class="px-4 py-3">Urutan</th>
                    <th class="px-4 py-3">Aktif</th>
                    @if (auth()->user()->role === 'admin')
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-mono text-on-surface">{{ $row->kode }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">
                            {{ $row->nama }}
                            @if ($row->deskripsi)
                                <span class="mt-0.5 block text-xs text-on-surface-variant/80">{{ \Illuminate\Support\Str::limit($row->deskripsi, 80) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-on-surface-variant">
                            @if ($row->tools->isEmpty())
                                <span class="italic">Semua alat (via asesmen)</span>
                            @else
                                {{ $row->tools->pluck('kode')->join(', ') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->urutan }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        @if (auth()->user()->role === 'admin')
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.template-prompt-ai.edit', $row) }}" class="text-on-surface-variant underline">Ubah</a>
                                <form action="{{ route('master.template-prompt-ai.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Hapus template ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-error underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-on-surface-variant">Belum ada template. Jalankan seeder atau tambah dari admin.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
