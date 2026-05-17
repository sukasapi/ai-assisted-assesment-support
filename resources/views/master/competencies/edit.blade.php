@extends('layouts.app')

@section('title', 'Ubah kompetensi — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.kompetensi.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Ubah kompetensi</h1>
    </div>
    <form method="POST" action="{{ route('master.kompetensi.update', $item) }}" class="max-w-lg space-y-4 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="id_kelompok_kompetensi" class="block text-sm font-medium text-on-surface">Kelompok</label>
            <select name="id_kelompok_kompetensi" id="id_kelompok_kompetensi" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
                @foreach ($kelompok as $g)
                    <option value="{{ $g->id }}" @selected(old('id_kelompok_kompetensi', $item->id_kelompok_kompetensi) == $g->id)>{{ $g->kode }} — {{ $g->nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="kode_kompetensi" class="block text-sm font-medium text-on-surface">Kode kompetensi</label>
            <input type="text" name="kode_kompetensi" id="kode_kompetensi" value="{{ old('kode_kompetensi', $item->kode_kompetensi) }}" required maxlength="32" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nama" class="block text-sm font-medium text-on-surface">Nama</label>
            <input type="text" name="nama" id="nama" value="{{ old('nama', $item->nama) }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="definisi" class="block text-sm font-medium text-on-surface">Definisi</label>
            <textarea name="definisi" id="definisi" rows="3" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">{{ old('definisi', $item->definisi) }}</textarea>
        </div>
        <div>
            <label for="tingkat_maksimum" class="block text-sm font-medium text-on-surface">Tingkat maksimum</label>
            <input type="number" name="tingkat_maksimum" id="tingkat_maksimum" value="{{ old('tingkat_maksimum', $item->tingkat_maksimum) }}" min="1" max="20" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', $item->aktif))>
            <label for="aktif" class="text-sm text-on-surface">Aktif</label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm text-white hover:opacity-90">Simpan</button>
            <a href="{{ route('master.kompetensi.index') }}" class="rounded-md border border-outline-variant px-4 py-2 text-sm text-on-surface">Batal</a>
        </div>
    </form>
@endsection
