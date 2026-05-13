@extends('layouts.app')

@section('title', 'Impor peserta (CSV) — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-zinc-900">Impor peserta</h1>
        <p class="mt-1 max-w-2xl text-sm text-zinc-600">
            Unggah berkas CSV dengan baris pertama header. Kolom wajib: <code class="rounded bg-zinc-100 px-1">kode_peserta</code>,
            <code class="rounded bg-zinc-100 px-1">nama_lengkap</code>. Opsional:
            <code class="rounded bg-zinc-100 px-1">alamat_surel</code>, <code class="rounded bg-zinc-100 px-1">jabatan</code>,
            <code class="rounded bg-zinc-100 px-1">pendidikan</code>, <code class="rounded bg-zinc-100 px-1">tanggal_lahir</code>,
            <code class="rounded bg-zinc-100 px-1">kode_versi_matriks</code> (mis. <code class="rounded bg-zinc-100 px-1">KAMUS-17-DEFAULT</code>).
        </p>
        <div class="mt-4 flex flex-wrap gap-3 text-sm">
            <a href="{{ route('peserta.impor-csv.template', ['delimiter' => ',']) }}" class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-3 py-2 font-medium text-zinc-800 shadow-sm hover:bg-zinc-50">
                Unduh template (koma <span class="font-mono">,</span>)
            </a>
            <a href="{{ route('peserta.impor-csv.template', ['delimiter' => ';']) }}" class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-3 py-2 font-medium text-zinc-800 shadow-sm hover:bg-zinc-50">
                Unduh template (titik koma <span class="font-mono">;</span>)
            </a>
        </div>
        <p class="mt-2 text-xs text-zinc-500">Template berisi header + satu baris contoh (UTF-8 dengan BOM agar Excel mengenali encoding). Sesuaikan pemisah dengan pilihan di bawah saat mengunggah.</p>
    </div>

    <form method="POST" action="{{ route('peserta.impor-csv.store') }}" enctype="multipart/form-data" class="max-w-md space-y-5 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        <fieldset>
            <legend class="text-sm font-medium text-zinc-800">Pemisah kolom (delimiter) pada berkas CSV</legend>
            <p class="mt-1 text-xs text-zinc-500">Harus sama dengan yang dipakai di file Anda (dan dengan template yang diunduh).</p>
            <div class="mt-3 space-y-2">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-800">
                    <input type="radio" name="delimiter" value="," @checked(old('delimiter', ',') === ',') class="size-4 border-zinc-300 text-zinc-900">
                    Koma (<span class="font-mono">,</span>) — umum untuk locale US / Excel regional tertentu
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-800">
                    <input type="radio" name="delimiter" value=";" @checked(old('delimiter') === ';') class="size-4 border-zinc-300 text-zinc-900">
                    Titik koma (<span class="font-mono">;</span>) — umum untuk Excel locale Indonesia / Eropa
                </label>
            </div>
            @error('delimiter')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>
        <div>
            <label for="berkas_csv" class="block text-sm font-medium text-zinc-800">Berkas CSV</label>
            <input type="file" name="berkas_csv" id="berkas_csv" accept=".csv,.txt" required class="mt-1 block w-full text-sm text-zinc-700 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-900 file:px-3 file:py-1.5 file:text-sm file:text-white hover:file:bg-zinc-800">
            @error('berkas_csv')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">Unggah dan proses</button>
    </form>
@endsection
