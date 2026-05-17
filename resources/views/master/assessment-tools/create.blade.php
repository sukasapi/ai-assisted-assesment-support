@extends('layouts.app')

@section('title', 'Tambah alat penilaian — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.alat-penilaian.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Tambah alat penilaian</h1>
    </div>
    <form method="POST" action="{{ route('master.alat-penilaian.store') }}" class="max-w-lg space-y-4 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
        @csrf
        <div>
            <label for="kode" class="block text-sm font-medium text-on-surface">Kode</label>
            <input type="text" name="kode" id="kode" value="{{ old('kode') }}" required maxlength="32" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nama" class="block text-sm font-medium text-on-surface">Nama</label>
            <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="deskripsi" class="block text-sm font-medium text-on-surface">Deskripsi</label>
            <textarea name="deskripsi" id="deskripsi" rows="3" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">{{ old('deskripsi') }}</textarea>
        </div>
        <div>
            <label for="urutan" class="block text-sm font-medium text-on-surface">Urutan</label>
            <input type="number" name="urutan" id="urutan" value="{{ old('urutan', 0) }}" min="0" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', true))>
            <label for="aktif" class="text-sm text-on-surface">Aktif</label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm text-white hover:opacity-90">Simpan</button>
            <a href="{{ route('master.alat-penilaian.index') }}" class="rounded-md border border-outline-variant px-4 py-2 text-sm text-on-surface">Batal</a>
        </div>
    </form>
@endsection
