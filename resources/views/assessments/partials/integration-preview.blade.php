@php
    $jobFit = $asesmen->job_fit_persen_pratinjau;
    $punyaBaris = $integrasiPratinjau->isNotEmpty();
    $detailAgregat = $asesmen->detail_rekomendasi_agregat ?? [];
    $kodeAgregat = $asesmen->kode_rekomendasi_agregat;
    $labelAgregat = is_array($detailAgregat) ? ($detailAgregat['label'] ?? null) : null;
    $dimensiAgregat = is_array($detailAgregat) ? ($detailAgregat['dimensi'] ?? []) : [];
    $nomorRevisi = $asesmen->lastRecommendationConfigRevision?->nomor_revisi;
    $labelRekomendasi = fn (?string $kode): string => match ($kode) {
        'fit' => 'Fit',
        'development' => 'Development',
        'not_fit' => 'Not Fit',
        default => '—',
    };
    $kelasRekomendasi = fn (?string $kode): string => match ($kode) {
        'fit' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'development' => 'border-amber-200 bg-amber-50 text-amber-800',
        'not_fit' => 'border-rose-200 bg-rose-50 text-rose-800',
        default => 'border-outline-variant/40 bg-surface-container text-on-surface-variant',
    };
    $kelasAgregat = match ($kodeAgregat) {
        'qualified' => 'border-emerald-300 bg-emerald-50 text-emerald-900',
        'not_qualified' => 'border-rose-300 bg-rose-50 text-rose-900',
        default => 'border-outline-variant/40 bg-surface-container text-on-surface-variant',
    };
@endphp

