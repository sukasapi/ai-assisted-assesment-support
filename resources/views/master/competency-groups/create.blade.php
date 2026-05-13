@extends('layouts.app')

@section('title', 'Tambah kelompok kompetensi — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.kelompok-kompetensi.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Tambah kelompok kompetensi</h1>
    </div>
    <form method="POST" action="{{ route('master.kelompok-kompetensi.store') }}" class="max-w-md space-y-4 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        <div>
            <label for="kode" class="block text-sm font-medium text-zinc-800">Kode</label>
            <input type="text" name="kode" id="kode" value="{{ old('kode') }}" required maxlength="16" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nama" class="block text-sm font-medium text-zinc-800">Nama</label>
            <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm text-white hover:bg-zinc-800">Simpan</button>
            <a href="{{ route('master.kelompok-kompetensi.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm text-zinc-800">Batal</a>
        </div>
    </form>
@endsection
