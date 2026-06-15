@php
    use App\Enums\EvidenceSourceType;
    use App\Enums\EvidenceTranscriptionStatus;

    $alatAktif = $pemilihanAlatPreset->where('aktif', true)->values();
    $idKompetensiTerpeta = $pemetaanKompetensiAlat
        ->pluck('id_kompetensi')
        ->unique()
        ->map(fn ($id) => (int) $id);

    $kompetensiSidebar = collect();
    if (isset($kelompokKompetensiMatriks) && $kelompokKompetensiMatriks->isNotEmpty()) {
        foreach ($kelompokKompetensiMatriks as $grup) {
            foreach ($grup->competencies as $c) {
                if ($idKompetensiTerpeta->contains((int) $c->id)) {
                    $kompetensiSidebar->push($c);
                }
            }
        }
    } else {
        $kompetensiSidebar = $kompetensi->filter(fn ($c) => $idKompetensiTerpeta->contains((int) $c->id))->values();
    }

    $kompetensiSidebar = $kompetensiSidebar->unique('id')->values();

    $pasanganPemetaan = $pemetaanKompetensiAlat
        ->map(fn ($m) => ['k' => (int) $m->id_kompetensi, 'a' => (int) $m->id_alat_penilaian])
        ->unique(fn ($p) => $p['k'].'-'.$p['a'])
        ->values();

    $semuaBukti = $asesmen->evidenceItems->sortByDesc('dibuat_pada')->values();

    $pasanganDenganBukti = $semuaBukti
        ->unique(fn ($b) => $b->id_kompetensi.'-'.$b->id_alat_penilaian)
        ->map(fn ($b) => [
            'k' => (int) $b->id_kompetensi,
            'a' => (int) $b->id_alat_penilaian,
            'id' => (int) $b->id,
        ])
        ->values();

    $bisaEdit = $isDraft && auth()->user()?->can('update', $asesmen);
@endphp

