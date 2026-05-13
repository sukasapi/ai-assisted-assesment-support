@extends('layouts.app')

@section('title', 'Ubah versi matriks — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.versi-matriks.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Ubah versi matriks</h1>
    </div>
    <form method="POST" action="{{ route('master.versi-matriks.update', $item) }}" class="max-w-lg space-y-4 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="kode_versi" class="block text-sm font-medium text-zinc-800">Kode versi</label>
            <input type="text" name="kode_versi" id="kode_versi" value="{{ old('kode_versi', $item->kode_versi) }}" required maxlength="64" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nama_versi" class="block text-sm font-medium text-zinc-800">Nama versi</label>
            <input type="text" name="nama_versi" id="nama_versi" value="{{ old('nama_versi', $item->nama_versi) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="kunci_kamus" class="block text-sm font-medium text-zinc-800">Kunci kamus</label>
            <input type="text" name="kunci_kamus" id="kunci_kamus" value="{{ old('kunci_kamus', $item->kunci_kamus) }}" maxlength="64" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="catatan_konteks" class="block text-sm font-medium text-zinc-800">Catatan konteks</label>
            <textarea name="catatan_konteks" id="catatan_konteks" rows="2" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">{{ old('catatan_konteks', $item->catatan_konteks) }}</textarea>
        </div>
        <div>
            <label for="dipublikasikan_pada" class="block text-sm font-medium text-zinc-800">Dipublikasikan pada (opsional)</label>
            <input type="datetime-local" name="dipublikasikan_pada" id="dipublikasikan_pada" value="{{ old('dipublikasikan_pada', $item->dipublikasikan_pada?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded border-zinc-300" @checked(old('aktif', $item->aktif))>
            <label for="aktif" class="text-sm text-zinc-800">Aktif</label>
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="bawaan" value="0">
            <input type="checkbox" name="bawaan" id="bawaan" value="1" class="size-4 rounded border-zinc-300" @checked(old('bawaan', $item->bawaan))>
            <label for="bawaan" class="text-sm text-zinc-800">Bawaan</label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm text-white hover:bg-zinc-800">Simpan</button>
            <a href="{{ route('master.versi-matriks.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm text-zinc-800">Batal</a>
        </div>
    </form>
@endsection
