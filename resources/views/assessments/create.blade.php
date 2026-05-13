@extends('layouts.app')

@section('title', 'Buat asesmen — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('asesmen.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Kembali ke daftar</a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Buat asesmen</h1>
        <p class="mt-1 text-sm text-zinc-600">Pilih peserta, versi matriks, tujuan, dan asesor. Alat penilaian diisi otomatis dari preset.</p>
    </div>

    <form method="POST" action="{{ route('asesmen.store') }}" class="max-w-xl space-y-5 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="id_peserta" class="block text-sm font-medium text-zinc-800">Peserta</label>
            <select name="id_peserta" id="id_peserta" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
                <option value="">— pilih —</option>
                @foreach ($peserta as $p)
                    <option value="{{ $p->id }}" @selected(old('id_peserta') == $p->id)>
                        {{ $p->nama_lengkap }} ({{ $p->kode_peserta }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="id_versi_matriks" class="block text-sm font-medium text-zinc-800">Versi matriks</label>
            <select name="id_versi_matriks" id="id_versi_matriks" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
                <option value="">— pilih —</option>
                @foreach ($versiMatriks as $v)
                    <option value="{{ $v->id }}" @selected(old('id_versi_matriks') == $v->id)>{{ $v->kode_versi }} — {{ $v->nama_versi }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="tujuan" class="block text-sm font-medium text-zinc-800">Tujuan</label>
            <select name="tujuan" id="tujuan" required class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500">
                <option value="promosi" @selected(old('tujuan') === 'promosi')>Promosi</option>
                <option value="pemetaan_talenta" @selected(old('tujuan', 'pemetaan_talenta') === 'pemetaan_talenta')>Pemetaan talenta</option>
            </select>
        </div>

        <fieldset>
            <legend class="block text-sm font-medium text-zinc-800">Cara mengumpulkan bukti</legend>
            <p class="mt-0.5 text-xs text-zinc-500">Menentukan formulir mana yang aktif di halaman detail asesmen (dapat diubah nanti).</p>
            <div class="mt-3 space-y-3">
                <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 has-[:checked]:border-zinc-900 has-[:checked]:bg-white">
                    <input type="radio" name="metode_koleksi_bukti" value="manual" class="mt-1 size-4 border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked(old('metode_koleksi_bukti', 'manual') === 'manual')>
                    <span>
                        <span class="block text-sm font-medium text-zinc-900">Manual — bukti per kompetensi</span>
                        <span class="mt-0.5 block text-xs text-zinc-600">Input bukti penilaian per alat dan kompetensi; analisis AI per baris bukti.</span>
                    </span>
                </label>
                <label class="flex cursor-pointer gap-3 rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 has-[:checked]:border-violet-600 has-[:checked]:bg-violet-50/50">
                    <input type="radio" name="metode_koleksi_bukti" value="payload_alat" class="mt-1 size-4 border-zinc-300 text-violet-700 focus:ring-violet-500" @checked(old('metode_koleksi_bukti') === 'payload_alat')>
                    <span>
                        <span class="block text-sm font-medium text-zinc-900">Otomatis — payload alat + AI bulk</span>
                        <span class="mt-0.5 block text-xs text-zinc-600">Unggah teks muatan per alat, lalu jalankan pemetaan AI ke banyak kompetensi (hasil wajib direview).</span>
                    </span>
                </label>
            </div>
        </fieldset>

        <div class="flex items-center gap-2">
            <input type="hidden" name="tanpa_intray" value="0">
            <input type="checkbox" name="tanpa_intray" id="tanpa_intray" value="1" class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500" @checked(old('tanpa_intray'))>
            <label for="tanpa_intray" class="text-sm text-zinc-800">Tanpa INTRAY (mis. BOD-3)</label>
        </div>

        <div>
            <span class="block text-sm font-medium text-zinc-800">Asesor (opsional)</span>
            <p class="mt-0.5 text-xs text-zinc-500">Pilih satu atau lebih pengguna dengan peran admin atau konsultan.</p>
            <div class="mt-2 max-h-48 space-y-1 overflow-y-auto rounded-md border border-zinc-200 bg-zinc-50/50 p-3">
                @foreach ($asesorKandidat as $u)
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input type="checkbox" name="id_asesor[]" value="{{ $u->id }}" class="size-4 rounded border-zinc-300" @checked(collect(old('id_asesor', []))->contains($u->id))>
                        <span>{{ $u->name }} <span class="text-zinc-400">({{ $u->role }})</span></span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">Simpan</button>
            <a href="{{ route('asesmen.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50">Batal</a>
        </div>
    </form>
@endsection