<section class="space-y-4" data-evidence-workspace data-pemetaan='@json($pasanganPemetaan, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)' data-bukti-pasangan='@json($pasanganDenganBukti, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)' data-ai-analyze-template="{{ route('asesmen.bukti.analisis-ai', [$asesmen, '__BUKTI__']) }}">
    <div class="flex items-center justify-between gap-4">
        <h2 class="font-display text-2xl text-on-surface">Bukti Penilaian</h2>
        @if ($bisaEdit && config('ai.aktif'))
            <form
                method="POST"
                action=""
                class="js-evidence-ai-form js-ai-processing-form hidden shrink-0"
                data-ai-mode="incremental"
            >
                @csrf
                <input type="hidden" name="id_kompetensi" value="" class="js-evidence-ai-kompetensi">
                <input type="hidden" name="id_alat_penilaian" value="" class="js-evidence-ai-alat">
                <input type="hidden" name="bukti" value="" class="js-evidence-ai-bukti">
                <button type="submit" class="flex items-center gap-2 rounded-full bg-primary px-5 py-2 text-sm font-bold text-white shadow-md shadow-primary/10 transition-all hover:opacity-90">
                    <span class="material-symbols-outlined text-sm">psychology</span>
                    Analisis AI
                </button>
            </form>
        @endif
    </div>

    @if ($alatAktif->isEmpty() || $kompetensiSidebar->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Belum ada alat aktif atau kompetensi terpetakan untuk input bukti manual.
        </div>
    @else
        <div class="card-depth overflow-hidden rounded-xl border border-outline-variant/30 bg-surface-container-lowest">
            <div class="flex min-h-[32rem] flex-col lg:flex-row">
                {{-- Sidebar kompetensi --}}
                <aside class="border-b border-outline-variant/30 bg-surface-container-low/40 lg:w-72 lg:shrink-0 lg:border-b-0 lg:border-r">
                    <div class="border-b border-outline-variant/20 px-3 py-3 space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Daftar kompetensi</p>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-base text-on-surface-variant/60">search</span>
                            <input
                                type="search"
                                class="js-evidence-kompetensi-search w-full rounded-lg border border-outline-variant/40 bg-surface-container-lowest py-2 pl-9 pr-8 text-sm text-on-surface placeholder:text-on-surface-variant/60 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary/30"
                                placeholder="Cari nama atau kode…"
                                autocomplete="off"
                                aria-label="Cari kompetensi"
                            >
                            <button
                                type="button"
                                class="js-evidence-kompetensi-search-clear absolute right-1 top-1/2 hidden -translate-y-1/2 rounded p-1 text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface"
                                aria-label="Hapus pencarian"
                            >
                                <span class="material-symbols-outlined text-base">close</span>
                            </button>
                        </div>
                    </div>
                    <div class="max-h-[420px] overflow-y-auto p-2">
                        <p class="js-evidence-kompetensi-search-empty hidden px-2 py-6 text-center text-xs text-on-surface-variant">Tidak ada kompetensi yang cocok.</p>
                        @foreach ($kompetensiSidebar as $c)
                            @php
                                $definisiSingkat = strip_tags((string) $c->definisi);
                                $searchBlob = strtolower($c->nama.' '.$c->kode_kompetensi.' '.$definisiSingkat);
                            @endphp
                            <button
                                type="button"
                                class="js-evidence-pick-kompetensi mb-1 w-full rounded-lg border border-transparent px-3 py-3 text-left transition-colors hover:bg-surface-container-low"
                                data-id="{{ $c->id }}"
                                data-kode="{{ $c->kode_kompetensi }}"
                                data-nama="{{ $c->nama }}"
                                data-definisi="{{ \Illuminate\Support\Str::limit($definisiSingkat, 160) }}"
                                data-search="{{ $searchBlob }}"
                            >
                                <span class="block text-sm font-bold text-on-surface">{{ $c->nama }}</span>
                                <span class="js-evidence-kompetensi-kode mt-0.5 block font-mono text-[10px] text-primary">{{ $c->kode_kompetensi }}</span>
                                @if ($c->definisi)
                                    <span class="mt-1 block text-xs leading-snug text-on-surface-variant">{{ \Illuminate\Support\Str::limit(strip_tags((string) $c->definisi), 90) }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </aside>

                <div class="flex min-w-0 flex-1 flex-col">
                    {{-- Tab alat bukti --}}
                    <div class="flex flex-wrap gap-1 border-b border-outline-variant/30 bg-surface-container-low/30 px-4 py-2">
                        @foreach ($alatAktif as $sel)
                            @php $tool = $sel->tool; @endphp
                            <button
                                type="button"
                                class="js-evidence-pick-alat rounded-lg px-4 py-2 text-sm font-semibold text-on-surface-variant transition-colors hover:bg-surface-container-low hover:text-on-surface"
                                data-id="{{ $sel->id_alat_penilaian }}"
                                data-kode="{{ $tool?->kode }}"
                                data-nama="{{ $tool?->nama }}"
                            >
                                {{ $tool?->kode ?? '—' }}
                            </button>
                        @endforeach
                    </div>

                    <div class="flex min-h-0 flex-1 flex-col xl:flex-row">
                        {{-- Area input --}}
                        <div class="min-w-0 flex-1 p-6">
                            <div class="js-evidence-workspace-hint rounded-xl border border-dashed border-outline-variant/50 bg-surface-container-low/30 px-6 py-10 text-center text-sm text-on-surface-variant">
                                <span class="material-symbols-outlined mb-2 text-3xl text-on-surface-variant/50">touch_app</span>
                                <p class="js-evidence-hint-title font-medium text-on-surface">Pilih kompetensi di kiri, lalu pilih alat bukti di atas.</p>
                                <p class="js-evidence-hint-sub mt-1 text-xs">Form input bukti akan muncul setelah keduanya dipilih dan terpetakan.</p>
                            </div>

                            <div class="js-evidence-workspace-unmapped hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                Kompetensi dan alat yang dipilih tidak terpetakan pada matriks asesmen ini.
                            </div>

                            <div class="js-evidence-workspace-header hidden mb-4">
                                <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Kompetensi terpilih</p>
                                <h3 class="mt-1 font-display text-xl font-bold text-on-surface">
                                    <span class="js-evidence-header-nama"></span>
                                    <span class="font-mono text-base text-primary">(<span class="js-evidence-header-kode"></span>)</span>
                                </h3>
                                <p class="js-evidence-header-definisi mt-2 text-sm text-on-surface-variant"></p>
                                <p class="mt-2 text-xs text-on-surface-variant">
                                    Alat: <strong class="js-evidence-header-alat text-on-surface"></strong>
                                </p>
                            </div>

                            @if ($bisaEdit)
                                <form
                                    method="POST"
                                    action="{{ route('asesmen.bukti.store', $asesmen) }}"
                                    enctype="multipart/form-data"
                                    class="js-evidence-form js-evidence-workspace-form hidden space-y-4"
                                    data-evidence-store-form
                                    data-transcript-preview-url="{{ route('asesmen.bukti.transkrip.preview', $asesmen) }}"
                                >
                                    @csrf
                                    <input type="hidden" name="id_alat_penilaian" value="" class="js-evidence-input-alat">
                                    <input type="hidden" name="id_kompetensi" value="" class="js-evidence-input-kompetensi">

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
                                        <p class="text-xs text-on-surface-variant">Unggah rekaman wawancara, klik Transcript, atau isi transkrip manual di bawah.</p>
                                        @if (! config('stt.aktif'))
                                            <p class="rounded-lg border border-amber-200/80 bg-amber-50 px-3 py-2 text-xs text-amber-900">Transkripsi otomatis nonaktif — isi transkrip manual.</p>
                                        @endif
                                    </div>

                                    <div class="js-evidence-teks-block space-y-2">
                                        <label class="js-evidence-teks-label text-xs font-bold uppercase tracking-wider text-on-surface-variant">Teks bukti</label>
                                        <textarea
                                            name="teks_mentah"
                                            rows="6"
                                            data-normalize-preview="1"
                                            class="w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3 text-sm shadow-inner"
                                            placeholder="Tuliskan bukti observasi atau kutipan wawancara di sini…"
                                        >{{ old('teks_mentah') }}</textarea>
                                    </div>

                                    <div class="flex flex-wrap justify-end gap-2 border-t border-outline-variant/20 pt-4">
                                        <button type="button" class="js-evidence-form-reset rounded-lg border border-outline-variant/50 px-4 py-2 text-sm font-semibold text-on-surface-variant hover:bg-surface-container-low">Batal</button>
                                        <button type="submit" @disabled(! $punyaAlatTersediaInput) class="rounded-lg accent-gradient px-5 py-2 text-sm font-bold text-white hover:opacity-90 disabled:opacity-50">Simpan bukti</button>
                                    </div>
                                </form>
                            @else
                                <p class="js-evidence-workspace-readonly hidden text-sm text-on-surface-variant">Asesmen tidak dapat diubah. Pilih kompetensi dan alat untuk melihat bukti terunggah.</p>
                            @endif
                        </div>

                        {{-- Kolom kanan: bukti terunggah --}}
                        <aside class="border-t border-outline-variant/30 bg-surface-container-low/20 xl:w-80 xl:shrink-0 xl:border-l xl:border-t-0">
                            <div class="border-b border-outline-variant/20 px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Bukti terunggah</p>
                            </div>
                            <div class="max-h-[480px] overflow-y-auto p-3 space-y-3" id="evidence-uploaded-list">
                                @forelse ($semuaBukti as $b)
                                    @include('assessments.partials.evidence-uploaded-card', [
                                        'b' => $b,
                                        'asesmen' => $asesmen,
                                        'bisaEdit' => $bisaEdit,
                                        'isDraft' => $isDraft,
                                    ])
                                @empty
                                    <p class="js-evidence-uploaded-empty px-2 py-6 text-center text-xs text-on-surface-variant">Belum ada bukti.</p>
                                @endforelse
                                <p class="js-evidence-uploaded-filter-empty hidden px-2 py-6 text-center text-xs text-on-surface-variant">Belum ada bukti untuk kombinasi kompetensi dan alat ini.</p>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <dialog id="modal-evidence-preview" data-competency-modal data-modal-size="xl" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
        <div class="competency-modal__form">
            <header class="competency-modal__header flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant js-evidence-preview-modal-meta">—</p>
                    <h3 class="mt-1 text-lg font-bold text-on-surface js-evidence-preview-modal-title">Preview bukti</h3>
                    <p class="mt-0.5 text-sm text-on-surface-variant js-evidence-preview-modal-kompetensi"></p>
                </div>
                <button type="button" data-close-modal="modal-evidence-preview" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>
            <div class="competency-modal__body space-y-4">
                <div class="js-evidence-preview-modal-ai hidden rounded-lg border border-primary/20 bg-primary-fixed/30 p-3 text-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-primary">Hasil analisis AI</p>
                    <p class="mt-1 font-semibold text-on-surface js-evidence-preview-modal-ai-tingkat"></p>
                    <p class="mt-2 text-on-surface-variant js-evidence-preview-modal-ai-alasan"></p>
                    <p class="mt-2 text-xs italic text-primary/90 js-evidence-preview-modal-ai-kutipan"></p>
                </div>
                <div class="js-evidence-preview-modal-audio hidden">
                    <button type="button" class="js-evidence-preview-modal-play inline-flex items-center gap-2 rounded-lg border border-outline-variant/50 bg-surface-container-low px-3 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-lowest">
                        <span class="material-symbols-outlined text-base">play_circle</span>
                        Putar audio wawancara
                    </button>
                </div>
                <div class="max-h-[min(50dvh,24rem)] overflow-y-auto rounded-xl border border-outline-variant/30 bg-surface-container-low/40 p-4">
                    <p class="whitespace-pre-wrap text-sm leading-relaxed text-on-surface js-evidence-preview-modal-teks"></p>
                </div>
            </div>
            <footer class="competency-modal__footer flex justify-end gap-3">
                <button type="button" data-close-modal="modal-evidence-preview" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Tutup</button>
            </footer>
        </div>
    </dialog>
</section>
