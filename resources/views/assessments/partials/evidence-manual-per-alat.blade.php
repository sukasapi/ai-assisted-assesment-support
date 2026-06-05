@php
    use App\Enums\EvidenceSourceType;
    use App\Enums\EvidenceTranscriptionStatus;
@endphp

<section class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="font-display text-2xl text-on-surface">Bukti Penilaian</h2>
    </div>

    @forelse ($pemilihanAlatPreset->where('aktif', true) as $sel)
        @php
            $tool = $sel->tool;
            $idAlat = (int) $sel->id_alat_penilaian;
            $buktiAlat = $buktiPerAlat->get($idAlat, collect());
            $adaBukti = $buktiAlat->isNotEmpty();
            $kompetensiAlat = $pemetaanKompetensiAlat
                ->filter(fn ($m) => (int) $m->id_alat_penilaian === $idAlat)
                ->map(fn ($m) => $m->competency)
                ->filter()
                ->unique('id')
                ->sortBy('kode_kompetensi')
                ->values();
            if ($kompetensiAlat->isEmpty()) {
                $kompetensiAlat = $kompetensi;
            }
        @endphp
        <div class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest {{ ! $adaBukti ? 'opacity-90' : '' }}" data-evidence-tool-card>
            <div class="flex items-center justify-between border-b border-outline-variant/30 bg-surface-container-low/50 px-8 py-5">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined {{ $adaBukti ? 'text-primary' : 'text-on-surface-variant/50' }}" @if($adaBukti) style="font-variation-settings: 'FILL' 1;" @endif>{{ $ikonAlat($tool?->kode) }}</span>
                    <h3 class="font-bold text-on-surface">{{ $tool?->kode }} — {{ $tool?->nama }}</h3>
                </div>
                @if ($adaBukti && config('ai.aktif'))
                    @can('update', $asesmen)
                        @php $bPertama = $buktiAlat->first(); @endphp
                        <form method="POST" action="{{ route('asesmen.bukti.analisis-ai', [$asesmen, $bPertama]) }}" class="js-ai-processing-form shrink-0" data-ai-mode="incremental">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 rounded-full bg-primary px-5 py-2 text-sm font-bold text-white shadow-md shadow-primary/10 transition-all hover:opacity-90">
                                <span class="material-symbols-outlined text-sm">psychology</span>
                                Analisis AI
                            </button>
                        </form>
                    @endcan
                @else
                    <button type="button" disabled class="flex cursor-not-allowed items-center gap-2 rounded-full bg-surface-container-high px-5 py-2 text-sm font-bold text-on-surface-variant/60">
                        <span class="material-symbols-outlined text-sm">psychology</span>
                        Analisis AI
                    </button>
                @endif
            </div>
            <div class="space-y-6 p-8">
                @foreach ($buktiAlat as $b)
                    @php
                        $jenisBukti = $b->jenis_sumber ?? EvidenceSourceType::Teks;
                        $statusTrx = $b->status_transkripsi;
                        $bisaEdit = $isDraft && auth()->user()?->can('update', $asesmen);
                        $tampilFormEdit = $bisaEdit && ($loop->last || request()->query('edit_bukti') == $b->id);
                    @endphp
                    <div class="space-y-4 rounded-xl border border-outline-variant/25 bg-surface-container-low/30 p-5" data-evidence-row="{{ $b->id }}">
                        @if ($b->ai_alasan || $b->ai_tingkat || (is_array($b->ai_muatan) && ! empty($b->ai_muatan['kutipan_dari_teks_mentah'])))
                            <div class="ai-accent-bg relative rounded-xl border p-5 shadow-sm">
                                <div class="absolute -top-3 left-6 rounded-full bg-primary px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white">Hasil AI</div>
                                <p class="pt-2 text-sm font-medium leading-relaxed">
                                    @if ($b->ai_tingkat)
                                        <span class="font-bold text-primary">Level {{ $b->ai_tingkat }}</span> —
                                    @endif
                                    {{ $b->ai_alasan ?: ($b->ai_muatan['kutipan_dari_teks_mentah'] ?? '') }}
                                </p>
                                @if ($isDraft && auth()->user()?->can('update', $asesmen))
                                    <form method="POST" action="{{ route('asesmen.bukti.mapping', [$asesmen, $b]) }}" class="mt-4">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-primary/30 bg-primary-fixed px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary-fixed/80">
                                            <span class="material-symbols-outlined text-sm">account_tree</span>
                                            Mapping
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                                {{ $b->competency?->nama ?? 'Kompetensi' }}
                                <span class="font-normal normal-case">({{ $b->competency?->kode_kompetensi }})</span>
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md border border-outline-variant/40 bg-surface-container-lowest px-2 py-0.5 text-[10px] font-bold uppercase text-on-surface-variant">
                                    {{ $jenisBukti->label() }}
                                </span>
                                @if ($jenisBukti === EvidenceSourceType::Wawancara && $statusTrx)
                                    @php
                                        $badgeTrx = match ($statusTrx) {
                                            EvidenceTranscriptionStatus::Selesai => 'border-emerald-100 bg-emerald-50 text-emerald-700',
                                            EvidenceTranscriptionStatus::Gagal => 'border-amber-100 bg-amber-50 text-amber-800',
                                            EvidenceTranscriptionStatus::Memproses, EvidenceTranscriptionStatus::Menunggu => 'border-primary/20 bg-primary-fixed/40 text-primary',
                                            default => 'border-outline-variant/40 bg-surface-container text-on-surface-variant',
                                        };
                                    @endphp
                                    <span class="rounded-md border px-2 py-0.5 text-[10px] font-semibold {{ $badgeTrx }}">
                                        {{ $statusTrx->label() }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($statusTrx === EvidenceTranscriptionStatus::Gagal && $b->pesan_status_transkripsi)
                            <p class="rounded-lg border border-amber-200/80 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                {{ $b->pesan_status_transkripsi }} — Anda dapat mengisi atau mengoreksi transkrip di bawah.
                            </p>
                        @endif

                        @if ($bisaEdit && ! $tampilFormEdit)
                            <button type="button" class="js-evidence-edit-toggle text-xs font-bold text-primary hover:underline" data-target="evidence-edit-{{ $b->id }}">
                                Perbarui bukti
                            </button>
                        @endif

                        <div id="evidence-view-{{ $b->id }}" class="{{ $tampilFormEdit && $bisaEdit ? 'hidden' : '' }}">
                            <div class="min-h-[120px] rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 text-sm leading-relaxed text-on-surface shadow-inner whitespace-pre-wrap">{{ $b->teks_mentah }}</div>
                        </div>

                        @if ($bisaEdit)
                            <form
                                id="evidence-edit-{{ $b->id }}"
                                method="POST"
                                action="{{ route('asesmen.bukti.update', [$asesmen, $b]) }}"
                                enctype="multipart/form-data"
                                class="js-evidence-form space-y-3 {{ $tampilFormEdit ? '' : 'hidden' }}"
                                data-evidence-edit-form
                                data-has-audio="{{ $b->path_audio ? '1' : '0' }}"
                                data-transcript-preview-url="{{ route('asesmen.bukti.transkrip.preview', $asesmen) }}"
                                data-transcript-evidence-url="{{ route('asesmen.bukti.transkrip', [$asesmen, $b]) }}"
                                @if ($b->path_audio) data-audio-play-url="{{ route('asesmen.bukti.audio', [$asesmen, $b]) }}" @endif
                            >
                                @csrf
                                @method('PATCH')
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Jenis bukti</span>
                                    <div class="mt-2 flex flex-wrap gap-4">
                                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-on-surface">
                                            <input type="radio" name="jenis_sumber" value="teks" class="js-evidence-jenis text-primary" @checked($jenisBukti === EvidenceSourceType::Teks)>
                                            Bukti teks
                                        </label>
                                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-on-surface">
                                            <input type="radio" name="jenis_sumber" value="wawancara" class="js-evidence-jenis text-primary" @checked($jenisBukti === EvidenceSourceType::Wawancara)>
                                            Bukti wawancara
                                        </label>
                                    </div>
                                </div>
                                <div class="js-evidence-field-wawancara space-y-2 {{ $jenisBukti === EvidenceSourceType::Teks ? 'hidden' : '' }}">
                                    <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                                        {{ $b->path_audio ? 'Ganti berkas audio (opsional)' : 'Berkas audio wawancara' }}
                                    </label>
                                    <div class="flex flex-wrap items-start gap-2">
                                        <input type="file" name="berkas_audio" accept="audio/*" class="min-w-0 flex-1 text-sm text-on-surface-variant file:mr-3 file:rounded-lg file:border-0 file:bg-primary-fixed file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary">
                                        <div class="flex shrink-0 items-center gap-2">
                                            @if (config('stt.aktif'))
                                                <button type="button" class="js-evidence-transcript rounded-lg border border-primary/30 bg-primary-fixed px-4 py-2 text-sm font-bold text-primary transition-colors hover:bg-primary-fixed/80 disabled:cursor-not-allowed disabled:opacity-50">
                                                    Transcript
                                                </button>
                                            @endif
                                            <button type="button" class="js-evidence-play rounded-lg border border-outline-variant/50 bg-surface-container-lowest px-4 py-2 text-sm font-bold text-on-surface transition-colors hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-50 {{ $b->path_audio ? '' : 'hidden' }}">
                                                Play
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-xs text-on-surface-variant">
                                        @if ($b->path_audio)
                                            Biarkan kosong jika tidak ingin mengganti audio. Klik <span class="font-semibold">Transcript</span> untuk transkripsi ulang. MP3, WAV, M4A, WebM.
                                        @else
                                            Unggah rekaman wawancara (MP3, WAV, M4A, WebM), klik <span class="font-semibold">Transcript</span>, atau isi transkrip manual di bawah.
                                        @endif
                                        Jeda dalam rekaman dianggap paragraf baru setelah transkripsi.
                                    </p>
                                    @if (! config('stt.aktif'))
                                        <p class="rounded-lg border border-amber-200/80 bg-amber-50 px-3 py-2 text-xs text-amber-900">Transkripsi otomatis nonaktif — isi transkrip manual setelah unggah audio.</p>
                                    @endif
                                </div>
                                <div class="js-evidence-teks-block space-y-2">
                                    <label class="js-evidence-teks-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                                        {{ $jenisBukti === EvidenceSourceType::Wawancara ? 'Transkrip / teks bukti' : 'Teks bukti' }}
                                    </label>
                                    <textarea
                                        name="teks_mentah"
                                        rows="6"
                                        class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3 text-sm shadow-inner"
                                        placeholder="{{ $jenisBukti === EvidenceSourceType::Wawancara ? 'Koreksi atau isi transkrip wawancara…' : 'Tuliskan bukti observasi di sini…' }}"
                                    >{{ $b->teks_mentah }}</textarea>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm font-bold text-white hover:opacity-90">Simpan perubahan</button>
                                    <button type="button" class="js-evidence-edit-cancel rounded-lg border border-outline-variant/50 px-4 py-2 text-sm font-semibold text-on-surface-variant hover:bg-surface-container-low" data-view="evidence-view-{{ $b->id }}" data-form="evidence-edit-{{ $b->id }}">Batal</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endforeach

                @if (! $adaBukti)
                    <div class="asesmen-evidence-empty flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-outline-variant/50 bg-surface-container-low/40 px-8 py-12 text-on-surface-variant/70">
                        <span class="material-symbols-outlined text-4xl">upload_file</span>
                        <p class="text-center text-sm font-medium">Belum ada bukti untuk alat ini — tambahkan di bawah.</p>
                    </div>
                @endif

                @if ($isDraft)
                    @can('update', $asesmen)
                        <form
                            method="POST"
                            action="{{ route('asesmen.bukti.store', $asesmen) }}"
                            enctype="multipart/form-data"
                            class="js-evidence-form space-y-4 border-t border-outline-variant/20 pt-6"
                            data-evidence-store-form
                            data-transcript-preview-url="{{ route('asesmen.bukti.transkrip.preview', $asesmen) }}"
                        >
                            @csrf
                            <input type="hidden" name="id_alat_penilaian" value="{{ $idAlat }}">
                            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Tambah bukti</p>
                            <div>
                                <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Kompetensi</label>
                                <select name="id_kompetensi" required class="mt-1 w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm">
                                    @foreach ($kompetensiAlat as $c)
                                        <option value="{{ $c->id }}">{{ $c->kode_kompetensi }} — {{ $c->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Jenis bukti</span>
                                <div class="mt-2 flex flex-wrap gap-4">
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-on-surface">
                                        <input type="radio" name="jenis_sumber" value="teks" class="js-evidence-jenis text-primary" checked>
                                        Bukti teks
                                    </label>
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-on-surface">
                                        <input type="radio" name="jenis_sumber" value="wawancara" class="js-evidence-jenis text-primary">
                                        Bukti wawancara
                                    </label>
                                </div>
                            </div>
                            <div class="js-evidence-field-wawancara hidden space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Berkas audio wawancara</label>
                                <div class="flex flex-wrap items-start gap-2">
                                    <input type="file" name="berkas_audio" accept="audio/*" class="min-w-0 flex-1 text-sm text-on-surface-variant file:mr-3 file:rounded-lg file:border-0 file:bg-primary-fixed file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary">
                                    <div class="flex shrink-0 items-center gap-2">
                                        @if (config('stt.aktif'))
                                            <button type="button" class="js-evidence-transcript rounded-lg border border-primary/30 bg-primary-fixed px-4 py-2 text-sm font-bold text-primary transition-colors hover:bg-primary-fixed/80 disabled:cursor-not-allowed disabled:opacity-50">
                                                Transcript
                                            </button>
                                        @endif
                                        <button type="button" class="js-evidence-play hidden rounded-lg border border-outline-variant/50 bg-surface-container-lowest px-4 py-2 text-sm font-bold text-on-surface transition-colors hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-50">
                                            Play
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-on-surface-variant">Unggah rekaman wawancara (MP3, WAV, M4A, WebM), klik <span class="font-semibold">Transcript</span> untuk mengubah suara menjadi teks, atau isi transkrip manual di bawah. Jeda dianggap paragraf baru.</p>
                                @if (! config('stt.aktif'))
                                    <p class="rounded-lg border border-amber-200/80 bg-amber-50 px-3 py-2 text-xs text-amber-900">Transkripsi otomatis nonaktif — isi transkrip manual setelah unggah audio.</p>
                                @endif
                            </div>
                            <div class="js-evidence-teks-block space-y-2">
                                <label class="js-evidence-teks-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">Teks bukti</label>
                                <textarea name="teks_mentah" rows="5" data-normalize-preview="1" class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3 text-sm shadow-inner" placeholder="Tuliskan bukti observasi di sini…">{{ old('teks_mentah') }}</textarea>
                            </div>
                            <button type="submit" @disabled(! $punyaAlatTersediaInput) class="rounded-lg accent-gradient px-4 py-2 text-sm font-bold text-white hover:opacity-90 disabled:opacity-50">Tambah bukti</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-on-surface-variant">Tidak ada alat aktif pada preset.</p>
    @endforelse
</section>
