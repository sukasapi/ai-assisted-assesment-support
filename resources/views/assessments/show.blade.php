@extends('layouts.app')

@section('title', 'Detail asesmen — ' . config('app.name'))

@section('topbar_back')
    <a href="{{ route('asesmen.index') }}" class="inline-flex items-center gap-2 font-section-header text-section-header text-on-surface-variant transition-colors hover:text-primary">
        <span class="material-symbols-outlined">arrow_back</span>
        Kembali ke Daftar
    </a>
@endsection

@section('content')
    @php
        $metodeBukti = $asesmen->metode_koleksi_bukti;
        $punyaAlatTersediaInput = $alatTersediaInput->isNotEmpty();
        $statusAsesmen = $asesmen->status?->value ?? 'draf';
        $isFinal = $statusAsesmen === 'selesai_final';
        $isDraft = ! $isFinal;
        $jumlahAlatAktif = $pemilihanAlatPreset->where('aktif', true)->count();
        $cakupanLengkap = $ringkasanFinalisasi['total_kompetensi_kurang'] === 0 && $ringkasanFinalisasi['total_wajib'] > 0;
        $matrixLabel = trim(($asesmen->matrixVersion?->nama_versi ?: $asesmen->matrixVersion?->kode_versi) ?? '—');
        $idAsesmenLabel = '#ASM-' . str_pad((string) $asesmen->id, 4, '0', STR_PAD_LEFT);

        $ikonAlat = function (?string $kode): string {
            return match (strtoupper((string) $kode)) {
                'LGD' => 'groups',
                'BEI' => 'forum',
                'PA', 'PRESENTATION', 'PRES' => 'present_to_all',
                default => 'assignment',
            };
        };

        $labelStatusBukti = fn (string $s): string => match ($s) {
            'terisi' => 'Terisi',
            'belum_lengkap' => 'Belum Lengkap',
            default => 'Kosong',
        };

        $ikonStatusBukti = fn (string $s): string => match ($s) {
            'terisi' => 'check',
            'belum_lengkap' => 'edit',
            default => 'radio_button_unchecked',
        };
    @endphp

    <div class="min-w-0 space-y-4" data-testid="asesmen-detail" id="asesmen-detail-root">
        {{-- Header asesmen --}}
        <section class="card-depth rounded-xl bg-surface-container-lowest">
            <div class="flex flex-col gap-4 px-4 py-4 sm:px-6 sm:py-5 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                    <h1 class="font-display text-2xl font-bold tracking-tight text-on-surface md:text-3xl">Assessment Center</h1>
                    <p class="mt-1 truncate text-base font-semibold text-on-surface">{{ $asesmen->participant?->nama_lengkap ?? 'Peserta' }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                        @if ($isFinal)
                            <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-bold uppercase text-emerald-700">Final</span>
                        @else
                            <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-bold uppercase text-amber-700">Draft</span>
                        @endif
                        <span class="font-mono text-on-surface-variant">{{ $idAsesmenLabel }}</span>
                        <span class="text-on-surface-variant">·</span>
                        <span class="font-semibold text-on-surface">{{ $persenProgress }}% Selesai</span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if ($bisaUbahAsesmen ?? false)
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant/50 bg-surface-container-lowest px-3 py-1.5 text-xs font-semibold text-primary transition-colors hover:bg-surface-container-low"
                            data-open-modal="modal-asesmen-ubah"
                            data-edit="{{ json_encode(['id' => $asesmen->id, 'label' => $asesmen->participant?->nama_lengkap ?? 'Asesmen', 'id_peserta' => $asesmen->id_peserta, 'id_versi_matriks' => $asesmen->id_versi_matriks, 'tujuan' => $asesmen->tujuan?->value, 'metode_koleksi_bukti' => $asesmen->metode_koleksi_bukti?->value, 'tanpa_intray' => $asesmen->tanpa_intray, 'id_template_prompt_ai' => $asesmen->id_template_prompt_ai, 'id_asesor' => $asesmen->assessorAssignments->pluck('id_pengguna')->all()], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
                        >
                            <span class="material-symbols-outlined text-sm">edit</span>
                            Ubah asesmen
                        </button>
                    @endif
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant/50 bg-surface-container-low px-3 py-1.5 text-xs font-semibold text-on-surface-variant">
                        <span class="material-symbols-outlined text-sm">grid_view</span>
                        {{ $matrixLabel }}
                    </span>
                    @if ($asesmen->tujuan?->value === 'promosi')
                        <span class="inline-flex items-center gap-1 rounded-lg border border-amber-200/80 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800">
                            <span class="material-symbols-outlined text-sm">military_tech</span>
                            Promosi
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-lg border border-outline-variant/50 bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface-variant">Pemetaan talenta</span>
                    @endif
                    @if ($asesmen->tanpa_intray)
                        <span class="rounded-lg border border-outline-variant/50 px-3 py-1.5 text-xs text-on-surface-variant">Tanpa INTRAY</span>
                    @endif
                </div>
            </div>
        </section>

        {{-- Navigasi tab --}}
        <section class="asesmen-tab-nav-card card-depth rounded-xl bg-surface-container-lowest">
            <nav class="asesmen-tab-nav flex gap-4 overflow-x-auto px-4 sm:gap-6 sm:px-6 xl:gap-8 xl:px-8" aria-label="Bagian asesmen" id="asesmen-tab-nav">
                @foreach ([
                    ['id' => 'overview', 'label' => 'Overview', 'no' => 1],
                    ['id' => 'konfigurasi', 'label' => 'Konfigurasi Bukti', 'no' => 2],
                    ['id' => 'pengumpulan', 'label' => 'Pengumpulan Bukti', 'no' => 3],
                    ['id' => 'hasil-mapping', 'label' => 'Hasil Mapping', 'no' => 4],
                ] as $tab)
                    <button type="button" class="asesmen-tab-nav__btn {{ $loop->first ? 'is-active' : '' }}" data-asesmen-tab="{{ $tab['id'] }}">
                        <span class="asesmen-tab-nav__dot">{{ $tab['no'] }}</span>
                        <span class="asesmen-tab-nav__label">{{ $tab['label'] }}</span>
                    </button>
                @endforeach
            </nav>
        </section>

        {{-- Konten tab --}}
        <section class="card-depth min-w-0 rounded-xl bg-surface-container-lowest p-4 sm:p-6 xl:p-8" id="asesmen-tab-content-card">

        {{-- Tab: Overview --}}
        <div data-asesmen-panel="overview" class="asesmen-tab-panel min-w-0">
            <section class="asesmen-overview-grid grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:col-span-4 xl:grid-cols-1 2xl:col-span-3">
                    <div class="card-depth flex min-w-0 flex-col rounded-xl bg-surface-container-lowest p-4 sm:p-5 xl:p-7">
                        <p class="mb-4 text-xs font-bold uppercase tracking-widest text-on-surface-variant xl:mb-5">Status Progress</p>
                        <div class="asesmen-overview-progress-box mb-6 xl:mb-8">
                            <p class="font-display text-4xl font-bold text-primary sm:text-5xl">{{ $persenProgress }}%</p>
                            <p class="mt-2 text-sm text-on-surface-variant">{{ $isFinal ? 'Asesmen difinalisasi' : 'Asesmen dimulai' }}</p>
                        </div>
                        @if ($isFinal && $asesmen->waktu_finalisasi)
                            <p class="mb-4 text-center text-xs text-on-surface-variant xl:mb-5">Difinalisasi {{ $asesmen->waktu_finalisasi->timezone(config('app.timezone'))->format('d M Y H:i') }}</p>
                        @endif
                        @can('update', $asesmen)
                            @if ($isDraft)
                                <form method="POST" action="{{ route('asesmen.finalisasi', $asesmen) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-3 py-3 text-xs font-bold text-white shadow-md shadow-primary/15 transition-opacity hover:opacity-90 sm:px-4 sm:py-3.5 sm:text-sm">
                                        <span class="material-symbols-outlined shrink-0 text-base">check_circle</span>
                                        <span class="text-center leading-snug">Finalisasi Asesmen</span>
                                    </button>
                                </form>
                            @elseif ((auth()->user()->peran ?? '') === 'admin')
                                <form method="POST" action="{{ route('asesmen.batal-finalisasi', $asesmen) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="w-full rounded-xl border border-error/30 bg-error-container/30 px-3 py-2.5 text-xs font-bold text-on-error-container hover:bg-error-container/50 sm:text-sm">Batalkan Finalisasi</button>
                                </form>
                            @endif
                        @endcan
                    </div>

                    <div class="card-depth min-w-0 rounded-xl bg-surface-container-lowest p-4 sm:p-5 xl:p-7">
                        <p class="mb-4 text-xs font-bold uppercase tracking-widest text-on-surface-variant xl:mb-5">Tim Asesor</p>
                        @if ($asesmen->assessorAssignments->isEmpty())
                            <p class="text-sm text-on-surface-variant">Belum ada asesor yang ditetapkan.</p>
                        @else
                            <div class="space-y-3">
                                @foreach ($asesmen->assessorAssignments as $a)
                                    <div class="asesmen-assessor-strip" title="{{ $a->user?->name }}">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full border-2 border-white bg-primary text-xs font-bold text-white shadow-sm">
                                            {{ strtoupper(substr($a->user?->name ?? '?', 0, 1)) }}
                                        </div>
                                        <span class="min-w-0 truncate text-sm font-semibold text-on-surface">{{ $a->user?->name ?? 'Asesor' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-depth min-w-0 rounded-xl bg-surface-container-lowest p-4 sm:p-5 xl:col-span-8 xl:p-7 2xl:col-span-9">
                    <div class="mb-5 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Cakupan Kompetensi</p>
                            @if ($gridCakupanKompetensi !== [])
                                <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">
                                    {{ $ringkasanFinalisasi['total_terpenuhi'] }}/{{ $ringkasanFinalisasi['total_wajib'] }} kompetensi wajib sudah memiliki perilaku kunci <strong class="text-on-surface">disahkan</strong>.
                                </p>
                            @endif
                        </div>
                        @if ($ringkasanFinalisasi['total_wajib'] === 0)
                            <span class="rounded-lg border border-outline-variant/40 bg-surface-container px-3 py-1.5 text-xs font-bold text-on-surface-variant">Belum ada kompetensi wajib</span>
                        @elseif ($cakupanLengkap)
                            <span class="flex items-center gap-1.5 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                                <span class="material-symbols-outlined text-sm">check_circle</span> Lengkap
                            </span>
                        @else
                            <span class="flex items-center gap-1.5 rounded-lg border border-amber-100 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700">
                                <span class="material-symbols-outlined text-sm">warning</span> Belum Lengkap
                            </span>
                        @endif
                    </div>
                    @if ($gridCakupanKompetensi === [])
                        <p class="text-sm text-on-surface-variant">Aktifkan alat preset dan pemetaan wajib di matriks untuk melihat progress per kompetensi.</p>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-3 2xl:grid-cols-4">
                            @foreach ($gridCakupanKompetensi as $item)
                                @php
                                    $barColor = ($item['terpenuhi'] ?? false)
                                        ? 'bg-emerald-500'
                                        : (($item['ada_pk'] ?? false) ? 'bg-amber-500' : 'bg-primary/25');
                                    $borderKelas = ($item['terpenuhi'] ?? false)
                                        ? 'border-emerald-200/80'
                                        : (($item['ada_pk'] ?? false) ? 'border-amber-300 ring-1 ring-amber-200/60' : 'border-outline-variant/25');
                                @endphp
                                <div class="min-w-0 rounded-xl border {{ $borderKelas }} bg-surface-container-lowest p-3 text-center shadow-sm sm:p-4" title="{{ $item['kode'] }} — {{ $item['nama'] }}">
                                    <p class="font-mono text-[10px] font-bold uppercase text-primary sm:text-[11px]">{{ $item['kode'] }}</p>
                                    <p class="mb-2 line-clamp-2 min-h-[2.25rem] text-[10px] leading-snug text-on-surface-variant sm:mb-3 sm:min-h-[2.75rem] sm:text-[11px]">{{ $item['nama'] }}</p>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-container-high">
                                        <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $item['persen'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>

        {{-- Tab: Konfigurasi Bukti (metode & template AI) --}}
        <div data-asesmen-panel="konfigurasi" class="asesmen-tab-panel hidden space-y-6">
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-1">
            <div class="card-depth rounded-xl bg-surface-container-lowest p-8 xl:col-span-2">
                <p class="mb-6 text-section-header uppercase text-on-surface-variant">Metode Pengumpulan Bukti</p>
                @can('update', $asesmen)
                    <form
                        method="POST"
                        action="{{ route('asesmen.metode-koleksi-bukti.update', $asesmen) }}"
                        id="form-metode-bukti"
                        data-metode-awal="{{ $metodeBukti->value }}"
                        data-jumlah-payload="{{ $asesmen->toolPayloads->count() }}"
                        data-jumlah-perilaku="{{ $asesmen->keyBehaviors->count() }}"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="metode_koleksi_bukti" id="metode_koleksi_bukti_input" value="{{ $metodeBukti->value }}">
                        <input type="hidden" name="konfirmasi_ubah_metode" id="konfirmasi_ubah_metode" value="0">
                        <div class="flex w-fit rounded-xl bg-surface-container-low p-1.5">
                            <button type="button" data-metode="manual" class="metode-toggle px-8 py-2 text-body-base font-bold transition-colors rounded-lg {{ $metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">Manual</button>
                            <button type="button" data-metode="payload_alat" class="metode-toggle px-8 py-2 text-body-base transition-colors rounded-lg {{ $metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::PayloadAlat ? 'bg-surface-container-lowest text-primary shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface' }}">Otomatis</button>
                        </div>
                    </form>
                    <script>
                        document.querySelectorAll('.metode-toggle').forEach((btn) => {
                            btn.addEventListener('click', () => {
                                const form = document.getElementById('form-metode-bukti');
                                const metodeBaru = btn.dataset.metode;
                                const metodeAwal = form.dataset.metodeAwal;
                                const jumlahPayload = parseInt(form.dataset.jumlahPayload || '0', 10);
                                const jumlahPerilaku = parseInt(form.dataset.jumlahPerilaku || '0', 10);

                                document.getElementById('metode_koleksi_bukti_input').value = metodeBaru;

                                if (metodeBaru === metodeAwal) {
                                    return;
                                }

                                if (jumlahPayload > 0 || jumlahPerilaku > 0) {
                                    const labelBaru = metodeBaru === 'payload_alat' ? 'Otomatis (payload alat)' : 'Manual';
                                    window.Swal.fire({
                                        icon: 'warning',
                                        title: 'Ubah metode pengumpulan bukti?',
                                        html:
                                            '<p class="text-sm text-left">Asesmen ini sudah memiliki ' +
                                            (jumlahPayload > 0 ? '<strong>' + jumlahPayload + ' payload alat</strong>' : '') +
                                            (jumlahPayload > 0 && jumlahPerilaku > 0 ? ' dan ' : '') +
                                            (jumlahPerilaku > 0 ? '<strong>' + jumlahPerilaku + ' mapping perilaku kunci</strong>' : '') +
                                            '.</p><p class="mt-3 text-sm text-left">Indikator perilaku yang telah dimasukkan <strong>tetap tersimpan</strong>. Yang berubah hanya tampilan dan alur input bukti ke metode <strong>' +
                                            labelBaru +
                                            '</strong>.</p>',
                                        showCancelButton: true,
                                        confirmButtonText: 'Ya, ubah metode',
                                        cancelButtonText: 'Batal',
                                        confirmButtonColor: '#0058be',
                                        cancelButtonColor: '#727785',
                                    }).then((result) => {
                                        if (!result.isConfirmed) {
                                            return;
                                        }
                                        document.getElementById('konfirmasi_ubah_metode').value = '1';
                                        form.submit();
                                    });
                                    return;
                                }

                                form.submit();
                            });
                        });
                    </script>
                @else
                    <div class="flex w-fit rounded-xl bg-surface-container-low p-1.5">
                        <span class="rounded-lg bg-surface-container-lowest px-8 py-2 text-body-base font-bold text-primary shadow-sm">{{ $metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual ? 'Manual' : 'Otomatis' }}</span>
                    </div>
                @endcan
                <p class="mt-4 text-sm italic leading-relaxed text-on-surface-variant/70">
                    @if ($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual)
                        Bukti akan diinput secara manual oleh asesor melalui alat penilaian yang ditentukan.
                    @else
                        Bukti dikumpulkan lewat payload alat dan diproses dengan analisis AI bulk.
                    @endif
                </p>
                @if ($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual)
                    <div class="mt-4 flex gap-3 rounded-xl border border-primary/25 bg-primary-fixed/40 px-4 py-3 text-sm text-on-surface">
                        <span class="material-symbols-outlined shrink-0 text-primary">info</span>
                        <p>
                            Tombol <strong>Analisis AI bulk</strong> ada pada metode <strong>Otomatis</strong>.
                            Ubah toggle di atas, simpan payload alat, lalu gunakan tombol bulk pada setiap payload tersimpan.
                        </p>
                    </div>
                @endif
            </div>

            <div class="card-depth rounded-xl bg-surface-container-lowest p-8 xl:col-span-2">
                <p class="mb-2 text-section-header uppercase text-on-surface-variant">Strategi agregasi antar PK (per alat)</p>
                <p class="mb-6 text-xs text-on-surface-variant">
                    Saat satu alat punya beberapa perilaku kunci untuk satu kompetensi, tentukan cara menggabungkannya menjadi satu tingkat sebelum dirata-rata-tertimbang antar alat. Mempengaruhi hasil pratinjau — klik «Hitung ulang pratinjau» setelah mengubah.
                </p>
                @can('update', $asesmen)
                    @if ($asesmen->status === \App\Enums\AssessmentStatus::SelesaiFinal)
                        <p class="text-sm italic text-on-surface-variant/70">Asesmen sudah difinalisasi; strategi terkunci.</p>
                    @else
                        <form method="POST" action="{{ route('asesmen.strategi-agregasi.update', $asesmen) }}" class="max-w-md space-y-2">
                            @csrf
                            @method('PATCH')
                            <select
                                name="strategi_agregasi_alat"
                                class="w-full rounded-lg border border-outline-variant/40 bg-surface-container-low px-3 py-2 text-sm text-on-surface"
                                onchange="this.form.submit()"
                            >
                                @foreach ($opsiStrategiAgregasi as $opsi)
                                    <option value="{{ $opsi->value }}" @selected($strategiAgregasiAktif === $opsi)>{{ $opsi->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                @else
                    <p class="text-sm font-bold text-primary">{{ $strategiAgregasiAktif->label() }}</p>
                @endcan
                <p class="mt-4 text-sm italic leading-relaxed text-on-surface-variant/70">{{ $strategiAgregasiAktif->deskripsiSingkat() }}</p>
            </div>

            <div class="card-depth rounded-xl bg-surface-container-lowest p-8 xl:col-span-2">
                <p class="mb-2 text-section-header uppercase text-on-surface-variant">Template prompt AI</p>
                <p class="mb-6 text-xs text-on-surface-variant">Default dari master (per alat), dapat di-override per asesmen. Analisis AI memakai template sesuai alat bukti/payload.</p>
                @if (($opsiTemplatePromptAi ?? []) === [])
                    <p class="text-sm text-on-surface-variant">Belum ada template aktif. Admin dapat menambah di Master data → Template prompt AI.</p>
                @else
                    @can('update', $asesmen)
                        <form method="POST" action="{{ route('asesmen.template-prompt-ai.update', $asesmen) }}" class="mb-6 max-w-md space-y-2">
                            @csrf
                            @method('PATCH')
                            <label for="id_template_prompt_ai" class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant">Terapkan ke semua alat aktif</label>
                            <select
                                id="id_template_prompt_ai"
                                name="id_template_prompt_ai"
                                class="w-full rounded-lg border border-outline-variant/40 bg-surface-container-low px-3 py-2 text-sm text-on-surface"
                                onchange="if (confirm('Terapkan template ini ke semua alat aktif? Pengaturan per alat akan disamakan.')) { this.form.submit(); } else { this.selectedIndex = 0; }"
                            >
                                <option value="" disabled selected hidden>— pilih untuk terapkan massal —</option>
                                <option value="">Tanpa template (semua alat)</option>
                                @foreach ($opsiTemplatePromptAi as $tpl)
                                    <option value="{{ $tpl['id'] }}">{{ $tpl['nama'] }} ({{ $tpl['kode'] }})</option>
                                @endforeach
                            </select>
                        </form>

                        @if (($ringkasanTemplatePerAlat ?? []) !== [])
                            <form method="POST" action="{{ route('asesmen.template-prompt-alat.update', $asesmen) }}" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                                    <table class="min-w-full text-left text-sm">
                                        <thead class="bg-surface-container-low text-xs uppercase text-on-surface-variant">
                                            <tr>
                                                <th class="px-4 py-3">Alat</th>
                                                <th class="px-4 py-3">Default master</th>
                                                <th class="px-4 py-3">Pengaturan asesmen</th>
                                                <th class="px-4 py-3">Efektif</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant/20">
                                            @foreach ($ringkasanTemplatePerAlat as $baris)
                                                <tr>
                                                    <td class="px-4 py-3 font-semibold text-on-surface">{{ $baris['kode'] }}</td>
                                                    <td class="px-4 py-3 text-xs text-on-surface-variant">{{ $baris['master_label'] ?? '—' }}</td>
                                                    <td class="px-4 py-3">
                                                        <input type="hidden" name="prompt_alat[{{ $baris['id_alat'] }}][id_alat_penilaian]" value="{{ $baris['id_alat'] }}">
                                                        <select
                                                            name="prompt_alat[{{ $baris['id_alat'] }}][mode]"
                                                            class="js-prompt-mode mb-2 w-full min-w-[10rem] rounded-md border border-outline-variant px-2 py-1.5 text-xs"
                                                            data-alat="{{ $baris['id_alat'] }}"
                                                        >
                                                            <option value="master" @selected($baris['mode'] === 'master')>Ikuti master</option>
                                                            <option value="none" @selected($baris['mode'] === 'none')>Tanpa template</option>
                                                            <option value="custom" @selected($baris['mode'] === 'custom')>Template khusus</option>
                                                        </select>
                                                        <select
                                                            name="prompt_alat[{{ $baris['id_alat'] }}][id_template_prompt_ai]"
                                                            class="js-prompt-custom w-full min-w-[10rem] rounded-md border border-outline-variant px-2 py-1.5 text-xs {{ $baris['mode'] === 'custom' ? '' : 'hidden' }}"
                                                        >
                                                            <option value="">— pilih —</option>
                                                            @foreach ($opsiTemplatePromptAi as $tpl)
                                                                <option value="{{ $tpl['id'] }}" @selected((int) ($baris['id_template'] ?? 0) === $tpl['id'])>{{ $tpl['kode'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="px-4 py-3 text-xs text-on-surface-variant">{{ $baris['template_label'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Simpan per alat</button>
                            </form>
                            <script>
                                document.querySelectorAll('.js-prompt-mode').forEach((sel) => {
                                    const toggle = () => {
                                        const custom = sel.closest('td')?.querySelector('.js-prompt-custom');
                                        if (custom) {
                                            custom.classList.toggle('hidden', sel.value !== 'custom');
                                        }
                                    };
                                    sel.addEventListener('change', toggle);
                                    toggle();
                                });
                            </script>
                        @endif
                    @else
                        <ul class="space-y-2 text-sm text-on-surface-variant">
                            @foreach ($ringkasanTemplatePerAlat ?? [] as $baris)
                                <li><strong>{{ $baris['kode'] }}</strong>: {{ $baris['template_label'] }} <span class="text-xs">({{ $baris['mode_label'] }})</span></li>
                            @endforeach
                        </ul>
                    @endcan
                @endif
            </div>
        </section>
        </div>

        {{-- Tab: Pengumpulan Bukti --}}
        <div data-asesmen-panel="pengumpulan" class="asesmen-tab-panel hidden space-y-8">

        {{-- Preset alat (tabel) --}}
        <section class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest">
            <details class="group" close>
                <summary class="flex cursor-pointer list-none items-center justify-between p-8 transition-colors hover:bg-surface-container-low/50 [&::-webkit-details-marker]:hidden">
                    <div class="flex items-center gap-4">
                        <span class="material-symbols-outlined text-primary transition-transform group-open:rotate-180">expand_more</span>
                        <span class="text-section-header uppercase tracking-wide text-on-surface">Alat Penilaian Preset</span>
                    </div>
                    <span class="rounded-lg bg-surface-container px-3 py-1 text-sm font-bold text-on-surface-variant">{{ $jumlahAlatAktif }} Alat Terpilih</span>
                </summary>
                <div class="border-t border-outline-variant/30 px-8 pb-8 pt-6">
                    @if ($ringkasanAlatPreset === [])
                        <p class="text-sm text-on-surface-variant">Belum ada alat aktif dalam preset asesmen ini.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-surface-container-low/50">
                                        <th class="px-6 py-4 text-xs font-section-header uppercase text-on-surface">Nama Alat</th>
                                        <th class="px-6 py-4 text-xs font-section-header uppercase text-on-surface">Kompetensi yang Diukur</th>
                                        <th class="px-6 py-4 text-xs font-section-header uppercase text-on-surface">Status Bukti</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/20">
                                    @foreach ($ringkasanAlatPreset as $baris)
                                        <tr class="transition-colors hover:bg-surface-container-lowest">
                                            <td class="px-6 py-5 text-body-base font-bold text-on-surface">{{ $baris['kode'] }} — {{ $baris['nama'] }}</td>
                                            <td class="px-6 py-5 text-body-muted text-on-surface-variant">{{ $baris['kompetensi_label'] }}</td>
                                            <td class="px-6 py-5">
                                                <span class="{{ $baris['status_kelas'] }} flex items-center gap-2 text-sm font-bold">
                                                    <span class="material-symbols-outlined text-sm font-bold">{{ $ikonStatusBukti($baris['status']) }}</span>
                                                    {{ $labelStatusBukti($baris['status']) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </details>
        </section>

        @if ($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual)
            @include('assessments.partials.evidence-manual-per-alat', [
                'asesmen' => $asesmen,
                'pemilihanAlatPreset' => $pemilihanAlatPreset,
                'buktiPerAlat' => $buktiPerAlat,
                'kompetensi' => $kompetensi,
                'kelompokKompetensiMatriks' => $kelompokKompetensiMatriks,
                'pemetaanKompetensiAlat' => $pemetaanKompetensiAlat,
                'isDraft' => $isDraft,
                'isFinal' => $isFinal,
                'punyaAlatTersediaInput' => $punyaAlatTersediaInput,
                'ikonAlat' => $ikonAlat,
            ])
        @endif

        {{-- Payload otomatis --}}
        @if ($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::PayloadAlat)
            @include('assessments.partials.show-payload-alat', [
                'asesmen' => $asesmen,
                'alatTersediaInput' => $alatTersediaInput,
                'punyaAlatTersediaInput' => $punyaAlatTersediaInput,
                'aiFiturAktif' => $aiFiturAktif ?? false,
                'aiPesanNonaktif' => $aiPesanNonaktif ?? '',
                'aiModelOptions' => $aiModelOptions ?? [],
                'aiModelDefault' => $aiModelDefault ?? '',
                'aiAntrianAsync' => $aiAntrianAsync ?? false,
            ])
        @endif
        </div>

        {{-- Tab: Hasil Mapping --}}
        <div data-asesmen-panel="hasil-mapping" class="asesmen-tab-panel hidden space-y-8">
        @php
            use App\Support\KeyBehaviorPresentation;
        @endphp
        <section class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-display text-xl font-bold text-on-surface">Mapping Perilaku Kunci</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('asesmen.laporan-pdf', $asesmen) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-on-primary shadow-sm transition-colors hover:opacity-90"
                        title="Unduh laporan hasil asesmen peserta (PDF)"
                    >
                        <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                        Laporan PDF
                    </a>
                    @if (! $asesmen->keyBehaviors->isEmpty())
                        <button
                            type="button"
                            data-open-modal="modal-unduh-mapping-pk"
                            class="inline-flex items-center gap-2 rounded-lg border border-outline-variant/40 bg-surface-container-lowest px-4 py-2 text-sm font-semibold text-on-surface shadow-sm transition-colors hover:bg-surface-container-low"
                        >
                            <span class="material-symbols-outlined text-base">download</span>
                            Unduh Data
                        </button>
                    @endif
                </div>
            </div>
            @error('perilaku_kunci')
                <p class="rounded-lg border border-error/30 bg-error-container/20 px-4 py-2 text-sm text-on-error-container">{{ $message }}</p>
            @enderror
            <div class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest">
                @if ($asesmen->keyBehaviors->isEmpty())
                    <p class="p-8 text-sm text-on-surface-variant">Belum ada mapping perilaku kunci.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-[1100px] w-full text-left">
                            <thead>
                                <tr class="bg-on-surface text-white">
                                    <th class="w-28 px-4 py-5 text-xs font-section-header uppercase">Alat</th>
                                    <th class="w-48 px-4 py-5 text-xs font-section-header uppercase">Kompetensi</th>
                                    <th class="w-16 px-4 py-5 text-center text-xs font-section-header uppercase">Lvl</th>
                                    <th class="w-24 px-4 py-5 text-center text-xs font-section-header uppercase">Sumber</th>
                                    <th class="w-28 px-4 py-5 text-center text-xs font-section-header uppercase">Status</th>
                                    <th class="px-4 py-5 text-xs font-section-header uppercase">Indikator Perilaku &amp; Reasoning</th>
                                    <th class="w-24 px-4 py-5 text-right text-xs font-section-header uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                @foreach ($asesmen->keyBehaviors->sortBy(fn ($p) => [$p->tervalidasi ? 1 : 0, $p->keyakinan ?? 1]) as $pk)
                                    @php
                                        $badgeSumber = KeyBehaviorPresentation::badgeSumber($pk, $idPerilakuDariBulkAi);
                                        $badgeStatus = KeyBehaviorPresentation::badgeStatus($pk);
                                        $dariAi = $badgeSumber['label'] === 'AI';
                                        $kutipan = $pk->kutipan_referensi ?: (is_array($pk->evidence?->ai_muatan) ? ($pk->evidence->ai_muatan['kutipan_dari_teks_mentah'] ?? null) : null);
                                        // E-5: keyakinan & penanda "perlu ditinjau" untuk usulan AI keyakinan rendah.
                                        $keyakinan = $pk->keyakinan;
                                        $keyakinanRendah = $keyakinan !== null && $keyakinan < ($ambangKeyakinanRendah ?? 0.5) && ! $pk->tervalidasi;
                                        // E-6: indikator resmi sebagai referensi sekunder bila teks utama berbeda.
                                        $indikatorResmi = $pk->competencyLevel?->indikator_perilaku;
                                        $tampilkanIndikator = $indikatorResmi && trim((string) $indikatorResmi) !== trim((string) $pk->teks_perilaku);
                                    @endphp
                                    <tr class="transition-colors hover:bg-surface-container-lowest {{ $dariAi ? 'bg-primary-fixed/15' : '' }} {{ ! $pk->tervalidasi ? 'opacity-95' : '' }} {{ $keyakinanRendah ? 'ring-1 ring-inset ring-amber-300/60' : '' }}">
                                        <td class="px-4 py-6 align-top">
                                            <span class="text-xs font-bold text-on-surface">{{ $pk->tool?->kode ?? '—' }}</span>
                                        </td>
                                        <td class="px-4 py-6 align-top">
                                            <p class="font-bold text-on-surface">{{ $pk->competency?->nama ?? '?' }}</p>
                                            @if ($pk->competency?->group?->nama)
                                                <p class="mt-1 text-xs {{ $dariAi ? 'text-primary/70' : 'text-on-surface-variant' }}">{{ $pk->competency->group->nama }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-6 align-top text-center">
                                            @if ($pk->competencyLevel)
                                                <span class="rounded-lg border border-outline-variant/30 bg-surface-container px-2.5 py-1.5 font-bold text-on-surface">{{ $pk->competencyLevel->tingkat }}</span>
                                            @else
                                                <span class="text-on-surface-variant/50">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-6 align-top text-center">
                                            <span class="inline-block rounded px-2 py-1 text-[10px] font-bold uppercase tracking-tighter {{ $badgeSumber['kelas'] }}">{{ $badgeSumber['label'] }}</span>
                                            @can('update', $asesmen)
                                                @if ($isDraft && ! $pk->tervalidasi)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('asesmen.perilaku.sahkan', [$asesmen, $pk]) }}"
                                                        class="js-pk-sahkan-form mt-2"
                                                    >
                                                        @csrf
                                                        @method('PATCH')
                                                        <button
                                                            type="submit"
                                                            class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-800 transition-colors hover:bg-emerald-100"
                                                            title="Simpan sebagai mapping resmi peserta"
                                                        >
                                                            Sahkan
                                                        </button>
                                                    </form>
                                                @elseif ($pk->tervalidasi)
                                                    <p class="mt-2 text-[10px] font-medium text-emerald-700">✓ Resmi</p>
                                                @endif
                                            @endcan
                                        </td>
                                        <td class="px-4 py-6 align-top text-center">
                                            <span data-pk-status-badge class="inline-block rounded-full border px-2.5 py-1 text-[10px] font-bold {{ $badgeStatus['kelas'] }}">{{ $badgeStatus['label'] }}</span>
                                            @if ($keyakinan !== null)
                                                <p class="mt-2 text-[10px] font-semibold text-on-surface-variant" title="Keyakinan AI atas usulan ini">Keyakinan {{ round($keyakinan * 100) }}%</p>
                                            @endif
                                            @if ($keyakinanRendah)
                                                <p class="mt-1 inline-flex rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-800" title="Keyakinan AI di bawah ambang — prioritaskan untuk ditinjau">perlu ditinjau</p>
                                            @endif
                                        </td>
                                        <td class="space-y-4 px-4 py-6 align-top">
                                            <div class="space-y-2">
                                                <p class="font-bold leading-tight text-on-surface">{{ $pk->teks_perilaku }}</p>
                                                @if ($tampilkanIndikator)
                                                    <p class="text-xs leading-relaxed text-on-surface-variant/70">
                                                        <span class="font-semibold">Indikator resmi (L{{ $pk->competencyLevel->tingkat }}):</span> {{ $indikatorResmi }}
                                                    </p>
                                                @endif
                                                @if ($pk->alasan_pemilihan)
                                                    <p class="text-sm leading-relaxed text-on-surface-variant">{{ $pk->alasan_pemilihan }}</p>
                                                @endif
                                            </div>
                                            @if ($kutipan)
                                                <div class="rounded-r-lg border-l-4 border-primary/50 bg-surface-container-low p-4 text-sm italic text-on-surface-variant shadow-sm">
                                                    "{{ $kutipan }}"
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-6 align-top text-right">
                                            @can('update', $asesmen)
                                                <a href="{{ route('asesmen.perilaku.edit', [$asesmen, $pk]) }}" class="inline-flex items-center gap-1 rounded-lg bg-surface-container p-2 text-on-surface-variant transition-all hover:text-primary" title="Edit">
                                                    <span class="material-symbols-outlined text-sm">edit</span>
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('update', $asesmen)
                    <details class="border-t border-outline-variant/20 bg-surface-container-low/30">
                        <summary class="flex cursor-pointer list-none items-center justify-center gap-3 p-6 text-sm font-bold text-on-surface-variant transition-all hover:text-primary [&::-webkit-details-marker]:hidden">
                            <span class="material-symbols-outlined">add_circle</span>
                            Tambah Mapping Perilaku
                        </summary>
                        <form method="POST" action="{{ route('asesmen.perilaku.store', $asesmen) }}" class="space-y-4 border-t border-outline-variant/20 px-8 pb-8 pt-4">
                            @csrf
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="id_alat_penilaian_pk" class="text-xs font-medium text-on-surface-variant">Alat</label>
                                    <select name="id_alat_penilaian" id="id_alat_penilaian_pk" required @disabled(! $punyaAlatTersediaInput) class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                                        @foreach ($alatTersediaInput as $sel)
                                            <option value="{{ $sel->id_alat_penilaian }}">{{ $sel->tool?->kode }} — {{ $sel->tool?->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="id_kompetensi_pk" class="text-xs font-medium text-on-surface-variant">Kompetensi</label>
                                    <select name="id_kompetensi" id="id_kompetensi_pk" required class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                                        @foreach ($kompetensi as $c)
                                            <option value="{{ $c->id }}">{{ $c->kode_kompetensi }} — {{ $c->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="id_bukti_penilaian" class="text-xs font-medium text-on-surface-variant">Bukti (opsional)</label>
                                    <select name="id_bukti_penilaian" id="id_bukti_penilaian" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                                        <option value="">— tidak ada —</option>
                                        @foreach ($asesmen->evidenceItems as $b)
                                            <option value="{{ $b->id }}">#{{ $b->id }} {{ $b->tool?->kode }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="id_tingkat_kompetensi" class="text-xs font-medium text-on-surface-variant">Tingkat (opsional)</label>
                                    <select name="id_tingkat_kompetensi" id="id_tingkat_kompetensi" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                                        <option value="">— tidak ada —</option>
                                        @foreach ($tingkatKompetensi as $tk)
                                            <option value="{{ $tk->id }}">{{ $tk->competency?->kode_kompetensi }} · Level {{ $tk->tingkat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label for="teks_perilaku" class="text-xs font-medium text-on-surface-variant">Teks perilaku</label>
                                <textarea name="teks_perilaku" id="teks_perilaku" rows="3" required class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">{{ old('teks_perilaku') }}</textarea>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="alasan_pemilihan_pk" class="text-xs font-medium text-on-surface-variant">Alasan / reasoning</label>
                                    <textarea name="alasan_pemilihan" id="alasan_pemilihan_pk" rows="2" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">{{ old('alasan_pemilihan') }}</textarea>
                                </div>
                                <div>
                                    <label for="kutipan_referensi_pk" class="text-xs font-medium text-on-surface-variant">Kutipan referensi</label>
                                    <textarea name="kutipan_referensi" id="kutipan_referensi_pk" rows="2" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm" placeholder="Potongan teks dari bukti">{{ old('kutipan_referensi') }}</textarea>
                                </div>
                            </div>
                            <button type="submit" @disabled(! $punyaAlatTersediaInput) class="rounded-lg accent-gradient px-4 py-2 text-sm font-bold text-white hover:opacity-90 disabled:opacity-50">Simpan mapping</button>
                        </form>
                    </details>
                @endcan
            </div>
        </section>

        @include('assessments.partials.integration-preview', [
            'asesmen' => $asesmen,
            'integrasiPratinjau' => $integrasiPratinjau ?? collect(),
            'isFinal' => $isFinal,
            'isDraft' => $isDraft,
            'pratinjauKedaluwarsa' => $pratinjauKedaluwarsa ?? false,
            'strategiAgregasiAktif' => $strategiAgregasiAktif,
        ])
        </div>

        </section>
    </div>

    <script>
        (function () {
            const root = document.getElementById('asesmen-detail-root');
            if (!root) return;

            const buttons = root.querySelectorAll('[data-asesmen-tab]');
            const panels = root.querySelectorAll('[data-asesmen-panel]');
            const valid = ['overview', 'konfigurasi', 'pengumpulan', 'hasil-mapping'];
            const hashAliases = {
                mapping: 'hasil-mapping',
                ai: 'konfigurasi',
                evidence: 'pengumpulan',
            };

            const normalizeTab = (id) => {
                if (!id) return null;
                const mapped = hashAliases[id] ?? id;
                return valid.includes(mapped) ? mapped : null;
            };

            const getActiveTab = () => {
                const active = root.querySelector('.asesmen-tab-nav__btn.is-active');
                return active?.dataset.asesmenTab ?? 'overview';
            };

            const activate = (id) => {
                const tab = normalizeTab(id);
                if (!tab) return;
                buttons.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.asesmenTab === tab));
                panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.asesmenPanel !== tab));
            };

            buttons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.asesmenTab;
                    activate(id);
                    const base = window.location.pathname + window.location.search;
                    if (id !== 'overview') {
                        history.replaceState(null, '', base + '#' + id);
                    } else {
                        history.replaceState(null, '', base);
                    }
                });
            });

            root.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', () => {
                    let field = form.querySelector('[name="asesmen_tab"]');
                    if (!field) {
                        field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = 'asesmen_tab';
                        form.appendChild(field);
                    }
                    field.value = getActiveTab();
                });
            });

            const oldTab = normalizeTab(@json(old('asesmen_tab')));
            const hashTab = normalizeTab((window.location.hash || '').replace('#', ''));
            activate(oldTab ?? hashTab ?? 'overview');
        })();
    </script>

    @if ($bisaUbahAsesmen ?? false)
        @include('assessments.partials.asesmen-form-modals')
    @endif

    @if (! $asesmen->keyBehaviors->isEmpty())
        @include('assessments.partials.unduh-mapping-modal', ['asesmen' => $asesmen])
    @endif
@endsection
