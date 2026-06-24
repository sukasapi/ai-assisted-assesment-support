@php
    $fmt = fn ($d) => $d ? $d->timezone(config('app.timezone'))->format('d M Y H:i') : '—';
    $fmtTgl = fn ($d) => $d ? $d->timezone(config('app.timezone'))->format('d M Y') : '—';
    $labelStatus = fn (?string $s): string => match ($s) {
        'selesai_final' => 'Final',
        'terintegrasi' => 'Terintegrasi',
        'berlangsung' => 'Berlangsung',
        default => 'Draf',
    };
    $labelRekom = fn (?string $k): string => match ($k) {
        'qualified' => 'Memenuhi',
        'not_qualified' => 'Belum memenuhi',
        default => '—',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Sesi — {{ $sesi->nama }}</title>
    <style>
        * { font-family: "DejaVu Sans", sans-serif; }
        @page { margin: 26mm 16mm 22mm 16mm; }
        body { font-size: 10px; color: #1a1c1e; line-height: 1.45; }
        h1 { font-size: 16px; margin: 0; color: #0058be; }
        h2 { font-size: 11px; margin: 18px 0 6px; color: #0058be; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1.5px solid #0058be; padding-bottom: 3px; }
        .sub { font-size: 9px; color: #6b7280; margin-top: 2px; }
        .meta td { padding: 3px 6px; vertical-align: top; }
        .meta td.k { width: 22%; color: #6b7280; }
        .meta td.v { font-weight: bold; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-top: 4px; }
        .cards .box { border: 1px solid #e5e7eb; background: #f8fafc; padding: 8px 10px; text-align: center; }
        .cards .lbl { font-size: 7.5px; text-transform: uppercase; color: #6b7280; }
        .cards .big { font-size: 17px; font-weight: bold; color: #1e3a8a; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .grid th { background: #0058be; color: #fff; font-size: 8.5px; text-transform: uppercase; padding: 6px 5px; text-align: left; }
        .grid td { border-bottom: 1px solid #e5e7eb; padding: 5px; }
        .grid td.c, .grid th.c { text-align: center; }
        .chip { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; font-weight: bold; border: 1px solid; }
        .ok { background: #ecfdf5; color: #065f46; border-color: #6ee7b7; }
        .no { background: #fef2f2; color: #991b1b; border-color: #fca5a5; }
        .mut { background: #f3f4f6; color: #6b7280; border-color: #d1d5db; }
        .footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8px; color: #9ca3af; border-top: .5px solid #e5e7eb; padding-top: 4px; }
        .footer .pg:after { content: counter(page) " / " counter(pages); }
        .header { position: fixed; top: -18mm; left: 0; right: 0; font-size: 8px; color: #9ca3af; border-bottom: .5px solid #e5e7eb; padding-bottom: 4px; }
        .disc { background: #fffbeb; border: 1px solid #fcd34d; padding: 6px 8px; font-size: 8.5px; color: #92400e; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="header">{{ config('app.name') }} · Laporan Sesi {{ $sesi->kode_sesi }}</div>
    <div class="footer">
        <table style="width:100%"><tr>
            <td>Dokumen pratinjau — bukan nilai final perusahaan. Dicetak oleh {{ $dicetakOleh }} pada {{ $fmt($dicetakPada) }}.</td>
            <td style="text-align:right" class="pg"></td>
        </tr></table>
    </div>

    <h1>Laporan Sesi Assessment</h1>
    <p class="sub">Ringkasan agregat seluruh peserta dalam satu sesi. Angka Job Fit & rekomendasi adalah pratinjau, bukan nilai final.</p>

    <h2>Informasi Sesi</h2>
    <table class="meta">
        <tr><td class="k">Kode sesi</td><td class="v">{{ $sesi->kode_sesi }}</td>
            <td class="k">Status</td><td class="v">{{ $sesi->status?->label() ?? '—' }}</td></tr>
        <tr><td class="k">Nama</td><td class="v">{{ $sesi->nama }}</td>
            <td class="k">Periode</td><td class="v">{{ $fmtTgl($sesi->tanggal_mulai) }} – {{ $fmtTgl($sesi->tanggal_selesai) }}</td></tr>
    </table>

    <h2>Ringkasan</h2>
    <table class="cards"><tr>
        <td class="box"><div class="lbl">Peserta</div><div class="big">{{ $ringkasan['total'] }}</div></td>
        <td class="box"><div class="lbl">Rata Job Fit</div><div class="big">{{ $ringkasan['rata_job_fit'] !== null ? $ringkasan['rata_job_fit'].'%' : '—' }}</div></td>
        <td class="box"><div class="lbl">Memenuhi</div><div class="big" style="color:#065f46">{{ $ringkasan['qualified'] }}</div></td>
        <td class="box"><div class="lbl">Belum memenuhi</div><div class="big" style="color:#991b1b">{{ $ringkasan['not_qualified'] }}</div></td>
        <td class="box"><div class="lbl">Final</div><div class="big">{{ $ringkasan['final'] }}/{{ $ringkasan['total'] }}</div></td>
        <td class="box"><div class="lbl">Belum dinilai</div><div class="big" style="color:#92400e">{{ $ringkasan['belum_dinilai'] }}</div></td>
    </tr></table>

    <h2>Peringkat Peserta (Job Fit)</h2>
    <table class="grid">
        <thead><tr>
            <th class="c" style="width:30px">#</th><th>Peserta</th><th>Matriks</th>
            <th class="c" style="width:60px">Job Fit</th><th style="width:100px">Rekomendasi</th><th style="width:80px">Status</th>
        </tr></thead>
        <tbody>
        @foreach ($ringkasan['peringkat'] as $a)
            @php $kelas = $a->kode_rekomendasi_agregat === 'qualified' ? 'ok' : ($a->kode_rekomendasi_agregat === 'not_qualified' ? 'no' : 'mut'); @endphp
            <tr>
                <td class="c">{{ $loop->iteration }}</td>
                <td>{{ $a->participant?->nama_lengkap ?? '—' }}</td>
                <td>{{ $a->matrixVersion?->kode_versi ?? '—' }}</td>
                <td class="c"><strong>{{ $a->job_fit_persen_pratinjau !== null ? number_format((float) $a->job_fit_persen_pratinjau, 1).'%' : '—' }}</strong></td>
                <td><span class="chip {{ $kelas }}">{{ $labelRekom($a->kode_rekomendasi_agregat) }}</span></td>
                <td>{{ $labelStatus($a->status?->value) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="disc">
        Catatan: Laporan ini menyajikan <strong>pratinjau</strong> integrasi per peserta. Peserta «Belum dinilai» belum memiliki perilaku kunci disahkan / belum dihitung pratinjau. Keputusan akhir tetap kewenangan asesor / perusahaan.
    </div>
</body>
</html>
