@extends('layouts.app')

@section('title', 'Tambah kelompok kompetensi — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.kelompok-kompetensi.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Tambah kelompok kompetensi</h1>
    </div>
    <form method="POST" action="{{ route('master.kelompok-kompetensi.store') }}" class="max-w-md space-y-4 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
        @csrf
        <div>
            <label for="kode" class="block text-sm font-medium text-on-surface">Kode</label>
            <input type="text" name="kode" id="kode" value="{{ old('kode') }}" required maxlength="16" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nama" class="block text-sm font-medium text-on-surface">Nama</label>
            <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm text-white hover:opacity-90">Simpan</button>
            <a href="{{ route('master.kelompok-kompetensi.index') }}" class="rounded-md border border-outline-variant px-4 py-2 text-sm text-on-surface">Batal</a>
        </div>
    </form>
@endsection
