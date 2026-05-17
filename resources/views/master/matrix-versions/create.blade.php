@extends('layouts.app')

@section('title', 'Tambah versi matriks — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.versi-matriks.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Tambah versi matriks</h1>
    </div>
    <form method="POST" action="{{ route('master.versi-matriks.store') }}" class="max-w-lg space-y-4 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
        @csrf
        <div>
            <label for="kode_versi" class="block text-sm font-medium text-on-surface">Kode versi</label>
            <input type="text" name="kode_versi" id="kode_versi" value="{{ old('kode_versi') }}" required maxlength="64" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nama_versi" class="block text-sm font-medium text-on-surface">Nama versi</label>
            <input type="text" name="nama_versi" id="nama_versi" value="{{ old('nama_versi') }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="kunci_kamus" class="block text-sm font-medium text-on-surface">Kunci kamus</label>
            <input type="text" name="kunci_kamus" id="kunci_kamus" value="{{ old('kunci_kamus') }}" maxlength="64" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="catatan_konteks" class="block text-sm font-medium text-on-surface">Catatan konteks</label>
            <textarea name="catatan_konteks" id="catatan_konteks" rows="2" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">{{ old('catatan_konteks') }}</textarea>
        </div>
        <div>
            <label for="dipublikasikan_pada" class="block text-sm font-medium text-on-surface">Dipublikasikan pada (opsional)</label>
            <input type="datetime-local" name="dipublikasikan_pada" id="dipublikasikan_pada" value="{{ old('dipublikasikan_pada') }}" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', true))>
            <label for="aktif" class="text-sm text-on-surface">Aktif</label>
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="bawaan" value="0">
            <input type="checkbox" name="bawaan" id="bawaan" value="1" class="size-4 rounded border-outline-variant" @checked(old('bawaan'))>
            <label for="bawaan" class="text-sm text-on-surface">Bawaan</label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm text-white hover:opacity-90">Simpan</button>
            <a href="{{ route('master.versi-matriks.index') }}" class="rounded-md border border-outline-variant px-4 py-2 text-sm text-on-surface">Batal</a>
        </div>
    </form>
@endsection
