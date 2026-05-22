@php
    $jobFit = $asesmen->job_fit_persen_pratinjau;
    $punyaBaris = $integrasiPratinjau->isNotEmpty();
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

    <div class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest p-6 md:p-8">
        <div class="mb-6 flex flex-wrap items-center gap-4">
            <div class="rounded-xl border border-primary/20 bg-primary-fixed/30 px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wide text-primary">Job Fit pratinjau</p>
                <p class="mt-1 font-display text-3xl font-bold text-primary">
                    {{ $jobFit !== null ? number_format((float) $jobFit, 1).'%' : '—' }}
                </p>
            </div>
            <span class="inline-flex rounded-full border border-dashed border-amber-300 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-900">
                Bukan nilai final
            </span>
        </div>

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
                            @endphp
                            <tr class="border-b border-outline-variant/15 hover:bg-surface-container-low/50">
                                <td class="px-3 py-3">
                                    <span class="font-semibold text-on-surface">{{ $baris->competency?->kode_kompetensi }}</span>
                                    <span class="block text-xs text-on-surface-variant">{{ $baris->competency?->nama }}</span>
                                </td>
                                <td class="px-3 py-3 text-center font-mono">{{ $baris->tingkat_target ?? '—' }}</td>
                                <td class="px-3 py-3 text-center font-mono">{{ $baris->tingkat_tercapai ?? '—' }}</td>
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