<section class="space-y-4 pb-8" data-testid="integrasi-pratinjau">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="font-display text-2xl text-on-surface">Pratinjau integrasi</h2>
            <p class="mt-1 text-sm text-on-surface-variant">
                Hasil perhitungan GAP &amp; Job Fit adalah <strong>pratinjau</strong>, bukan nilai final perusahaan. Hanya perilaku kunci <strong>disahkan</strong> yang masuk hitungan.
            </p>
        </div>
        @if ($asesmen->integrasi_pratinjau_pada)
            <p class="text-xs text-on-surface-variant">
                Terakhir dihitung: {{ $asesmen->integrasi_pratinjau_pada->timezone(config('app.timezone'))->format('d M Y H:i') }}
            </p>
        @endif
    </div>

    @if (($pratinjauKedaluwarsa ?? false) && $punyaBaris)
        <div class="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" data-testid="pratinjau-kedaluwarsa">
            <span class="material-symbols-outlined shrink-0">update</span>
            <p>
                <strong>Hasil pratinjau mungkin kedaluwarsa.</strong> Ada perilaku kunci disahkan yang berubah setelah perhitungan terakhir.
                Klik «Hitung ulang pratinjau» agar GAP &amp; Job Fit memakai data terbaru.
            </p>
        </div>
    @endif

    <div class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest p-6 md:p-8">
        @isset($strategiAgregasiAktif)
            <p class="mb-4 inline-flex items-center gap-2 rounded-lg border border-outline-variant/30 bg-surface-container-low px-3 py-1.5 text-xs text-on-surface-variant">
                <span class="material-symbols-outlined text-sm">tune</span>
                Strategi agregasi antar PK per alat: <strong class="text-on-surface">{{ $strategiAgregasiAktif->label() }}</strong>
            </p>
        @endisset
        <div class="mb-6 flex flex-wrap items-center gap-4">
            <div class="rounded-xl border border-primary/20 bg-primary-fixed/30 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-primary">Job Fit pratinjau</p>
                <p class="mt-1 font-display text-3xl font-bold text-primary">
                    {{ $jobFit !== null ? number_format((float) $jobFit, 1).'%' : '—' }}
                </p>
            </div>
            @if ($kodeAgregat !== null)
                <div class="rounded-xl border px-5 py-4 {{ $kelasAgregat }}" data-testid="rekomendasi-agregat">
                    <p class="text-xs font-bold uppercase tracking-wide opacity-80">Rekomendasi agregat</p>
                    <p class="mt-1 font-display text-lg font-bold leading-snug">{{ $labelAgregat ?? ($kodeAgregat === 'qualified' ? 'Memenuhi Persyaratan' : 'Belum Memenuhi Persyaratan') }}</p>
                    @if ($nomorRevisi !== null)
                        <p class="mt-1 text-xs opacity-75">Revisi konfigurasi #{{ $nomorRevisi }} · bukan nilai final</p>
                    @endif
                </div>
            @endif
            <span class="inline-flex rounded-full border border-dashed border-amber-300 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-900">
                Bukan nilai final
            </span>
        </div>

        @if ($kodeAgregat !== null && $dimensiAgregat !== [])
            <div class="mb-6 overflow-hidden rounded-xl border border-outline-variant/30">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-container-low text-xs uppercase text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3">Dimensi</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/15">
                        @foreach ($dimensiAgregat as $kodeDim => $info)
                            @php
                                $lolos = (bool) ($info['lolos'] ?? false);
                                $pelanggaran = is_array($info['pelanggaran'] ?? null) ? $info['pelanggaran'] : [];
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium text-on-surface">{{ $info['label'] ?? $kodeDim }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $lolos ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $lolos ? 'Lolos' : 'Gagal' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-on-surface-variant">
                                    @if ($pelanggaran === [])
                                        —
                                    @else
                                        <ul class="list-disc pl-4 space-y-0.5">
                                            @foreach ($pelanggaran as $p)
                                                <li>{{ $p }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @can('update', $asesmen)
            @if ($isDraft)
                <form method="POST" action="{{ route('asesmen.integrasi.hitung', $asesmen) }}" class="mb-6">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-secondary px-5 py-2.5 text-sm font-bold text-on-secondary shadow-sm hover:opacity-90">
                        <span class="material-symbols-outlined text-lg">calculate</span>
                        Hitung ulang pratinjau
                    </button>
                </form>
            @else
                <p class="mb-4 text-sm text-on-surface-variant">Asesmen sudah difinalisasi; pratinjau tidak dapat dihitung ulang.</p>
            @endif
        @endcan

        @error('integrasi')
            <p class="mb-4 text-sm font-medium text-error">{{ $message }}</p>
        @enderror

        @if (! $punyaBaris)
            <p class="text-sm text-on-surface-variant">
                Belum ada data pratinjau. Sahkan perilaku kunci (centang «mapping resmi» pada edit, atau tambah manual), lalu klik «Hitung ulang pratinjau».
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant/30 text-xs uppercase tracking-wide text-on-surface-variant">
                            <th class="px-3 py-3">Kompetensi</th>
                            <th class="px-3 py-3 text-center">Target</th>
                            <th class="px-3 py-3 text-center">Capaian</th>
                            <th class="px-3 py-3 text-center">GAP</th>
                            <th class="px-3 py-3">Rekomendasi</th>
                            <th class="px-3 py-3 text-center"># PK</th>
                            <th class="px-3 py-3">Sumber</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($integrasiPratinjau as $baris)
                            @php
                                $gap = (int) ($baris->selisih_gap ?? 0);
                                $gapKelas = $gap > 0 ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold';
                                // L-4: tandai capaian "ambang" (skor terbobot dekat X,5 → pembulatan menentukan level).
                                $skorTerbobot = $baris->skor_terbobot !== null ? (float) $baris->skor_terbobot : null;
                                $borderline = $skorTerbobot !== null && abs($skorTerbobot - round($skorTerbobot)) >= 0.35;
                                // L-3: sebaran PK antar alat (agar asesor sadar bagaimana beberapa PK digabung).
                                $detail = is_array($baris->detail_bobot) ? $baris->detail_bobot : [];
                                $adaSebaran = collect($detail)->contains(fn ($d) => is_array($d) && (int) ($d['level_min'] ?? 0) !== (int) ($d['level_max'] ?? 0));
                                $sebaranTeks = collect($detail)
                                    ->map(fn ($d, $kode) => is_array($d)
                                        ? $kode.': '.($d['jumlah_pk'] ?? 1).' PK (L'.($d['level_min'] ?? '?').'–L'.($d['level_max'] ?? '?').' → dipakai L'.($d['level'] ?? '?').')'
                                        : null)
                                    ->filter()->implode(' · ');
                            @endphp
                            <tr class="border-b border-outline-variant/15 hover:bg-surface-container-low/50">
                                <td class="px-3 py-3">
                                    <span class="font-semibold text-on-surface">{{ $baris->competency?->kode_kompetensi }}</span>
                                    <span class="block text-xs text-on-surface-variant">{{ $baris->competency?->nama }}</span>
                                </td>
                                <td class="px-3 py-3 text-center font-mono">{{ $baris->tingkat_target ?? '—' }}</td>
                                <td class="px-3 py-3 text-center font-mono">
                                    {{ $baris->tingkat_tercapai ?? '—' }}
                                    @if ($borderline)
                                        <span class="ml-1 inline-flex rounded-full border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 align-middle"
                                              title="Ambang: skor terbobot {{ number_format($skorTerbobot, 2) }} berada dekat batas pembulatan antar tingkat. Tinjau bukti sebelum menyimpulkan.">
                                            ambang
                                        </span>
                                    @endif
                                    @if ($adaSebaran)
                                        <span class="ml-1 inline-flex items-center align-middle text-on-surface-variant/70" title="Sebaran PK antar alat — {{ $sebaranTeks }}">
                                            <span class="material-symbols-outlined text-sm">info</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center font-mono {{ $gapKelas }}">{{ $gap > 0 ? '+'.$gap : $gap }}</td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $kelasRekomendasi($baris->rekomendasi_kode) }}">
                                        {{ $labelRekomendasi($baris->rekomendasi_kode) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">{{ $baris->jumlah_pk_masuk }}</td>
                                <td class="px-3 py-3 text-xs text-on-surface-variant">{{ str_replace('_', ' ', $baris->sumber_utama ?? '—') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
