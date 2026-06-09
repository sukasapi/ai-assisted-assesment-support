@extends('layouts.app')

@section('title', 'Pemetaan — ' . $versiMatriks->kode_versi . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.versi-matriks.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Versi matriks</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Pemetaan kompetensi–alat</h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Versi: <span class="font-mono text-on-surface">{{ $versiMatriks->kode_versi }}</span>
            <span class="text-on-surface-variant/70">·</span>
            {{ $versiMatriks->nama_versi }}
        </p>
        @if (auth()->user()->role === 'admin')
            <p class="mt-2 max-w-2xl text-xs text-on-surface-variant">
                Kompetensi dikelompokkan menurut kelompok (inti, manajerial, kepemimpinan). Hanya kombinasi <strong>kompetensi &amp; alat aktif</strong> yang tampil di kisi. Centang alat per baris lalu simpan. Pasangan baru: bobot 1, tidak wajib, aktif.
            </p>
        @else
            <p class="mt-2 text-xs text-on-surface-variant">Tampilan baca saja. Hanya admin yang dapat mengubah pemetaan.</p>
        @endif
    </div>

    @if (! $punyaKompetensi || $alat->isEmpty())
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            @if (! $punyaKompetensi)
                Belum ada data kompetensi.
            @endif
            @if ($alat->isEmpty())
                Belum ada data alat penilaian.
            @endif
        </div>
    @elseif ($kelompokKompetensi->isEmpty())
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            @if (filled(request('q')))
                Tidak ada kompetensi yang cocok dengan pencarian.
            @else
                Tidak ada kelompok kompetensi dengan data kompetensi aktif.
            @endif
        </div>
    @elseif (auth()->user()->role === 'admin')
        <x-ui.table-toolbar placeholder="Cari kode atau nama kompetensi..." />

        <form method="POST" action="{{ route('master.versi-matriks.pemetaan.sync', $versiMatriks) }}" class="space-y-4">
            @csrf
            <div class="overflow-x-auto rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
                <table class="w-full min-w-max table-fixed divide-y divide-outline-variant/30 text-sm">
                    <colgroup>
                        <col class="w-[min(28rem,35%)]">
                        @foreach ($alat as $tool)
                            <col>
                        @endforeach
                    </colgroup>
                    <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                        <tr>
                            <th class="sticky-col-header border-r border-outline-variant/40 px-3 py-2">Kompetensi</th>
                            @foreach ($alat as $tool)
                                <th class="px-2 py-2 text-center" title="{{ $tool->nama }}">
                                    <span class="block truncate font-mono normal-case text-on-surface-variant">{{ $tool->kode }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @include('master.partials.matrix-mapping-tbody', ['editable' => true])
                    </tbody>
                </table>
            </div>
            <div class="max-w-2xl rounded-md border border-amber-200 bg-amber-50/80 px-3 py-2 text-xs text-amber-950">
                <label class="flex cursor-pointer items-start gap-2">
                    <input type="checkbox" name="hapus_semua" value="1" class="mt-0.5 size-4 rounded border-amber-400 text-on-surface" @checked(old('hapus_semua'))>
                    <span><strong>Konfirmasi hapus semua:</strong> centang hanya jika Anda sengaja ingin menghapus seluruh pemetaan pada versi ini (menyimpan tanpa kotak tercentang). Tanpa centang ini, simpan akan ditolak jika masih ada pemetaan.</span>
                </label>
                @error('hapus_semua')
                    <p class="mt-2 text-xs font-medium text-red-700">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm font-medium text-white hover:opacity-90">Simpan pemetaan</button>
                <span class="text-xs text-on-surface-variant">Hanya pasangan tercentang pada kisi di atas yang dipertahankan untuk kombinasi kompetensi–alat aktif.</span>
            </div>
        </form>
    @else
        <x-ui.table-toolbar placeholder="Cari kode atau nama kompetensi..." />

        <div class="overflow-x-auto rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
            <table class="w-full min-w-max table-fixed divide-y divide-outline-variant/30 text-sm">
                <colgroup>
                    <col class="w-[min(28rem,35%)]">
                    @foreach ($alat as $tool)
                        <col>
                    @endforeach
                </colgroup>
                <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                    <tr>
                        <th class="sticky-col-header border-r border-outline-variant/40 px-3 py-2">Kompetensi</th>
                        @foreach ($alat as $tool)
                            <th class="px-2 py-2 text-center" title="{{ $tool->nama }}">
                                <span class="block truncate font-mono normal-case text-on-surface-variant">{{ $tool->kode }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @include('master.partials.matrix-mapping-tbody', ['editable' => false])
                </tbody>
            </table>
        </div>
    @endif
@endsection
