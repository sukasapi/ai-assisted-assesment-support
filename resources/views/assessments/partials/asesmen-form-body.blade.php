@props([
    'formKey',
    'peserta',
    'versiMatriks',
    'asesorKandidat',
    'opsiTemplatePromptAi' => [],
    'selectedAsesorIds' => [],
    'selectedPesertaIds' => [],
    'values' => [],
    'multiPeserta' => false,
])

@php
    $val = fn (string $key, mixed $default = '') => old($key, $values[$key] ?? $default);
    $metodeDefault = $val('metode_koleksi_bukti', 'manual');
    $asesorTerpilih = collect(old('id_asesor', $selectedAsesorIds))->map(fn ($id) => (int) $id)->unique()->values();
    $pesertaTerpilih = collect(old('id_peserta', $multiPeserta ? $selectedPesertaIds : [$val('id_peserta')]))
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();
    $pesertaById = $peserta->keyBy('id');
    $asesorById = $asesorKandidat->keyBy('id');
@endphp

<div class="asesmen-form-page space-y-6">
    <section class="asesmen-form-card">
        <header class="asesmen-form-card__header">
            <span class="material-symbols-outlined asesmen-form-card__icon">groups</span>
            <h2 class="asesmen-form-card__title">Peserta &amp; Matriks</h2>
        </header>
        <div class="asesmen-form-card__body space-y-5">
            <div class="grid gap-5 md:grid-cols-2">
                <div data-peserta-picker-wrap="{{ $formKey }}">
                    <span class="asesmen-form-label">Peserta</span>
                    @if ($multiPeserta)
                        <button
                            type="button"
                            class="asesmen-peserta-trigger mt-1.5 w-full text-left"
                            data-peserta-picker-trigger="{{ $formKey }}"
                            aria-haspopup="dialog"
                        >
                            <span class="material-symbols-outlined text-lg text-on-surface-variant">group_add</span>
                            <span data-peserta-trigger-label="{{ $formKey }}">
                                @if ($pesertaTerpilih->isEmpty())
                                    Klik untuk memilih peserta…
                                @else
                                    {{ $pesertaTerpilih->count() }} peserta dipilih
                                @endif
                            </span>
                        </button>
                        <div class="mt-2 flex flex-wrap gap-2" data-peserta-selected-list="{{ $formKey }}">
                            @foreach ($pesertaTerpilih as $idPeserta)
                                @php $p = $pesertaById->get($idPeserta); @endphp
                                @if ($p)
                                    <span class="asesmen-peserta-chip" data-peserta-chip="{{ $idPeserta }}">
                                        <input type="hidden" name="id_peserta[]" value="{{ $idPeserta }}">
                                        <span>{{ $p->nama_lengkap }}</span>
                                        <button type="button" data-peserta-chip-remove="{{ $idPeserta }}" aria-label="Hapus {{ $p->nama_lengkap }}">
                                            <span class="material-symbols-outlined text-sm">close</span>
                                        </button>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <select name="id_peserta" id="{{ $formKey }}-id-peserta" required class="asesmen-form-select mt-1.5">
                            <option value="">— pilih —</option>
                            @foreach ($peserta as $p)
                                <option value="{{ $p->id }}" @selected((int) $val('id_peserta') === $p->id)>{{ $p->nama_lengkap }} ({{ $p->kode_peserta }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div>
                    <label for="{{ $formKey }}-id-versi" class="asesmen-form-label">Versi matriks</label>
                    <select name="id_versi_matriks" id="{{ $formKey }}-id-versi" required class="asesmen-form-select">
                        <option value="">— pilih —</option>
                        @foreach ($versiMatriks as $v)
                            <option value="{{ $v->id }}" @selected((int) $val('id_versi_matriks') === $v->id)>{{ $v->kode_versi }} — {{ $v->nama_versi }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="{{ $formKey }}-tujuan" class="asesmen-form-label">Tujuan</label>
                <select name="tujuan" id="{{ $formKey }}-tujuan" required class="asesmen-form-select">
                    <option value="pemetaan_talenta" @selected($val('tujuan', 'pemetaan_talenta') === 'pemetaan_talenta')>Pemetaan talenta</option>
                    <option value="promosi" @selected($val('tujuan') === 'promosi')>Promosi</option>
                </select>
            </div>
        </div>
    </section>

    <section class="asesmen-form-card">
        <header class="asesmen-form-card__header">
            <span class="material-symbols-outlined asesmen-form-card__icon">fact_check</span>
            <h2 class="asesmen-form-card__title">Cara Mengumpulkan Bukti</h2>
        </header>
        <div class="asesmen-form-card__body">
            <p class="text-sm text-on-surface-variant">Menentukan formulir mana yang aktif di halaman detail asesmen (dapat diubah nanti).</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2" data-metode-koleksi-group>
                <label class="asesmen-metode-card {{ $metodeDefault === 'manual' ? 'asesmen-metode-card--active' : '' }}">
                    <input type="radio" name="metode_koleksi_bukti" value="manual" class="sr-only" @checked($metodeDefault === 'manual')>
                    <span class="asesmen-metode-card__check material-symbols-outlined">check_circle</span>
                    <span class="asesmen-metode-card__title">Manual</span>
                    <span class="asesmen-metode-card__subtitle">— bukti per kompetensi</span>
                    <span class="asesmen-metode-card__desc">Input bukti per alat dan kompetensi, analisis AI per baris bukti.</span>
                </label>
                <label class="asesmen-metode-card {{ $metodeDefault === 'payload_alat' ? 'asesmen-metode-card--active' : '' }}">
                    <input type="radio" name="metode_koleksi_bukti" value="payload_alat" class="sr-only" @checked($metodeDefault === 'payload_alat')>
                    <span class="asesmen-metode-card__check material-symbols-outlined">check_circle</span>
                    <span class="asesmen-metode-card__title">Otomatis</span>
                    <span class="asesmen-metode-card__subtitle">— payload alat + AI bulk</span>
                    <span class="asesmen-metode-card__desc">Unggah teks muatan per alat, hasil wajib direview asesor.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="asesmen-form-card">
        <header class="asesmen-form-card__header">
            <span class="material-symbols-outlined asesmen-form-card__icon">settings</span>
            <h2 class="asesmen-form-card__title">Pengaturan Lainnya</h2>
        </header>
        <div class="asesmen-form-card__body space-y-5">
            @if (($opsiTemplatePromptAi ?? []) !== [])
                <div>
                    <label for="{{ $formKey }}-template" class="asesmen-form-label">Template prompt AI — terapkan ke semua alat (opsional)</label>
                    <p class="mt-0.5 text-xs text-on-surface-variant">Menerapkan template yang sama ke tiap alat aktif. Di detail asesmen dapat diatur per alat atau ikuti default master (mis. STAR hanya BEI).</p>
                    <select name="id_template_prompt_ai" id="{{ $formKey }}-template" class="asesmen-form-select mt-2">
                        <option value="">— tanpa template tambahan —</option>
                        @foreach ($opsiTemplatePromptAi as $tpl)
                            <option value="{{ $tpl['id'] }}" @selected((int) $val('id_template_prompt_ai') === (int) $tpl['id'])>
                                {{ $tpl['nama'] }} ({{ $tpl['kode'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="rounded-xl border border-outline-variant/40 bg-surface-container-low/60 p-4">
                <input type="hidden" name="tanpa_intray" value="0">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="tanpa_intray" value="1" class="mt-0.5 size-4 rounded border-outline-variant text-primary focus:ring-primary/20" @checked((bool) $val('tanpa_intray'))>
                    <span>
                        <span class="block text-sm font-semibold text-on-surface">Tanpa INTRAY (mis. BOD-3)</span>
                        <span class="mt-0.5 block text-xs text-on-surface-variant">Mematikan modul intray untuk asesmen level eksekutif spesifik.</span>
                    </span>
                </label>
            </div>

            <div data-asesor-picker-wrap="{{ $formKey }}">
                <span class="asesmen-form-label">Admin penilai (opsional)</span>
                <p class="mt-0.5 text-xs text-on-surface-variant">Hanya akun admin; pembuat asesmen otomatis ditambahkan.</p>
                <button
                    type="button"
                    class="asesmen-asesor-add mt-3 w-full"
                    data-asesor-picker-trigger="{{ $formKey }}"
                    aria-haspopup="dialog"
                >
                    <span class="material-symbols-outlined text-lg">person_add</span>
                    <span data-asesor-trigger-label="{{ $formKey }}">
                        @if ($asesorTerpilih->isEmpty())
                            Tambah Admin Penilai
                        @else
                            {{ $asesorTerpilih->count() }} admin dipilih
                        @endif
                    </span>
                </button>
                <div class="mt-2 flex flex-wrap gap-2" data-asesor-selected-list="{{ $formKey }}">
                    @foreach ($asesorTerpilih as $idAsesor)
                        @php $u = $asesorById->get($idAsesor); @endphp
                        @if ($u)
                            <span class="asesmen-peserta-chip" data-asesor-chip="{{ $idAsesor }}">
                                <input type="hidden" name="id_asesor[]" value="{{ $idAsesor }}">
                                <span>{{ $u->nama }} <span class="font-normal text-on-surface-variant">({{ strtoupper($u->peran) }})</span></span>
                                <button type="button" data-asesor-chip-remove="{{ $idAsesor }}" aria-label="Hapus {{ $u->nama }}">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>
