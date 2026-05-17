@extends('layouts.app')

@section('title', 'Impor peserta (CSV) — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-on-surface">Impor peserta</h1>
        <p class="mt-1 max-w-2xl text-sm text-on-surface-variant">
            Unggah berkas CSV dengan baris pertama header. Kolom wajib: <code class="rounded bg-surface-container px-1">kode_peserta</code>,
            <code class="rounded bg-surface-container px-1">nama_lengkap</code>. Opsional:
            <code class="rounded bg-surface-container px-1">alamat_surel</code>, <code class="rounded bg-surface-container px-1">jabatan</code>,
            <code class="rounded bg-surface-container px-1">pendidikan</code>, <code class="rounded bg-surface-container px-1">tanggal_lahir</code>,
            <code class="rounded bg-surface-container px-1">kode_versi_matriks</code> (mis. <code class="rounded bg-surface-container px-1">KAMUS-17-DEFAULT</code>).
        </p>
        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            <a href="{{ route('peserta.impor-csv.template', ['delimiter' => ',']) }}" class="inline-flex items-center rounded-md border border-outline-variant bg-surface-container-lowest px-3 py-2 font-medium text-on-surface shadow-sm hover:bg-surface-container-low">
                Unduh template (koma <span class="font-mono">,</span>)
            </a>
            <a href="{{ route('peserta.impor-csv.template', ['delimiter' => ';']) }}" class="inline-flex items-center rounded-md border border-outline-variant bg-surface-container-lowest px-3 py-2 font-medium text-on-surface shadow-sm hover:bg-surface-container-low">
                Unduh template (titik koma <span class="font-mono">;</span>)
            </a>
        </div>
        <p class="mt-2 text-xs text-on-surface-variant">Template berisi header + satu baris contoh (UTF-8 dengan BOM agar Excel mengenali encoding). Sesuaikan pemisah dengan pilihan di bawah saat mengunggah.</p>
    </div>

    <form method="POST" action="{{ route('peserta.impor-csv.store') }}" enctype="multipart/form-data" class="max-w-md space-y-5 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
        @csrf
        <fieldset>
            <legend class="text-sm font-medium text-on-surface">Pemisah kolom (delimiter) pada berkas CSV</legend>
            <p class="mt-1 text-xs text-on-surface-variant">Harus sama dengan yang dipakai di file Anda (dan dengan template yang diunduh).</p>
            <div class="mt-3 space-y-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-on-surface">
                    <input type="radio" name="delimiter" value="," @checked(old('delimiter', ',') === ',') class="size-4 border-outline-variant text-on-surface">
                    Koma (<span class="font-mono">,</span>) — umum untuk locale US / Excel regional tertentu
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-on-surface">
                    <input type="radio" name="delimiter" value=";" @checked(old('delimiter') === ';') class="size-4 border-outline-variant text-on-surface">
                    Titik koma (<span class="font-mono">;</span>) — umum untuk Excel locale Indonesia / Eropa
                </label>
            </div>
            @error('delimiter')
                <p class="mt-2 text-xs text-error">{{ $message }}</p>
            @enderror
        </fieldset>
        <div>
            <label for="berkas_csv" class="block text-sm font-medium text-on-surface">Berkas CSV</label>
            <input type="file" name="berkas_csv" id="berkas_csv" accept=".csv,.txt" required class="mt-1 block w-full text-sm text-on-surface-variant file:mr-3 file:rounded-md file:border-0 accent-gradient file:border-0 file:px-3 file:py-1.5 file:text-sm file:text-white hover:file:opacity-90">
            @error('berkas_csv')
                <p class="mt-2 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm font-medium text-white hover:opacity-90">Unggah dan proses</button>
    </form>
@endsection
