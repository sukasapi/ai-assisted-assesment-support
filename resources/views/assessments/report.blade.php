@php
    use App\Enums\AssessmentStatus;
    use App\Enums\AssessmentPurpose;

    $labelTujuan = match ($asesmen->tujuan?->value) {
        'promosi' => 'Promosi',
        'pemetaan_talenta' => 'Pemetaan Talenta',
        default => '—',
    };
    $labelStatus = match ($asesmen->status?->value) {
        'draf' => 'Draf',
        'berlangsung' => 'Berlangsung',
        'terintegrasi' => 'Terintegrasi (pratinjau dihitung)',
        'selesai_final' => 'Selesai (Final)',
        default => (string) ($asesmen->status?->value ?? '—'),
    };
    $labelRekomKomp = fn (?string $k): string => match ($k) {
        'fit' => 'Fit',
        'development' => 'Development',
        'not_fit' => 'Not Fit',
        default => '—',
    };
    $jobFit = $asesmen->job_fit_persen_pratinjau;
    $kodeAgregat = $asesmen->kode_rekomendasi_agregat;
    $asesorNama = $asesmen->assessorAssignments
        ->map(fn ($a) => $a->user?->nama)
        ->filter()
        ->implode(', ');
    $tz = config('app.timezone');
    $fmt = fn ($d) => $d ? $d->timezone($tz)->format('d M Y H:i') : '—';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Asesmen — {{ $asesmen->participant?->nama_lengkap ?? 'Peserta' }}</title>
    <style>
        * { font-family: "DejaVu Sans", sans-serif; }
        @page { margin: 26mm 16mm 22mm 16mm; }
        body { font-size: 10px; color: #1a1c1e; line-height: 1.45; }
        h1 { font-size: 16px; margin: 0; color: #0058be; letter-spacing: .3px; }
        h2 { font-size: 11px; margin: 18px 0 6px; color: #0058be; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1.5px solid #0058be; padding-bottom: 3px; }
        .sub { font-size: 9px; color: #6b7280; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 6px; vertical-align: top; }
        .meta td.k { width: 32%; color: #6b7280; }
        .meta td.v { font-weight: bold; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .grid th { background: #0058be; color: #fff; font-size: 8.5px; text-transform: uppercase; padding: 6px 5px; text-align: left; }
        .grid td { border-bottom: 1px solid #e5e7eb; padding: 5px; vertical-align: top; }
        .grid td.c, .grid th.c { text-align: center; }
        .chip { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; font-weight: bold; border: 1px solid; }
        .fit { background: #ecfdf5; color: #065f46; border-color: #6ee7b7; }
        .dev { background: #fffbeb; color: #92400e; border-color: #fcd34d; }
        .nofit { background: #fef2f2; color: #991b1b; border-color: #fca5a5; }
        .muted { color: #9ca3af; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-top: 6px; }
        .summary .box { border: 1px solid #c7d2fe; background: #eef2ff; padding: 10px 12px; }
        .summary .lbl { font-size: 8px; text-transform: uppercase; color: #4f46e5; font-weight: bold; }
        .summary .big { font-size: 20px; font-weight: bold; color: #1e3a8a; }
        .badge-ok { color: #065f46; font-weight: bold; }
        .badge-no { color: #991b1b; font-weight: bold; }
        .pk { margin: 4px 0 10px; padding: 6px 8px; border-left: 3px solid #c7d2fe; background: #f8fafc; }
        .pk .tool { font-weight: bold; color: #0058be; }
        .pk .quote { font-style: italic; color: #4b5563; margin-top: 3px; }
        .pk .ind { color: #6b7280; font-size: 9px; margin-top: 2px; }
        .footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8px; color: #9ca3af; border-top: .5px solid #e5e7eb; padding-top: 4px; }
        .footer .pg:after { content: counter(page) " / " counter(pages); }
        .header { position: fixed; top: -18mm; left: 0; right: 0; font-size: 8px; color: #9ca3af; border-bottom: .5px solid #e5e7eb; padding-bottom: 4px; }
        .disc { background: #fffbeb; border: 1px solid #fcd34d; padding: 6px 8px; font-size: 8.5px; color: #92400e; margin-top: 6px; }
        .pk-group-title { font-weight: bold; margin: 10px 0 2px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        {{ config('app.name') }} · Laporan Asesmen #ASM-{{ str_pad((string) $asesmen->id, 4, '0', STR_PAD_LEFT) }}
    </div>
    <div class="footer">
        <table style="width:100%"><tr>
            <td>Dokumen pratinjau — bukan nilai final perusahaan. Dicetak oleh {{ $dicetakOleh }} pada {{ $fmt($dicetakPada) }}.</td>
            <td style="text-align:right" class="pg"></td>
        </tr></table>
    </div>

    <h1>Laporan Hasil Asesmen Kompetensi</h1>
    <p class="sub">Hasil di bawah merupakan <strong>pratinjau</strong> integrasi berbasis perilaku kunci yang disahkan asesor; keputusan akhir tetap pada kewenangan asesor / perusahaan.</p>

    <h2>Identitas Peserta</h2>
    <table class="meta">
        <tr><td class="k">Nama</td><td class="v">{{ $asesmen->participant?->nama_lengkap ?? '—' }}</td>
            <td class="k">Kode peserta</td><td class="v">{{ $asesmen->participant?->kode_peserta ?? '—' }}</td></tr>
        <tr><td class="k">Jabatan</td><td class="v">{{ $asesmen->participant?->jabatan ?? '—' }}</td>
            <td class="k">Pendidikan</td><td class="v">{{ $asesmen->participant?->pendidikan ?? '—' }}</td></tr>
        <tr><td class="k">Email</td><td class="v">{{ $asesmen->participant?->alamat_surel ?? '—' }}</td>
            <td class="k">Versi matriks</td><td class="v">{{ $asesmen->matrixVersion?->nama_versi ?: $asesmen->matrixVersion?->kode_versi ?? '—' }}</td></tr>
    </table>

    <h2>Informasi Asesmen</h2>
    <table class="meta">
        <tr><td class="k">Tujuan</td><td class="v">{{ $labelTujuan }}</td>
            <td class="k">Status</td><td class="v">{{ $labelStatus }}</td></tr>
        <tr><td class="k">Strategi agregasi</td><td class="v">{{ $strategiAgregasi }}</td>
            <td class="k">Pratinjau dihitung</td><td class="v">{{ $fmt($asesmen->integrasi_pratinjau_pada) }}</td></tr>
        <tr><td class="k">Asesor</td><td class="v">{{ $asesorNama !== '' ? $asesorNama : '—' }}</td>
            <td class="k">Difinalisasi</td><td class="v">{{ $asesmen->waktu_finalisasi ? $fmt($asesmen->waktu_finalisasi).' oleh '.($asesmen->finalizedBy?->nama ?? '—') : 'Belum' }}</td></tr>
    </table>

    <h2>Ringkasan Hasil</h2>
    <table class="summary"><tr>
        <td class="box" style="width:33%">
            <div class="lbl">Job Fit (pratinjau)</div>
            <div class="big">{{ $jobFit !== null ? number_format((float) $jobFit, 1).'%' : '—' }}</div>
        </td>
        <td class="box" style="width:34%">
            <div class="lbl">Rekomendasi agregat</div>
            <div style="font-size:13px;font-weight:bold;margin-top:4px;color:#1e3a8a">
                {{ $labelAgregat ?? ($kodeAgregat === 'qualified' ? 'Memenuhi Persyaratan' : ($kodeAgregat === 'not_qualified' ? 'Belum Memenuhi Persyaratan' : '—')) }}
            </div>
        </td>
        <td class="box" style="width:33%">
            <div class="lbl">Jumlah kompetensi dinilai</div>
            <div class="big">{{ $integrasi->count() }}</div>
        </td>
    </tr></table>

    @if ($dimensiAgregat !== [])
        <h2>Penilaian per Dimensi</h2>
        <table class="grid">
            <thead><tr><th>Dimensi</th><th class="c" style="width:80px">Status</th><th>Catatan</th></tr></thead>
            <tbody>
            @foreach ($dimensiAgregat as $kodeDim => $info)
                @php $lolos = (bool) ($info['lolos'] ?? false); $pel = is_array($info['pelanggaran'] ?? null) ? $info['pelanggaran'] : []; @endphp
                <tr>
                    <td>{{ $info['label'] ?? $kodeDim }}</td>
                    <td class="c">{!! $lolos ? '<span class="badge-ok">Lolos</span>' : '<span class="badge-no">Gagal</span>' !!}</td>
                    <td>
                        @if ($pel === []) — @else
                            @foreach ($pel as $p)<div>• {{ $p }}</div>@endforeach
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>Profil Kompetensi</h2>
    @if ($integrasi->isEmpty())
        <p class="muted">Belum ada hasil integrasi. Sahkan perilaku kunci lalu hitung pratinjau terlebih dahulu.</p>
    @else
        <table class="grid">
            <thead><tr>
                <th>Kompetensi</th><th>Kelompok</th>
                <th class="c" style="width:46px">Target</th><th class="c" style="width:46px">Capaian</th>
                <th class="c" style="width:42px">GAP</th><th style="width:90px">Rekomendasi</th>
                <th class="c" style="width:34px">PK</th>
            </tr></thead>
            <tbody>
            @foreach ($integrasi as $r)
                @php
                    $gap = (int) ($r->selisih_gap ?? 0);
                    $kelas = $r->rekomendasi_kode === 'fit' ? 'fit' : ($r->rekomendasi_kode === 'development' ? 'dev' : 'nofit');
                @endphp
                <tr>
                    <td><strong>{{ $r->competency?->kode_kompetensi }}</strong> — {{ $r->competency?->nama }}</td>
                    <td>{{ $r->competency?->group?->nama ?? '—' }}</td>
                    <td class="c">{{ $r->tingkat_target ?? '—' }}</td>
                    <td class="c">{{ $r->tingkat_tercapai ?? '—' }}</td>
                    <td class="c">{{ $gap > 0 ? '+'.$gap : $gap }}</td>
                    <td><span class="chip {{ $kelas }}">{{ $labelRekomKomp($r->rekomendasi_kode) }}</span></td>
                    <td class="c">{{ $r->jumlah_pk_masuk ?? 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <h2>Rincian Perilaku Kunci (Disahkan)</h2>
    @if ($pkPerKompetensi->isEmpty())
        <p class="muted">Belum ada perilaku kunci yang disahkan.</p>
    @else
        @foreach ($pkPerKompetensi as $idKomp => $grup)
            @php $komp = $grup->first()?->competency; @endphp
            <div class="pk-group-title">{{ $komp?->kode_kompetensi }} — {{ $komp?->nama }}</div>
            @foreach ($grup as $pk)
                @php
                    $indikator = $pk->competencyLevel?->indikator_perilaku;
                    $tampilInd = $indikator && trim((string) $indikator) !== trim((string) $pk->teks_perilaku);
                @endphp
                <div class="pk">
                    <span class="tool">{{ $pk->tool?->kode ?? '—' }}</span>
                    @if ($pk->competencyLevel) <span class="muted">· Tingkat {{ $pk->competencyLevel->tingkat }}</span> @endif
                    <div>{{ $pk->teks_perilaku }}</div>
                    @if ($tampilInd)<div class="ind">Indikator resmi (L{{ $pk->competencyLevel->tingkat }}): {{ $indikator }}</div>@endif
                    @if ($pk->alasan_pemilihan)<div style="margin-top:2px">{{ $pk->alasan_pemilihan }}</div>@endif
                    @if ($pk->kutipan_referensi)<div class="quote">"{{ $pk->kutipan_referensi }}"</div>@endif
                </div>
            @endforeach
        @endforeach
    @endif

    <div class="disc">
        Catatan: Angka Job Fit dan GAP adalah <strong>pratinjau</strong> berbasis rata-rata tertimbang tingkat × bobot (strategi: {{ $strategiAgregasi }}), dihitung hanya dari perilaku kunci yang telah disahkan. Bukan keputusan akhir perusahaan.
    </div>
</body>
</html>
