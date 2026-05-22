@extends('layouts.app')

@section('title', 'Ubah perilaku kunci — ' . config('app.name'))

@section('content')
    @php
        $teksIndikatorTampil = $teksIndikatorResmi !== ''
            ? $teksIndikatorResmi
            : trim((string) ($perilaku->teks_perilaku ?? ''));
        $kutipanTampil = trim((string) ($perilaku->kutipan_referensi ?? ''));
        if ($kutipanTampil === '' && $perilaku->evidence) {
            $muatanAi = $perilaku->evidence->ai_muatan;
            if (is_array($muatanAi)) {
                $kutipanTampil = trim((string) ($muatanAi['kutipan_dari_teks_mentah'] ?? ''));
            }
        }
    @endphp

    <div class="mb-6">
        <a href="{{ route('asesmen.show', $asesmen) }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali ke asesmen</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Ubah perilaku kunci</h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $perilaku->tool?->kode }} · {{ $perilaku->competency?->kode_kompetensi }} — {{ $perilaku->competency?->nama }}
        </p>
    </div>

    <section class="rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-sm">
        <form method="POST" action="{{ route('asesmen.perilaku.update', [$asesmen, $perilaku]) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="rounded-lg border border-outline-variant/30 bg-surface-container-low/50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Indikator perilaku (master data)</p>
                <p class="mt-1 text-xs text-on-surface-variant">Teks resmi dari kamus kompetensi. Ubah melalui pilihan tingkat di bawah.</p>
                <div
                    id="indikator-perilaku-tampil"
                    class="mt-3 rounded-lg border border-outline-variant/20 bg-surface-container-lowest px-4 py-3 text-sm font-medium leading-relaxed text-on-surface"
                    data-placeholder="Pilih tingkat untuk memuat indikator resmi dari master data."
                >
                    @if ($teksIndikatorTampil !== '')
                        {{ $teksIndikatorTampil }}
                    @else
                        <span class="text-on-surface-variant/70">Pilih tingkat untuk memuat indikator resmi dari master data.</span>
                    @endif
                </div>
            </div>

            <div>
                <label for="id_tingkat_kompetensi_edit" class="block text-xs font-medium text-on-surface-variant">Tingkat indikator perilaku</label>
                <p class="mt-0.5 text-xs text-on-surface-variant">Satu-satunya cara mengubah indikator perilaku pada mapping ini.</p>
                <select name="id_tingkat_kompetensi" id="id_tingkat_kompetensi_edit" class="mt-1 w-full rounded-md border border-outline-variant px-2 py-1.5 text-sm">
                    <option value="">— belum dipilih —</option>
                    @foreach ($tingkatUntukKompetensi as $tk)
                        <option value="{{ $tk->id }}" @selected(old('id_tingkat_kompetensi', $perilaku->id_tingkat_kompetensi) == $tk->id)>
                            Level {{ $tk->tingkat }}@if (! empty($tk->etiket)) — {{ $tk->etiket }} @endif
                        </option>
                    @endforeach
                </select>
                @error('id_tingkat_kompetensi')
                    <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="alasan_pemilihan_edit" class="block text-xs font-medium text-on-surface-variant">Alasan pemilihan tingkat</label>
                <p class="mt-0.5 text-xs text-on-surface-variant">Dapat disunting (mis. koreksi atau penjelasan dari analisis AI).</p>
                <textarea name="alasan_pemilihan" id="alasan_pemilihan_edit" rows="4" class="mt-1 w-full rounded-md border border-outline-variant px-2 py-1.5 text-sm">{{ old('alasan_pemilihan', $perilaku->alasan_pemilihan) }}</textarea>
                @error('alasan_pemilihan')
                    <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg border border-outline-variant/30 bg-surface-container-low/50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Kutipan referensi (bukti / payload)</p>
                <p class="mt-1 text-xs text-on-surface-variant">Data sumber dari bukti atau payload alat — tidak dapat diubah di sini.</p>
                @if ($kutipanTampil !== '')
                    <blockquote class="mt-3 rounded-r-lg border-l-4 border-primary/50 bg-surface-container-low px-4 py-3 text-sm italic leading-relaxed text-on-surface-variant">
                        &ldquo;{{ $kutipanTampil }}&rdquo;
                    </blockquote>
                @else
                    <p class="mt-3 text-sm text-on-surface-variant/70">— Tidak ada kutipan referensi —</p>
                @endif
            </div>

            <div class="rounded-lg border border-outline-variant/30 bg-surface-container-low/40 p-4">
                <label class="flex cursor-pointer items-start gap-3">
                    <input
                        type="checkbox"
                        name="simpan_sebagai_mapping"
                        value="1"
                        class="mt-0.5 size-4 rounded border-outline-variant text-primary focus:ring-primary/20"
                        @checked(old('simpan_sebagai_mapping', $perilaku->tervalidasi))
                    >
                    <span class="text-sm text-on-surface">
                        <span class="font-semibold">Simpan sebagai mapping resmi peserta</span>
                        <span class="mt-1 block text-xs text-on-surface-variant">Jika dicentang, status berubah dari Draft menjadi Disimpan dan tidak dapat dihapus lewat penghapusan payload bulk.</span>
                    </span>
                </label>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-outline-variant/20 pt-4">
                <button type="submit" class="rounded-lg accent-gradient px-3 py-1.5 text-sm font-medium text-white hover:opacity-90">Simpan</button>
                <a href="{{ route('asesmen.show', $asesmen) }}" class="rounded-md border border-outline-variant bg-surface-container-lowest px-3 py-1.5 text-sm font-medium text-on-surface hover:bg-surface-container-low">Batal</a>
            </div>
        </form>
    </section>

    @php
        $petaIndikator = $tingkatUntukKompetensi->mapWithKeys(fn ($tk) => [
            (string) $tk->id => trim((string) ($tk->indikator_perilaku ?? '')),
        ]);
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sel = document.getElementById('id_tingkat_kompetensi_edit');
            var box = document.getElementById('indikator-perilaku-tampil');
            if (!sel || !box) return;
            var map = @json($petaIndikator);
            var placeholder = box.dataset.placeholder || '';

            function tampilkanIndikator(teks) {
                var t = String(teks || '').trim();
                if (t === '') {
                    box.innerHTML =
                        '<span class="text-on-surface-variant/70">' +
                        placeholder.replace(/</g, '&lt;') +
                        '</span>';
                    return;
                }
                box.textContent = t;
            }

            sel.addEventListener('change', function () {
                var id = String(this.value || '');
                if (!id || !map[id]) {
                    tampilkanIndikator('');
                    return;
                }
                tampilkanIndikator(map[id]);
            });
        });
    </script>
@endsection
