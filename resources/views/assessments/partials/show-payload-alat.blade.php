@php
    use App\Support\PayloadAnalysisPresenter;
@endphp

<section class="space-y-6" data-payload-status-url="{{ route('asesmen.payload-alat.statuses', $asesmen) }}">
    <div class="flex items-center justify-between">
        <h2 class="font-display text-2xl text-on-surface">Payload Alat (Otomatis)</h2>
    </div>
    <div class="card-depth rounded-xl bg-surface-container-lowest p-8">
        <p class="text-sm text-on-surface-variant">Tempel teks mentah dari alat, simpan payload, lalu jalankan <strong>Analisis AI bulk</strong>. Hasil wajib direview asesor.</p>

        @if (! ($aiFiturAktif ?? false))
            <div class="mt-4 rounded-xl border border-amber-200/80 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                <p class="font-semibold">Fitur AI belum siap dipakai</p>
                <p class="mt-1">{{ $aiPesanNonaktif ?? '' }}</p>
            </div>
        @endif

        @can('update', $asesmen)
            <form method="POST" action="{{ route('asesmen.payload-alat.store', $asesmen) }}" class="mt-6 space-y-4 border-t border-outline-variant/20 pt-6">
                @csrf
                <div>
                    <label for="id_alat_penilaian_payload" class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Alat</label>
                    <select name="id_alat_penilaian" id="id_alat_penilaian_payload" required @disabled(! $punyaAlatTersediaInput) class="mt-1 w-full rounded-xl border border-outline-variant/40 px-3 py-2 text-sm disabled:bg-surface-container">
                        @if (! $punyaAlatTersediaInput)
                            <option value="">Tidak ada alat pada matriks</option>
                        @else
                            @foreach ($alatTersediaInput as $sel)
                                <option value="{{ $sel->id_alat_penilaian }}">{{ $sel->tool?->kode }} — {{ $sel->tool?->nama }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <label for="teks_muatan" class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Teks muatan</label>
                    <textarea name="teks_muatan" id="teks_muatan" rows="5" required data-normalize-preview="1" class="mt-1 w-full rounded-xl border border-outline-variant/40 px-4 py-3 text-sm shadow-inner">{{ old('teks_muatan') }}</textarea>
                </div>
                <button type="submit" @disabled(! $punyaAlatTersediaInput) class="rounded-lg accent-gradient px-4 py-2 text-sm font-bold text-white hover:opacity-90 disabled:opacity-50">Simpan payload</button>
            </form>
        @endcan

        <h3 class="mt-8 text-section-header uppercase text-on-surface-variant">Payload tersimpan</h3>

        @if ($asesmen->toolPayloads->isNotEmpty())
            <ul class="mt-4 space-y-4">
                @foreach ($asesmen->toolPayloads as $p)
                    @php
                        $formBulkId = 'bulk-ai-payload-' . $p->id;
                        $punyaPilihanModel = ($aiFiturAktif ?? false) && count($aiModelOptions ?? []) > 0;
                        $ringkasan = PayloadAnalysisPresenter::ringkasan($p);
                        $status = $ringkasan['status'];
                        $badgeKelas = match ($status) {
                            'berhasil' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                            'gagal' => 'border-error/30 bg-error-container/40 text-on-error-container',
                            'antrian', 'memproses' => 'border-primary/30 bg-primary-fixed/40 text-primary',
                            default => 'border-outline-variant/40 bg-surface-container text-on-surface-variant',
                        };
                    @endphp
                    <li
                        class="js-payload-card flex flex-col gap-4 rounded-xl border border-outline-variant/30 bg-surface-container-lowest p-5"
                        data-payload-id="{{ $p->id }}"
                        data-status="{{ $status }}"
                    >
                        @can('update', $asesmen)
                            <form
                                id="{{ $formBulkId }}"
                                method="POST"
                                action="{{ route('asesmen.payload-alat.analisis-ai', [$asesmen, $p]) }}"
                                class="js-ai-processing-form js-payload-bulk-form hidden"
                                data-ai-mode="bulk"
                                data-payload-id="{{ $p->id }}"
                                aria-hidden="true"
                            >@csrf</form>
                        @endcan

                        {{-- Baris atas: judul + status + aksi --}}
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                                <span class="font-bold text-on-surface">#{{ $p->id }} · {{ $p->tool?->kode }}</span>
                                <span
                                    class="js-payload-status-badge inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold {{ $badgeKelas }}"
                                    data-status="{{ $status }}"
                                >
                                    @if (in_array($status, ['antrian', 'memproses'], true))
                                        <span class="inline-block size-2 animate-pulse rounded-full bg-current"></span>
                                    @endif
                                    <span class="js-payload-status-label">{{ $ringkasan['label'] }}</span>
                                </span>
                                <button
                                    type="button"
                                    class="js-payload-detail-btn inline-flex items-center gap-1 rounded-lg border border-outline-variant/40 bg-surface-container-low px-2.5 py-1 text-xs font-semibold text-primary hover:bg-primary-fixed/30"
                                    data-payload-detail-url="{{ route('asesmen.payload-alat.show', [$asesmen, $p]) }}"
                                >
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                    Lihat detail
                                </button>
                            </div>
                            @can('update', $asesmen)
                                @if ($aiFiturAktif ?? false)
                                    <button
                                        type="submit"
                                        form="{{ $formBulkId }}"
                                        class="js-payload-analyze-btn inline-flex shrink-0 items-center gap-2 rounded-full bg-primary px-4 py-2 text-sm font-bold text-white shadow-sm shadow-primary/10 transition-all hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                        @disabled(in_array($status, ['antrian', 'memproses'], true))
                                    >
                                        <span class="material-symbols-outlined text-sm">psychology</span>
                                        Analisis AI bulk
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        disabled
                                        title="{{ $aiPesanNonaktif ?? '' }}"
                                        class="inline-flex shrink-0 cursor-not-allowed items-center gap-2 rounded-full bg-surface-container-high px-4 py-2 text-sm font-bold text-on-surface-variant/70"
                                    >
                                        <span class="material-symbols-outlined text-sm">psychology</span>
                                        Analisis AI bulk
                                    </button>
                                @endif
                            @endcan
                        </div>

                        @can('update', $asesmen)
                            @if ($punyaPilihanModel)
                                <div class="max-w-sm">
                                    <label for="nama_model_{{ $p->id }}" class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Model AI</label>
                                    <select
                                        id="nama_model_{{ $p->id }}"
                                        name="nama_model"
                                        form="{{ $formBulkId }}"
                                        class="mt-1 w-full rounded-lg border border-outline-variant/40 bg-surface-container-low px-3 py-2 text-sm text-on-surface"
                                    >
                                        @foreach ($aiModelOptions as $opsi)
                                            <option value="{{ $opsi['id'] }}" @selected($opsi['id'] === ($aiModelDefault ?? ''))>
                                                {{ $opsi['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endcan

                        <p class="text-sm leading-relaxed text-on-surface-variant">{{ \Illuminate\Support\Str::limit($p->teks_muatan, 280) }}</p>

                        <div class="js-payload-hasil-ai @if (! ($p->diproses_pada && is_array($p->hasil_analisis_ai))) hidden @endif">
                            @if ($p->diproses_pada && is_array($p->hasil_analisis_ai))
                                <div class="ai-accent-bg relative rounded-xl border p-5">
                                    <div class="absolute -top-3 left-6 rounded-full bg-primary px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white">Hasil AI</div>
                                    <p class="js-payload-hasil-meta pt-2 text-xs text-on-surface-variant">
                                        Diproses {{ $p->diproses_pada->format('d M Y H:i') }} · {{ count($p->hasil_analisis_ai['usulan'] ?? []) }} usulan
                                    </p>
                                </div>
                            @endif
                        </div>

                        <p class="js-payload-status-pesan text-xs leading-relaxed text-on-surface-variant/90 @if (empty($ringkasan['pesan'])) hidden @endif">
                            {{ $ringkasan['pesan'] }}
                        </p>

                        {{-- Footer informasi --}}
                        <p class="border-t border-outline-variant/20 pt-3 text-xs leading-relaxed text-on-surface-variant/80">
                            @if ($aiFiturAktif ?? false)
                                @if ($aiAntrianAsync ?? false)
                                    Proses di background — halaman tidak perlu menunggu. Status diperbarui otomatis.
                                @else
                                    Analisis berjalan langsung; tunggu hingga selesai sebelum menutup halaman.
                                @endif
                            @elseif (! empty($aiPesanNonaktif))
                                {{ $aiPesanNonaktif }}
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-4 text-sm text-on-surface-variant">
                Belum ada payload. Setelah Anda menekan <strong>Simpan payload</strong>, setiap entri akan menampilkan tombol <strong>Analisis AI bulk</strong> di pojok kanan atas kartu.
            </p>
        @endif
    </div>

    <dialog id="payload-detail-dialog" class="w-full max-w-3xl rounded-2xl border border-outline-variant/40 bg-surface-container-lowest p-0 text-on-surface shadow-2xl backdrop:bg-on-surface/40">
        <div class="flex items-center justify-between border-b border-outline-variant/30 px-6 py-4">
            <h3 class="font-display text-lg font-bold" id="payload-detail-title">Detail payload</h3>
            <button type="button" class="rounded-lg p-2 text-on-surface-variant hover:bg-surface-container-low" data-payload-detail-close aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="max-h-[70vh] overflow-y-auto px-6 py-5" id="payload-detail-body">
            <p class="text-sm text-on-surface-variant">Memuat…</p>
        </div>
    </dialog>
</section>
