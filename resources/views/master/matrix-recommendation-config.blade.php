@extends('layouts.app')

@section('title', 'Konfigurasi rekomendasi — ' . $versiMatriks->kode_versi . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.versi-matriks.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Versi matriks</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Konfigurasi rekomendasi</h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            Versi: <span class="font-mono text-on-surface">{{ $versiMatriks->kode_versi }}</span>
            <span class="text-on-surface-variant/70">·</span>
            {{ $versiMatriks->nama_versi }}
            @if ($revisiTerbaru)
                <span class="text-on-surface-variant/70">·</span>
                Revisi terbaru: <strong>#{{ $revisiTerbaru->nomor_revisi }}</strong>
            @endif
        </p>
        <p class="mt-2 max-w-3xl text-xs text-on-surface-variant">
            Aturan dievaluasi terhadap <strong>tingkat tercapai</strong> pratinjau integrasi (skala 1–6) per kompetensi wajib.
            Setiap simpan membuat <strong>revisi baru</strong> (immutable). Hitung ulang pratinjau integrasi pada asesmen memakai revisi terbaru.
        </p>
    </div>

    @if ($jumlahAsesmenTerdampak > 0)
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <strong>Peringatan:</strong> {{ $jumlahAsesmenTerdampak }} asesmen sudah memakai matriks ini.
            Perubahan konfigurasi tidak otomatis mengubah rekomendasi agregat yang sudah dihitung — asesor perlu menjalankan «Hitung ulang pratinjau» pada masing-masing asesmen.
        </div>
    @endif

    @if (auth()->user()->role === 'admin')
        <form method="POST" action="{{ route('master.versi-matriks.konfigurasi-rekomendasi.store', $versiMatriks) }}" class="mb-10 space-y-6" id="form-konfigurasi-rekomendasi">
            @csrf

            <div class="rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm space-y-4">
                <h2 class="text-lg font-semibold text-on-surface">Hasil agregat</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-on-surface">Label memenuhi syarat</label>
                        <input type="text" name="label_qualified" value="{{ old('label_qualified', $konfigurasiForm['hasil_agregat']['label_qualified'] ?? '') }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-on-surface">Label belum memenuhi</label>
                        <input type="text" name="label_not_qualified" value="{{ old('label_not_qualified', $konfigurasiForm['hasil_agregat']['label_not_qualified'] ?? '') }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-on-surface">Logika antar dimensi</label>
                    <select name="logika_agregat" class="mt-1 rounded-md border border-outline-variant px-3 py-2 text-sm">
                        @foreach (['and' => 'AND — semua dimensi harus lolos', 'or' => 'OR — minimal satu dimensi lolos'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('logika_agregat', $konfigurasiForm['hasil_agregat']['logika'] ?? 'and') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="dimensi-container" class="space-y-6"></div>

            <div>
                <button type="button" id="btn-tambah-dimensi" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-medium text-on-surface hover:bg-surface-container-low">
                    + Tambah dimensi
                </button>
            </div>

            <div class="rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
                <label for="ringkasan_perubahan" class="block text-sm font-medium text-on-surface">Ringkasan / alasan perubahan <span class="text-error">*</span></label>
                <p class="mt-0.5 text-xs text-on-surface-variant">Wajib diisi untuk jejak audit, terutama jika matriks sudah dipakai asesmen.</p>
                <textarea name="ringkasan_perubahan" id="ringkasan_perubahan" rows="3" required class="mt-2 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">{{ old('ringkasan_perubahan') }}</textarea>
                @error('ringkasan_perubahan')
                    <p class="mt-1 text-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg accent-gradient px-5 py-2.5 text-sm font-medium text-white hover:opacity-90">Simpan revisi baru</button>
                <a href="{{ route('master.versi-matriks.pemetaan.index', $versiMatriks) }}" class="rounded-lg border border-outline-variant px-5 py-2.5 text-sm text-on-surface-variant hover:bg-surface-container-low">Pemetaan</a>
            </div>
        </form>

        <template id="tpl-dimensi">
            <div class="dimensi-blok rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-on-surface">Dimensi evaluasi</h3>
                    <button type="button" class="btn-hapus-dimensi text-sm text-error underline">Hapus dimensi</button>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-on-surface-variant">Kode (slug)</label>
                        <input type="text" data-field="kode" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-on-surface-variant">Label tampilan</label>
                        <input type="text" data-field="label" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-on-surface-variant">Kelompok kompetensi (filter)</label>
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($kelompokKompetensi as $grup)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" data-field="filter_kelompok_kode" value="{{ $grup->kode }}" class="rounded border-outline-variant">
                                <span class="font-mono text-xs">{{ $grup->kode }}</span> {{ $grup->nama }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-on-surface-variant">Logika antar aturan</label>
                    <select data-field="logika" class="mt-1 rounded-md border border-outline-variant px-3 py-2 text-sm">
                        <option value="and">AND</option>
                        <option value="or">OR</option>
                    </select>
                </div>
                <div class="aturan-container space-y-3"></div>
                <button type="button" class="btn-tambah-aturan rounded border border-dashed border-outline-variant px-3 py-1.5 text-xs font-medium text-on-surface-variant hover:border-primary hover:text-primary">
                    + Tambah aturan
                </button>
            </div>
        </template>

        <template id="tpl-aturan">
            <div class="aturan-baris flex flex-wrap items-end gap-3 rounded-md border border-outline-variant/30 bg-surface-container-low/50 p-3">
                <div>
                    <label class="block text-xs text-on-surface-variant">Jenis</label>
                    <select data-field="jenis" class="mt-1 rounded-md border border-outline-variant px-2 py-1.5 text-sm">
                        <option value="forbid_tingkat">Larang tingkat</option>
                        <option value="max_count_tingkat">Maks. jumlah tingkat</option>
                        <option value="min_tingkat_semua">Min. tingkat semua</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-on-surface-variant">Tingkat</label>
                    <input type="number" data-field="tingkat" min="1" max="6" value="2" class="mt-1 w-20 rounded-md border border-outline-variant px-2 py-1.5 text-sm">
                </div>
                <div class="field-maksimum hidden">
                    <label class="block text-xs text-on-surface-variant">Maksimum</label>
                    <input type="number" data-field="maksimum" min="0" max="99" value="3" class="mt-1 w-20 rounded-md border border-outline-variant px-2 py-1.5 text-sm">
                </div>
                <button type="button" class="btn-hapus-aturan mb-0.5 text-xs text-error underline">Hapus</button>
            </div>
        </template>

        @php
            $dimensiInit = old('dimensi', $konfigurasiForm['dimensi'] ?? []);
        @endphp
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('dimensi-container');
                const tplDimensi = document.getElementById('tpl-dimensi');
                const tplAturan = document.getElementById('tpl-aturan');
                const initData = @json($dimensiInit, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                let dimensiIndex = 0;

                function syncAturanNames(bloc) {
                    const dIdx = bloc.dataset.index;
                    bloc.querySelectorAll('.aturan-baris').forEach(function (row, aIdx) {
                        const jenis = row.querySelector('[data-field="jenis"]');
                        const tingkat = row.querySelector('[data-field="tingkat"]');
                        const maks = row.querySelector('[data-field="maksimum"]');
                        if (jenis) jenis.name = 'dimensi[' + dIdx + '][aturan][' + aIdx + '][jenis]';
                        if (tingkat) tingkat.name = 'dimensi[' + dIdx + '][aturan][' + aIdx + '][tingkat]';
                        if (maks) maks.name = 'dimensi[' + dIdx + '][aturan][' + aIdx + '][maksimum]';
                    });
                }

                function syncDimensiNames() {
                    container.querySelectorAll('.dimensi-blok').forEach(function (bloc, idx) {
                        bloc.dataset.index = idx;
                        const kode = bloc.querySelector('[data-field="kode"]');
                        const label = bloc.querySelector('[data-field="label"]');
                        const logika = bloc.querySelector('[data-field="logika"]');
                        if (kode) kode.name = 'dimensi[' + idx + '][kode]';
                        if (label) label.name = 'dimensi[' + idx + '][label]';
                        if (logika) logika.name = 'dimensi[' + idx + '][logika]';
                        bloc.querySelectorAll('[data-field="filter_kelompok_kode"]').forEach(function (cb) {
                            cb.name = 'dimensi[' + idx + '][filter_kelompok_kode][]';
                        });
                        syncAturanNames(bloc);
                    });
                }

                function toggleMaksimum(row) {
                    const jenis = row.querySelector('[data-field="jenis"]')?.value;
                    const wrap = row.querySelector('.field-maksimum');
                    if (wrap) wrap.classList.toggle('hidden', jenis !== 'max_count_tingkat');
                }

                function addAturan(bloc, data) {
                    const clone = tplAturan.content.cloneNode(true);
                    const row = clone.querySelector('.aturan-baris');
                    if (data) {
                        row.querySelector('[data-field="jenis"]').value = data.jenis || 'forbid_tingkat';
                        row.querySelector('[data-field="tingkat"]').value = data.tingkat ?? 2;
                        if (data.maksimum !== undefined) {
                            row.querySelector('[data-field="maksimum"]').value = data.maksimum;
                        }
                    }
                    toggleMaksimum(row);
                    row.querySelector('[data-field="jenis"]').addEventListener('change', function () { toggleMaksimum(row); });
                    row.querySelector('.btn-hapus-aturan').addEventListener('click', function () {
                        row.remove();
                        syncDimensiNames();
                    });
                    bloc.querySelector('.aturan-container').appendChild(row);
                    syncAturanNames(bloc);
                }

                function addDimensi(data) {
                    const clone = tplDimensi.content.cloneNode(true);
                    const bloc = clone.querySelector('.dimensi-blok');
                    bloc.dataset.index = dimensiIndex++;
                    if (data) {
                        bloc.querySelector('[data-field="kode"]').value = data.kode || '';
                        bloc.querySelector('[data-field="label"]').value = data.label || '';
                        bloc.querySelector('[data-field="logika"]').value = data.logika || data.kriteria_qualified?.logika || 'and';
                        const kodes = data.filter_kelompok_kode || [];
                        bloc.querySelectorAll('[data-field="filter_kelompok_kode"]').forEach(function (cb) {
                            cb.checked = kodes.includes(cb.value);
                        });
                    }
                    bloc.querySelector('.btn-tambah-aturan').addEventListener('click', function () {
                        addAturan(bloc, null);
                    });
                    bloc.querySelector('.btn-hapus-dimensi').addEventListener('click', function () {
                        if (container.querySelectorAll('.dimensi-blok').length <= 1) {
                            alert('Minimal satu dimensi diperlukan.');
                            return;
                        }
                        bloc.remove();
                        syncDimensiNames();
                    });
                    container.appendChild(bloc);
                    const aturanList = data?.kriteria_qualified?.aturan || data?.aturan || [];
                    if (aturanList.length) {
                        aturanList.forEach(function (a) { addAturan(bloc, a); });
                    } else {
                        addAturan(bloc, null);
                    }
                    syncDimensiNames();
                }

                document.getElementById('btn-tambah-dimensi').addEventListener('click', function () {
                    addDimensi(null);
                });

                if (initData.length) {
                    initData.forEach(function (d) { addDimensi(d); });
                } else {
                    addDimensi(null);
                }
            });
        </script>
    @else
        <p class="mb-6 text-sm text-on-surface-variant">Tampilan baca saja. Hanya admin yang dapat mengubah konfigurasi.</p>
    @endif

    <section class="rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm overflow-hidden">
        <div class="border-b border-outline-variant/30 px-4 py-3">
            <h2 class="text-lg font-semibold text-on-surface">Riwayat revisi</h2>
        </div>
        @if ($riwayat->isEmpty())
            <p class="p-4 text-sm text-on-surface-variant">Belum ada revisi konfigurasi.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-surface-container-low text-xs uppercase text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3">Revisi</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Pembuat</th>
                            <th class="px-4 py-3">Ringkasan perubahan</th>
                            <th class="px-4 py-3">JSON</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @foreach ($riwayat as $rev)
                            <tr>
                                <td class="px-4 py-3 font-mono font-bold">#{{ $rev->nomor_revisi }}</td>
                                <td class="px-4 py-3 text-on-surface-variant">{{ $rev->dibuat_pada?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-on-surface-variant">{{ $rev->createdBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 max-w-md">{{ $rev->ringkasan_perubahan }}</td>
                                <td class="px-4 py-3">
                                    <details>
                                        <summary class="cursor-pointer text-primary underline text-xs">Lihat</summary>
                                        <pre class="mt-2 max-h-48 overflow-auto rounded bg-surface-container p-2 text-xs">{{ json_encode($rev->konfigurasi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
