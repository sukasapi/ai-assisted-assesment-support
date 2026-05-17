@extends('layouts.app')

@section('title', 'Ubah perilaku kunci — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('asesmen.show', $asesmen) }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali ke asesmen</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Ubah perilaku kunci</h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            {{ $perilaku->tool?->kode }} · {{ $perilaku->competency?->kode_kompetensi }} — {{ $perilaku->competency?->nama }}
        </p>
    </div>

    <section class="rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-5 shadow-sm">
        <form method="POST" action="{{ route('asesmen.perilaku.update', [$asesmen, $perilaku]) }}" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="id_tingkat_kompetensi_edit" class="block text-xs font-medium text-on-surface-variant">Tingkat indikator perilaku</label>
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
                <label for="teks_perilaku_edit" class="block text-xs font-medium text-on-surface-variant">Teks perilaku kunci</label>
                <p class="mt-0.5 text-xs text-on-surface-variant">Mengikuti <strong>indikator perilaku</strong> tingkat yang dipilih; ganti tingkat untuk memuat ulang teks resmi (boleh disunting setelahnya).</p>
                <textarea name="teks_perilaku" id="teks_perilaku_edit" rows="4" required class="mt-1 w-full rounded-md border border-outline-variant px-2 py-1.5 text-sm">{{ old('teks_perilaku', $perilaku->teks_perilaku) }}</textarea>
                @error('teks_perilaku')
                    <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="alasan_pemilihan_edit" class="block text-xs font-medium text-on-surface-variant">Alasan pemilihan tingkat (dari AI atau penyuntingan)</label>
                <p class="mt-0.5 text-xs text-on-surface-variant">Isi dari analisis bulk memakai field alasan model; Anda dapat memperbaikinya di sini.</p>
                <textarea name="alasan_pemilihan" id="alasan_pemilihan_edit" rows="4" class="mt-1 w-full rounded-md border border-outline-variant px-2 py-1.5 text-sm">{{ old('alasan_pemilihan', $perilaku->alasan_pemilihan) }}</textarea>
                @error('alasan_pemilihan')
                    <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="kutipan_referensi_edit" class="block text-xs font-medium text-on-surface-variant">Kutipan (referensi) dari bukti / payload</label>
                <p class="mt-0.5 text-xs text-on-surface-variant">Untuk bulk AI, kutipan verbatim dari teks muatan; boleh dikoreksi jika perlu.</p>
                <textarea name="kutipan_referensi" id="kutipan_referensi_edit" rows="4" class="mt-1 w-full rounded-md border border-outline-variant px-2 py-1.5 text-sm">{{ old('kutipan_referensi', $perilaku->kutipan_referensi) }}</textarea>
                @error('kutipan_referensi')
                    <p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
                @enderror
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
            var ta = document.getElementById('teks_perilaku_edit');
            if (!sel || !ta) return;
            var map = @json($petaIndikator);
            sel.addEventListener('change', function () {
                var id = String(this.value || '');
                if (!id || !map[id]) return;
                var t = String(map[id]).trim();
                if (t !== '') ta.value = t;
            });
        });
    </script>
@endsection
