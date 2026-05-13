@extends('layouts.app')

@section('title', 'Tambah kompetensi — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.kompetensi.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Tambah kompetensi</h1>
    </div>
    <form method="POST" action="{{ route('master.kompetensi.store') }}" class="max-w-lg space-y-4 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label for="id_kelompok_kompetensi" class="block text-sm font-medium text-zinc-800">Kelompok</label>
            <select name="id_kelompok_kompetensi" id="id_kelompok_kompetensi" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
                @foreach ($kelompok as $g)
                    <option value="{{ $g->id }}" @selected(old('id_kelompok_kompetensi') == $g->id)>{{ $g->kode }} — {{ $g->nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="kode_kompetensi" class="block text-sm font-medium text-zinc-800">Kode kompetensi</label>
            <input type="text" name="kode_kompetensi" id="kode_kompetensi" value="{{ old('kode_kompetensi') }}" required maxlength="32" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nama" class="block text-sm font-medium text-zinc-800">Nama</label>
            <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="definisi" class="block text-sm font-medium text-zinc-800">Definisi</label>
            <textarea name="definisi" id="definisi" rows="3" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">{{ old('definisi') }}</textarea>
        </div>
        <div>
            <label for="tingkat_maksimum" class="block text-sm font-medium text-zinc-800">Tingkat maksimum</label>
            <input type="number" name="tingkat_maksimum" id="tingkat_maksimum" value="{{ old('tingkat_maksimum', 6) }}" min="1" max="20" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded border-zinc-300" @checked(old('aktif', true))>
            <label for="aktif" class="text-sm text-zinc-800">Aktif</label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm text-white hover:bg-zinc-800">Simpan</button>
            <a href="{{ route('master.kompetensi.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm text-zinc-800">Batal</a>
        </div>
    </form>
@endsection
