@extends('layouts.app')

@section('title', 'Ubah tingkat kompetensi — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.tingkat-kompetensi.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Ubah tingkat kompetensi</h1>
    </div>
    <form method="POST" action="{{ route('master.tingkat-kompetensi.update', $item) }}" class="max-w-lg space-y-4 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label for="id_kompetensi" class="block text-sm font-medium text-on-surface">Kompetensi</label>
            <select name="id_kompetensi" id="id_kompetensi" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
                @foreach ($kompetensi as $c)
                    <option value="{{ $c->id }}" @selected(old('id_kompetensi', $item->id_kompetensi) == $c->id)>{{ $c->kode_kompetensi }} — {{ $c->nama }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tingkat" class="block text-sm font-medium text-on-surface">Tingkat (angka)</label>
            <input type="number" name="tingkat" id="tingkat" value="{{ old('tingkat', $item->tingkat) }}" min="1" max="20" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="indikator_perilaku" class="block text-sm font-medium text-on-surface">Indikator perilaku</label>
            <textarea name="indikator_perilaku" id="indikator_perilaku" rows="4" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">{{ old('indikator_perilaku', $item->indikator_perilaku) }}</textarea>
        </div>
        <div>
            <label for="etiket" class="block text-sm font-medium text-on-surface">Etiket (opsional)</label>
            <input type="text" name="etiket" id="etiket" value="{{ old('etiket', $item->etiket) }}" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
        </div>
        <div>
            <label for="deskripsi" class="block text-sm font-medium text-on-surface">Deskripsi (opsional)</label>
            <textarea name="deskripsi" id="deskripsi" rows="2" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">{{ old('deskripsi', $item->deskripsi) }}</textarea>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm text-white hover:opacity-90">Simpan</button>
            <a href="{{ route('master.tingkat-kompetensi.index') }}" class="rounded-md border border-outline-variant px-4 py-2 text-sm text-on-surface">Batal</a>
        </div>
    </form>
@endsection
