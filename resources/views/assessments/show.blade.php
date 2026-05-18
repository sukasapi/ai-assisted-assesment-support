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

    <div class="space-y-gutter" data-testid="asesmen-detail">
        {{-- Section A: Header --}}
        <section class="card-depth flex flex-col items-start justify-between gap-6 rounded-xl bg-surface-container-lowest p-8 md:flex-row md:items-center">
            <div>
                <h1 class="font-display text-3xl tracking-tight text-on-surface md:text-4xl">{{ $asesmen->participant?->nama_lengkap ?? 'Peserta' }}</h1>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <span class="flex items-center gap-2 rounded-lg border border-outline-variant/50 bg-surface-container-low px-3 py-1.5 text-sm font-semibold text-on-surface-variant">
                        <span class="material-symbols-outlined text-sm">grid_view</span>
                        Matrix: {{ $matrixLabel }}
                    </span>
                    @if ($asesmen->tanpa_intray)
                        <span class="rounded-lg border border-outline-variant/50 bg-surface-container px-3 py-1.5 text-label text-on-surface-variant">Tanpa INTRAY</span>
                    @endif
                    @if ($asesmen->tujuan?->value === 'promosi')
                        <span class="rounded-lg border border-primary/20 bg-primary-fixed/30 px-3 py-1.5 text-label font-semibold text-primary">Promosi</span>
                    @endif
                </div>
            </div>
            <div class="flex flex-col items-start gap-1 md:items-end">
                <p class="text-label uppercase tracking-widest text-on-surface-variant/60">ID Asesmen</p>
                <p class="font-code text-lg font-bold text-primary">{{ $idAsesmenLabel }}</p>
            </div>
        </section>

        {{-- Section B: Status & cakupan --}}
        <section class="grid grid-cols-1 gap-gutter lg:grid-cols-3">
            <div class="card-depth flex flex-col justify-between rounded-xl bg-surface-container-lowest p-8 lg:col-span-1">
                <div>
                    <p class="mb-6 text-section-header uppercase text-on-surface-variant">Status Progress</p>
                    <div class="mb-8 flex flex-wrap items-center gap-4">
                        @if ($isFinal)
                            <span class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1.5 text-sm font-bold text-emerald-700">
                                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                                Final
                            </span>
                        @else
                            <span class="flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-4 py-1.5 text-sm font-bold text-amber-700">
                                <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">pending</span>
                                Draft
                            </span>
                        @endif
                        <span class="text-body-base font-semibold text-on-surface">{{ $persenProgress }}% Selesai</span>
                    </div>
                    @if ($isFinal && $asesmen->waktu_finalisasi)
                        <p class="mb-4 text-xs text-on-surface-variant">Difinalisasi {{ $asesmen->waktu_finalisasi->timezone(config('app.timezone'))->format('d M Y H:i') }}</p>
                    @endif
                </div>
                @can('update', $asesmen)
                    @if ($isDraft)
                        <form method="POST" action="{{ route('asesmen.finalisasi', $asesmen) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3.5 font-bold text-white shadow-lg shadow-primary/20 transition-all hover:opacity-90">
                                <span class="material-symbols-outlined">check_circle</span>
                                Finalisasi Asesmen
                            </button>
                        </form>
                    @elseif ((auth()->user()->peran ?? '') === 'admin')
                        <form method="POST" action="{{ route('asesmen.batal-finalisasi', $asesmen) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full rounded-xl border border-error/30 bg-error-container/30 py-3 text-sm font-bold text-on-error-container hover:bg-error-container/50">Batalkan Finalisasi</button>
                        </form>
                    @endif
                @endcan
            </div>

            <div class="card-depth rounded-xl bg-surface-container-lowest p-8 lg:col-span-2">
                <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-section-header uppercase text-on-surface-variant">Cakupan Kompetensi</p>
                    @if ($ringkasanFinalisasi['total_wajib'] === 0)
                        <span class="flex items-center gap-2 rounded-lg border border-outline-variant/40 bg-surface-container px-3 py-1.5 text-label font-bold text-on-surface-variant">Belum ada kompetensi wajib</span>
                    @elseif ($cakupanLengkap)
                        <span class="flex items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-label font-bold text-emerald-700">
                            <span class="material-symbols-outlined text-sm">check_circle</span> Lengkap
                        </span>
                    @else
                        <span class="flex items-center gap-2 rounded-lg border border-amber-100 bg-amber-50 px-3 py-1.5 text-label font-bold text-amber-700">
                            <span class="material-symbols-outlined text-sm">warning</span> Belum Lengkap
                        </span>
                    @endif
                </div>
                @if ($gridCakupanKompetensi === [])
                    <p class="text-sm text-on-surface-variant">Aktifkan alat preset dan pemetaan wajib di matriks untuk melihat progress per kompetensi.</p>
                @else
                    <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                        @foreach ($gridCakupanKompetensi as $item)
                            @php
                                $barColor = $item['persen'] >= 100 ? 'bg-emerald-500' : ($item['persen'] > 0 ? 'bg-amber-500' : 'bg-outline-variant/40');
                            @endphp
                            <div class="rounded-xl border border-outline-variant/30 bg-surface-container-lowest p-4 text-center">
                                <p class="mb-2 truncate text-label text-on-surface-variant" title="{{ $item['nama'] }}">{{ $item['nama'] }}</p>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-surface-container-high">
                                    <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $item['persen'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($ringkasanFinalisasi['total_kompetensi_kurang'] > 0)
                        <p class="mt-4 text-xs text-on-surface-variant">
                            {{ $ringkasanFinalisasi['total_terpenuhi'] }}/{{ $ringkasanFinalisasi['total_wajib'] }} kompetensi wajib sudah memiliki level indikator.
                        </p>
                    @endif
                @endif
            </div>
        </section>

        {{-- Section C & D: Metode & asesor --}}
        <section class="grid grid-cols-1 gap-gutter md:grid-cols-2">
            <div class="card-depth rounded-xl bg-surface-container-lowest p-8">
                <p class="mb-6 text-section-header uppercase text-on-surface-variant">Metode Pengumpulan Bukti</p>
                @can('update', $asesmen)
                    <form method="POST" action="{{ route('asesmen.metode-koleksi-bukti.update', $asesmen) }}" id="form-metode-bukti">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="metode_koleksi_bukti" id="metode_koleksi_bukti_input" value="{{ $metodeBukti->value }}">
                        <div class="flex w-fit rounded-xl bg-surface-container-low p-1.5">
                            <button type="button" data-metode="manual" class="metode-toggle px-8 py-2 text-body-base font-bold transition-colors rounded-lg {{ $metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual ? 'bg-surface-container-lowest text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">Manual</button>
                            <button type="button" data-metode="payload_alat" class="metode-toggle px-8 py-2 text-body-base transition-colors rounded-lg {{ $metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::PayloadAlat ? 'bg-surface-container-lowest text-primary shadow-sm font-bold' : 'text-on-surface-variant hover:text-on-surface' }}">Otomatis</button>
                        </div>
                    </form>
                    <script>
                        document.querySelectorAll('.metode-toggle').forEach((btn) => {
                            btn.addEventListener('click', () => {
                                document.getElementById('metode_koleksi_bukti_input').value = btn.dataset.metode;
                                document.getElementById('form-metode-bukti').submit();
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

            <div class="card-depth rounded-xl bg-surface-container-lowest p-8">
                <p class="mb-6 text-section-header uppercase text-on-surface-variant">Tim Asesor</p>
                @if ($asesmen->assessorAssignments->isEmpty())
                    <p class="text-sm text-on-surface-variant">Belum ada asesor yang ditetapkan.</p>
                @else
                    <div class="flex items-center gap-3">
                        <div class="flex -space-x-3">
                            @foreach ($asesmen->assessorAssignments->take(5) as $a)
                                <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-primary-fixed/50 ring-1 ring-outline-variant/30" title="{{ $a->user?->name }}">
                                    <span class="text-sm font-bold text-primary">{{ strtoupper(substr($a->user?->name ?? '?', 0, 1)) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <span class="ml-2 text-sm font-medium text-on-surface-variant">{{ $asesmen->assessorAssignments->count() }} Asesor Ditugaskan</span>
                    </div>
                @endif
            </div>
        </section>

        {{-- Section E: Preset alat (tabel) --}}
        <section class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest">
            <details class="group" open>
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

        {{-- Section F: Bukti manual (kartu per alat) --}}
        @if ($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual)
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
                        $punyaAi = $buktiAlat->contains(fn ($b) => $b->ai_dinilai_pada || $b->ai_alasan || $b->ai_tingkat);
                    @endphp
                    <div class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest {{ ! $adaBukti ? 'opacity-90' : '' }}">
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
                                @if ($b->ai_alasan || $b->ai_tingkat || (is_array($b->ai_muatan) && ! empty($b->ai_muatan['kutipan_dari_teks_mentah'])))
                                    <div class="ai-accent-bg relative rounded-xl border p-5 shadow-sm">
                                        <div class="absolute -top-3 left-6 rounded-full bg-primary px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-white">Hasil AI</div>
                                        <p class="pt-2 text-sm font-medium leading-relaxed">
                                            @if ($b->ai_tingkat)
                                                <span class="font-bold text-primary">Level {{ $b->ai_tingkat }}</span> —
                                            @endif
                                            {{ $b->ai_alasan ?: ($b->ai_muatan['kutipan_dari_teks_mentah'] ?? '') }}
                                        </p>
                                    </div>
                                @endif
                                <div class="space-y-3">
                                    <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                                        {{ $b->competency?->nama ?? 'Kompetensi' }}
                                        <span class="font-normal normal-case">({{ $b->competency?->kode_kompetensi }})</span>
                                    </label>
                                    <div class="min-h-[120px] rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-5 text-sm leading-relaxed text-on-surface shadow-inner whitespace-pre-wrap">{{ $b->teks_mentah }}</div>
                                </div>
                            @endforeach

                            @if (! $adaBukti)
                                <div class="flex min-h-[140px] flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-outline-variant/50 bg-surface-container-lowest p-8 text-on-surface-variant/60">
                                    <span class="material-symbols-outlined text-4xl">upload_file</span>
                                    <p class="text-sm font-medium">Belum ada bukti untuk alat ini — tambahkan di bawah.</p>
                                </div>
                            @endif

                            @can('update', $asesmen)
                                <form method="POST" action="{{ route('asesmen.bukti.store', $asesmen) }}" class="space-y-3 border-t border-outline-variant/20 pt-4">
                                    @csrf
                                    <input type="hidden" name="id_alat_penilaian" value="{{ $idAlat }}">
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Kompetensi</label>
                                        <select name="id_kompetensi" required class="mt-1 w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm">
                                            @foreach ($kompetensi as $c)
                                                <option value="{{ $c->id }}">{{ $c->kode_kompetensi }} — {{ $c->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Catatan Asesor</label>
                                        <textarea name="teks_mentah" rows="4" required data-normalize-preview="1" class="mt-1 w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3 text-sm shadow-inner" placeholder="Tuliskan bukti observasi di sini...">{{ old('teks_mentah') }}</textarea>
                                    </div>
                                    <button type="submit" @disabled(! $punyaAlatTersediaInput) class="rounded-lg accent-gradient px-4 py-2 text-sm font-bold text-white hover:opacity-90 disabled:opacity-50">Tambah bukti</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-on-surface-variant">Tidak ada alat aktif pada preset.</p>
                @endforelse
            </section>
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

        {{-- Section G: Perilaku kunci --}}
        <section class="space-y-6 pb-8">
            <h2 class="font-display text-2xl text-on-surface">Mapping Perilaku Kunci</h2>
            <div class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest">
                @if ($asesmen->keyBehaviors->isEmpty())
                    <p class="p-8 text-sm text-on-surface-variant">Belum ada mapping perilaku kunci.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-[1000px] w-full text-left">
                            <thead>
                                <tr class="bg-on-surface text-white">
                                    <th class="w-32 px-6 py-5 text-xs font-section-header uppercase">Alat</th>
                                    <th class="w-56 px-6 py-5 text-xs font-section-header uppercase">Kompetensi</th>
                                    <th class="w-20 px-6 py-5 text-center text-xs font-section-header uppercase">Lvl</th>
                                    <th class="px-6 py-5 text-xs font-section-header uppercase">Indikator Perilaku &amp; Reasoning</th>
                                    <th class="w-32 px-6 py-5 text-right text-xs font-section-header uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                @foreach ($asesmen->keyBehaviors as $pk)
                                    @php
                                        $dariAi = $pk->evidence && ($pk->evidence->ai_dinilai_pada || $pk->evidence->ai_tingkat);
                                        $kutipan = $pk->kutipan_referensi ?: (is_array($pk->evidence?->ai_muatan) ? ($pk->evidence->ai_muatan['kutipan_dari_teks_mentah'] ?? null) : null);
                                    @endphp
                                    <tr class="transition-colors hover:bg-surface-container-lowest {{ $dariAi ? 'bg-primary-fixed/20' : '' }}">
                                        <td class="px-6 py-8 align-top">
                                            <span class="rounded px-2 py-1 text-[10px] font-bold uppercase tracking-tighter {{ $dariAi ? 'bg-primary-fixed/50 text-primary' : 'bg-surface-container text-on-surface-variant' }}">{{ $dariAi ? 'AI-GEN' : ($pk->tool?->kode ?? '—') }}</span>
                                        </td>
                                        <td class="px-6 py-8 align-top">
                                            <p class="font-bold {{ $dariAi ? 'text-primary' : 'text-on-surface' }}">{{ $pk->competency?->nama ?? '?' }}</p>
                                            @if ($pk->competency?->group?->nama)
                                                <p class="mt-1 text-xs {{ $dariAi ? 'text-primary/70' : 'text-on-surface-variant' }}">{{ $pk->competency->group->nama }}</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-8 align-top text-center">
                                            @if ($pk->competencyLevel)
                                                <span class="rounded-lg border border-outline-variant/30 px-2.5 py-1.5 font-bold {{ $dariAi ? 'bg-primary text-white shadow-sm' : 'bg-surface-container text-on-surface' }}">{{ $pk->competencyLevel->tingkat }}</span>
                                            @else
                                                <span class="text-on-surface-variant/50">—</span>
                                            @endif
                                        </td>
                                        <td class="space-y-5 px-6 py-8 align-top">
                                            <div class="space-y-2">
                                                <p class="font-bold leading-tight text-on-surface">{{ $pk->teks_perilaku }}</p>
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
                                        <td class="px-6 py-8 align-top text-right">
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
    </div>
@endsection
