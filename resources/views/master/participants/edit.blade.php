@extends('layouts.app')

@section('title', 'Ubah peserta — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.peserta.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Ubah peserta</h1>
    </div>
    <form method="POST" action="{{ route('master.peserta.update', $item) }}" class="max-w-lg space-y-4 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="kode_peserta" class="block text-sm font-medium text-zinc-800">Kode peserta</label>
            <input type="text" name="kode_peserta" id="kode_peserta" value="{{ old('kode_peserta', $item->kode_peserta) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="nama_lengkap" class="block text-sm font-medium text-zinc-800">Nama lengkap</label>
            <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', $item->nama_lengkap) }}" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="alamat_surel" class="block text-sm font-medium text-zinc-800">Alamat surel</label>
            <input type="email" name="alamat_surel" id="alamat_surel" value="{{ old('alamat_surel', $item->alamat_surel) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="jabatan" class="block text-sm font-medium text-zinc-800">Jabatan</label>
            <input type="text" name="jabatan" id="jabatan" value="{{ old('jabatan', $item->jabatan) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="pendidikan" class="block text-sm font-medium text-zinc-800">Pendidikan</label>
            <input type="text" name="pendidikan" id="pendidikan" value="{{ old('pendidikan', $item->pendidikan) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="tanggal_lahir" class="block text-sm font-medium text-zinc-800">Tanggal lahir</label>
            <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="{{ old('tanggal_lahir', $item->tanggal_lahir?->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="id_versi_matriks" class="block text-sm font-medium text-zinc-800">Versi matriks (opsional)</label>
            <select name="id_versi_matriks" id="id_versi_matriks" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">
                <option value="">— tidak ada —</option>
                @foreach ($versiMatriks as $v)
                    <option value="{{ $v->id }}" @selected(old('id_versi_matriks', $item->id_versi_matriks) == $v->id)>{{ $v->kode_versi }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="catatan" class="block text-sm font-medium text-zinc-800">Catatan</label>
            <textarea name="catatan" id="catatan" rows="2" class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm">{{ old('catatan', $item->catatan) }}</textarea>
        </div>
        <div class="flex items-center gap-2">
            <input type="hidden" name="aktif" value="0">
            <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded border-zinc-300" @checked(old('aktif', $item->aktif))>
            <label for="aktif" class="text-sm text-zinc-800">Aktif</label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm text-white hover:bg-zinc-800">Simpan</button>
            <a href="{{ route('master.peserta.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm text-zinc-800">Batal</a>
        </div>
    </form>
@endsection
