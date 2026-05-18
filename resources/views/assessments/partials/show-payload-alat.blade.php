<section class="space-y-6">
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
                    <li class="rounded-xl border border-outline-variant/30 bg-surface-container-low/50 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <span class="font-bold text-on-surface">#{{ $p->id }} · {{ $p->tool?->kode }}</span>
                            <x-ui.btn-analisis-ai-bulk
                                :asesmen="$asesmen"
                                :payload="$p"
                                :ai-aktif="$aiFiturAktif ?? false"
                                :ai-pesan-nonaktif="$aiPesanNonaktif ?? ''"
                                :ai-model-options="$aiModelOptions ?? []"
                                :ai-model-default="$aiModelDefault ?? ''"
                                :ai-antrian-async="$aiAntrianAsync ?? false"
                            />
                        </div>
                        <p class="mt-2 line-clamp-3 text-sm text-on-surface-variant">{{ \Illuminate\Support\Str::limit($p->teks_muatan, 240) }}</p>
                        @if ($p->diproses_pada && is_array($p->hasil_analisis_ai))
                            <div class="ai-accent-bg relative mt-4 rounded-xl border p-5">
                                <div class="absolute -top-3 left-6 rounded-full bg-primary px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white">Hasil AI</div>
                                <p class="pt-2 text-xs text-on-surface-variant">Diproses {{ $p->diproses_pada->format('d M Y H:i') }} · {{ count($p->hasil_analisis_ai['usulan'] ?? []) }} usulan</p>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-4 text-sm text-on-surface-variant">
                Belum ada payload. Setelah Anda menekan <strong>Simpan payload</strong>, tombol <strong>Analisis AI bulk</strong> akan tampil di samping setiap entri di daftar ini.
            </p>
        @endif
    </div>
</section>
