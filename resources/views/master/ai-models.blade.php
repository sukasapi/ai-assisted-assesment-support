@extends('layouts.app')

@section('title', 'Model AI — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Model AI (OpenRouter)</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Daftar model yang boleh dipilih asesor; menggantikan daftar dari .env bila ada data aktif di sini.</p>
        </div>
        @if (auth()->user()->role === 'admin')
            <a href="{{ route('master.model-ai.create') }}" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</a>
        @endif
    </div>
    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">ID OpenRouter</th>
                    <th class="px-4 py-3">Label</th>
                    <th class="px-4 py-3">Urutan</th>
                    <th class="px-4 py-3">Utama</th>
                    <th class="px-4 py-3">Aktif</th>
                    @if (auth()->user()->role === 'admin')
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-mono text-xs text-on-surface">{{ $row->id_model_openrouter }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->label }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->urutan }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->utama ? 'Ya' : '—' }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        @if (auth()->user()->role === 'admin')
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('master.model-ai.edit', $row) }}" class="text-on-surface-variant underline">Ubah</a>
                                <form action="{{ route('master.model-ai.destroy', $row) }}" method="POST" class="inline" onsubmit="return confirm('Hapus model ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-error underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-on-surface-variant">Belum ada model di database — daftar masih dari .env. Tambah model atau jalankan <code class="text-xs">php artisan db:seed --class=AiMasterSeeder</code>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
