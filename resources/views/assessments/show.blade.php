@extends('layouts.app')

@section('title', 'Detail asesmen — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('asesmen.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Asesmen: {{ $asesmen->participant?->nama_lengkap ?? 'Peserta' }}</h1>
        <p class="mt-1 text-sm text-zinc-600">
            Matriks <strong>{{ $asesmen->matrixVersion?->kode_versi }}</strong>
            · Tujuan:
            @if ($asesmen->tujuan?->value === 'promosi') Promosi @else Pemetaan talenta @endif
            @if ($asesmen->tanpa_intray)
                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-amber-900">Tanpa INTRAY</span>
            @endif
        </p>
    </div>

    @php
        $metodeBukti = $asesmen->metode_koleksi_bukti;
        $punyaAlatTersediaInput = $alatTersediaInput->isNotEmpty();
        $statusAsesmenLabel = match($asesmen->status?->value) {
            'selesai_final' => 'Selesai final',
            'terintegrasi' => 'Terintegrasi',
            'berlangsung' => 'Berlangsung',
            default => 'Draf',
        };
    @endphp

    <section class="mb-8 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Status asesmen</h2>
                <p class="mt-1 text-sm text-zinc-800">
                    Saat ini:
                    <span class="rounded bg-zinc-100 px-2 py-0.5 font-medium text-zinc-900">{{ $statusAsesmenLabel }}</span>
                </p>
                @if ($asesmen->status?->value !== 'selesai_final')
                    <p class="mt-1 text-xs text-zinc-600">Sebelum tombol finalisasi ditekan konsultan/admin, status tetap <strong>draf</strong>.</p>
                @elseif ($asesmen->waktu_finalisasi)
                    <p class="mt-1 text-xs text-zinc-600">Difinalisasi pada {{ $asesmen->waktu_finalisasi->timezone(config('app.timezone'))->format('d M Y H:i') }}.</p>
                @endif
            </div>
            @can('update', $asesmen)
                @if ($asesmen->status?->value !== 'selesai_final')
                    <form method="POST" action="{{ route('asesmen.finalisasi', $asesmen) }}" class="shrink-0">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-md bg-emerald-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-800">Finalisasi asesmen</button>
                    </form>
                @elseif ((auth()->user()->peran ?? '') === 'admin')
                    <form method="POST" action="{{ route('asesmen.batal-finalisasi', $asesmen) }}" class="shrink-0">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-md border border-rose-300 bg-rose-50 px-3 py-1.5 text-sm font-medium text-rose-900 hover:bg-rose-100">Batalkan finalisasi</button>
                    </form>
                @endif
            @endcan
        </div>

        <div class="mt-4 rounded-md border border-zinc-100 bg-zinc-50/60 p-3">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Cakupan kompetensi wajib (matriks)</p>
            <p class="mt-1 text-sm text-zinc-700">
                Terpenuhi <strong>{{ $ringkasanFinalisasi['total_terpenuhi'] }}</strong> dari
                <strong>{{ $ringkasanFinalisasi['total_wajib'] }}</strong> kompetensi wajib.
            </p>
            @if ($ringkasanFinalisasi['total_kompetensi_kurang'] > 0)
                <div class="mt-2 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    Kompetensi wajib berikut belum mendapatkan tingkat/level indikator perilaku dari evidence:
                    <ul class="mt-1 list-inside list-disc text-xs">
                        @foreach ($ringkasanFinalisasi['kompetensi_kurang'] as $k)
                            <li><span class="font-mono">{{ $k['kode'] }}</span> — {{ $k['nama'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="mt-2 text-xs text-emerald-700">Semua kompetensi wajib sudah memiliki tingkat indikator perilaku.</p>
            @endif
        </div>
    </section>

    @can('update', $asesmen)
        <section class="mb-8 rounded-lg border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-emerald-900">Metode koleksi bukti</h2>
            <p class="mt-1 text-xs text-emerald-900/80">Pilih alur utama untuk asesmen ini. Formulir di bawah mengikuti pilihan; Anda dapat mengganti metode kapan saja.</p>
            <p class="mt-2 text-sm font-medium text-emerald-950">Aktif: {{ $metodeBukti->label() }}</p>
            <form method="POST" action="{{ route('asesmen.metode-koleksi-bukti.update', $asesmen) }}" class="mt-4 space-y-3">
                @csrf
                @method('PATCH')
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer gap-2 rounded-lg border border-emerald-200 bg-white p-3 text-sm has-[:checked]:ring-2 has-[:checked]:ring-emerald-600">
                        <input type="radio" name="metode_koleksi_bukti" value="manual" class="mt-0.5 size-4 border-zinc-300 text-emerald-800 focus:ring-emerald-500" @checked($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual)>
                        <span><span class="font-medium text-zinc-900">Manual</span> — bukti per kompetensi + AI per bukti</span>
                    </label>
                    <label class="flex cursor-pointer gap-2 rounded-lg border border-emerald-200 bg-white p-3 text-sm has-[:checked]:ring-2 has-[:checked]:ring-violet-600">
                        <input type="radio" name="metode_koleksi_bukti" value="payload_alat" class="mt-0.5 size-4 border-zinc-300 text-violet-700 focus:ring-violet-500" @checked($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::PayloadAlat)>
                        <span><span class="font-medium text-zinc-900">Otomatis</span> — payload alat + AI bulk</span>
                    </label>
                </div>
                <button type="submit" class="rounded-md bg-emerald-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-800">Simpan metode</button>
            </form>
        </section>
    @endcan

    <section class="mb-8 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Asesor</h2>
        @if ($asesmen->assessorAssignments->isEmpty())
            <p class="mt-2 text-sm text-zinc-600">Belum ada asesor yang ditetapkan.</p>
        @else
            <ul class="mt-2 list-inside list-disc text-sm text-zinc-800">
                @foreach ($asesmen->assessorAssignments as $a)
                    <li>{{ $a->user?->name ?? 'Pengguna #' . $a->id_pengguna }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    <details class="mb-8 rounded-lg border border-zinc-200 bg-white shadow-sm open:shadow-md open:[&>summary>svg]:rotate-180" open>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 marker:content-none [&::-webkit-details-marker]:hidden">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Alat penilaian (preset)</h2>
                <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $asesmen->toolSelections->count() }} alat · klik untuk membuka / menutup</p>
            </div>
            <svg class="size-5 shrink-0 text-zinc-400 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </summary>
        <div class="border-t border-zinc-100 px-5 pb-5 pt-3">
            <p class="mb-3 text-xs text-zinc-600">
                Kisi seperti pemetaan versi matriks: baris = kompetensi (per kelompok), kolom = alat dalam preset asesmen ini.
                Sel menampilkan pemetaan pada versi <span class="font-mono font-medium text-zinc-800">{{ $asesmen->matrixVersion?->kode_versi }}</span>.
                Label di kepala kolom = status preset asesmen (wajib / opsional); isi sel = aturan di matriks (✓ wajib atau ✓ opsional).
            </p>

            @if (! $punyaKompetensiUntukMatriks || $pemilihanAlatPreset->isEmpty())
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    @if (! $punyaKompetensiUntukMatriks)
                        Belum ada data kompetensi untuk menampilkan kisi.
                    @endif
                    @if ($pemilihanAlatPreset->isEmpty())
                        Belum ada alat dalam preset asesmen ini.
                    @endif
                </div>
            @elseif ($kelompokKompetensiMatriks->isEmpty())
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Tidak ada kelompok kompetensi dengan kompetensi aktif.
                </div>
            @else
                <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
                    <table class="min-w-max divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50 text-left text-xs font-medium uppercase text-zinc-500">
                            <tr>
                                <th class="sticky left-0 z-10 border-r border-zinc-200 bg-zinc-50 px-3 py-2 align-bottom">Kompetensi</th>
                                @foreach ($pemilihanAlatPreset as $sel)
                                    @php $tool = $sel->tool; @endphp
                                    <th class="max-w-[6rem] px-2 py-2 text-center align-bottom" title="{{ $tool?->nama }}">
                                        <span class="block truncate font-mono normal-case text-zinc-700">{{ $tool?->kode }}</span>
                                        @if ($sel->aktif)
                                            @if ($sel->wajib)
                                                <span class="mt-1 inline-block rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-medium normal-case text-rose-900">Preset wajib</span>
                                            @else
                                                <span class="mt-1 inline-block rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium normal-case text-zinc-600">Preset ops.</span>
                                            @endif
                                        @else
                                            <span class="mt-1 inline-block rounded bg-zinc-200 px-1.5 py-0.5 text-[10px] font-normal normal-case text-zinc-600">Nonaktif</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @include('assessments.partials.preset-competency-tool-matrix-tbody')
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500">
                    <span><span class="inline-flex size-5 items-center justify-center rounded-full bg-rose-100 text-[10px] text-rose-900">✓</span> wajib di matriks</span>
                    <span><span class="inline-flex size-5 items-center justify-center rounded-full bg-zinc-200 text-[10px] text-zinc-800">✓</span> opsional di matriks</span>
                    <span><span class="text-zinc-400">—</span> tidak dipetakan pada versi ini</span>
                </p>
            @endif
        </div>
    </details>

    @if ($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::Manual)
        <section class="mb-8 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-500">Bukti penilaian</h2>
            <p class="mb-3 rounded-md border border-emerald-100 bg-emerald-50/40 px-3 py-2 text-xs text-emerald-950">Metode <strong>manual</strong>: tambahkan bukti per kompetensi; tombol <strong>Analisis AI</strong> ada di tiap kartu bukti (butuh kunci OpenRouter di .env; set <span class="font-mono">AI_AKTIF=false</span> untuk mematikannya).</p>
            @if ($asesmen->evidenceItems->isEmpty())
                <p class="text-sm text-zinc-600">Belum ada bukti.</p>
            @else
                <ul class="space-y-3 text-sm">
                    @foreach ($asesmen->evidenceItems as $b)
                        <li class="rounded-md border border-zinc-100 bg-zinc-50/50 p-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="font-medium text-zinc-900">{{ $b->tool?->kode }} · {{ $b->competency?->kode_kompetensi }} {{ $b->competency?->nama }}</div>
                                @can('update', $asesmen)
                                    @if (config('ai.aktif'))
                                        <form method="POST" action="{{ route('asesmen.bukti.analisis-ai', [$asesmen, $b]) }}" class="shrink-0 js-ai-processing-form" data-ai-mode="incremental">
                                            @csrf
                                            <button type="submit" class="rounded border border-violet-300 bg-violet-50 px-2 py-1 text-xs font-medium text-violet-900 hover:bg-violet-100">Analisis AI</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                            <p class="mt-1 whitespace-pre-wrap text-zinc-700">{{ $b->teks_mentah }}</p>
                            @if ($b->ai_dinilai_pada || $b->ai_tingkat || $b->ai_alasan)
                                <div class="mt-3 rounded border border-violet-100 bg-violet-50/60 p-2 text-xs text-violet-950">
                                    <p class="font-semibold text-violet-900">Hasil AI</p>
                                    @if ($b->ai_tingkat)
                                        <p class="mt-1"><span class="text-violet-800">Tingkat usulan:</span> {{ $b->ai_tingkat }}</p>
                                    @endif
                                    @if ($b->ai_keyakinan !== null)
                                        <p class="mt-0.5"><span class="text-violet-800">Keyakinan:</span> {{ $b->ai_keyakinan }}</p>
                                    @endif
                                    @if ($b->ai_alasan)
                                        <p class="mt-1 whitespace-pre-wrap"><span class="text-violet-800">Alasan:</span> {{ $b->ai_alasan }}</p>
                                    @endif
                                    @if (is_array($b->ai_muatan))
                                        @if (! empty($b->ai_muatan['kutipan_dari_teks_mentah']))
                                            <p class="mt-1 whitespace-pre-wrap"><span class="text-violet-800">Kutipan:</span> {{ $b->ai_muatan['kutipan_dari_teks_mentah'] }}</p>
                                        @endif
                                        @if (! empty($b->ai_muatan['konfirmatori']))
                                            <p class="mt-1 whitespace-pre-wrap"><span class="text-violet-800">Konfirmatori:</span> {{ $b->ai_muatan['konfirmatori'] }}</p>
                                        @endif
                                    @endif
                                    @if ($b->ai_dinilai_pada)
                                        <p class="mt-1 text-violet-700">{{ $b->ai_dinilai_pada->timezone(config('app.timezone'))->format('d M Y H:i') }}</p>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @can('update', $asesmen)
                <form method="POST" action="{{ route('asesmen.bukti.store', $asesmen) }}" class="mt-4 space-y-3 border-t border-zinc-100 pt-4">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="id_alat_penilaian_bukti" class="block text-xs font-medium text-zinc-700">Alat</label>
                            <select name="id_alat_penilaian" id="id_alat_penilaian_bukti" required @disabled(! $punyaAlatTersediaInput) class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm disabled:bg-zinc-100 disabled:text-zinc-500">
                                @if (! $punyaAlatTersediaInput)
                                    <option value="">Tidak ada alat yang dipakai pada matriks ini</option>
                                @else
                                    @foreach ($alatTersediaInput as $sel)
                                        <option value="{{ $sel->id_alat_penilaian }}">{{ $sel->tool?->kode }} — {{ $sel->tool?->nama }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="id_kompetensi_bukti" class="block text-xs font-medium text-zinc-700">Kompetensi</label>
                            <select name="id_kompetensi" id="id_kompetensi_bukti" required class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
                                @foreach ($kompetensi as $c)
                                    <option value="{{ $c->id }}">{{ $c->kode_kompetensi }} — {{ $c->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="teks_mentah" class="block text-xs font-medium text-zinc-700">Teks mentah</label>
                        <p class="mt-0.5 text-xs text-zinc-500">Editor ini membantu formatting saat input, namun data tetap disimpan sebagai teks mentah.</p>
                        <textarea name="teks_mentah" id="teks_mentah" rows="4" required data-normalize-preview="1" class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">{{ old('teks_mentah') }}</textarea>
                    </div>
                    <button type="submit" @disabled(! $punyaAlatTersediaInput) class="rounded-md bg-zinc-900 px-3 py-1.5 text-sm text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-400">Tambah bukti</button>
                </form>
            @endcan
        </section>
    @endif

    @if ($metodeBukti === \App\Enums\AssessmentEvidenceCollectionMode::PayloadAlat)
        <section class="mb-8 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-zinc-500">Payload alat (bulk)</h2>
            <p class="text-xs text-zinc-600">Tempel teks mentah dari alat (log, ekspor, dll.), simpan payload, lalu gunakan <strong>Analisis AI bulk</strong> pada daftar di bawah. Butuh kunci OpenRouter di .env. Hasil wajib direview asesor.</p>

            @can('update', $asesmen)
                <form method="POST" action="{{ route('asesmen.payload-alat.store', $asesmen) }}" class="mt-4 space-y-3 border-t border-zinc-100 pt-4">
                    @csrf
                    <div>
                        <label for="id_alat_penilaian_payload" class="block text-xs font-medium text-zinc-700">Alat</label>
                        <select name="id_alat_penilaian" id="id_alat_penilaian_payload" required @disabled(! $punyaAlatTersediaInput) class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm disabled:bg-zinc-100 disabled:text-zinc-500">
                            @if (! $punyaAlatTersediaInput)
                                <option value="">Tidak ada alat yang dipakai pada matriks ini</option>
                            @else
                                @foreach ($alatTersediaInput as $sel)
                                    <option value="{{ $sel->id_alat_penilaian }}">{{ $sel->tool?->kode }} — {{ $sel->tool?->nama }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div>
                        <label for="teks_muatan" class="block text-xs font-medium text-zinc-700">Teks muatan</label>
                        <textarea name="teks_muatan" id="teks_muatan" rows="5" required data-normalize-preview="1" class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">{{ old('teks_muatan') }}</textarea>
                    </div>
                    <button type="submit" @disabled(! $punyaAlatTersediaInput) class="rounded-md bg-zinc-900 px-3 py-1.5 text-sm text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-400">Simpan payload</button>
                </form>
            @endcan

            @if ($asesmen->toolPayloads->isNotEmpty())
                <h3 class="mt-6 text-xs font-semibold uppercase tracking-wide text-zinc-500">Payload tersimpan</h3>
                <ul class="mt-2 space-y-2 text-sm">
                    @foreach ($asesmen->toolPayloads as $p)
                        <li class="rounded border border-zinc-100 bg-zinc-50/50 p-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <span class="font-medium text-zinc-900">#{{ $p->id }} · {{ $p->tool?->kode }}</span>
                                @can('update', $asesmen)
                                    @if (config('ai.aktif'))
                                        <form method="POST" action="{{ route('asesmen.payload-alat.analisis-ai', [$asesmen, $p]) }}" class="js-ai-processing-form" data-ai-mode="bulk">
                                            @csrf
                                            <button type="submit" class="rounded border border-violet-300 bg-violet-50 px-2 py-1 text-xs font-medium text-violet-900 hover:bg-violet-100">Analisis AI bulk</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                            <p class="mt-1 line-clamp-3 text-zinc-700">{{ \Illuminate\Support\Str::limit($p->teks_muatan, 240) }}</p>
                            @if ($p->diproses_pada && is_array($p->hasil_analisis_ai))
                                <div class="mt-2 rounded border border-violet-100 bg-violet-50/60 p-2 text-xs text-violet-950">
                                    <p class="font-semibold text-violet-900">Usulan AI ({{ $p->diproses_pada->format('d M Y H:i') }})</p>
                                    <div class="mt-2 overflow-x-auto rounded border border-violet-100/80 bg-white">
                                        <table class="min-w-[720px] w-full divide-y divide-violet-100 text-left text-xs">
                                            <thead class="bg-violet-50/90 font-medium uppercase tracking-wide text-violet-900">
                                                <tr>
                                                    <th class="px-2 py-2">Kompetensi</th>
                                                    <th class="px-2 py-2">Level</th>
                                                    <th class="px-2 py-2">Kutipan (referensi)</th>
                                                    <th class="px-2 py-2">Ringkasan</th>
                                                    <th class="px-2 py-2">Alasan (AI)</th>
                                                    <th class="px-2 py-2">Konfirmatori</th>
                                                    <th class="px-2 py-2">Keyakinan</th>
                                                    <th class="px-2 py-2">Perilaku kunci (usulan)</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-violet-50 text-zinc-800">
                                                @foreach ($p->hasil_analisis_ai['usulan'] ?? [] as $u)
                                                    <tr class="align-top">
                                                        <td class="px-2 py-2 font-mono font-semibold text-zinc-900">{{ $u['kode_kompetensi'] ?? '?' }}</td>
                                                        <td class="px-2 py-2">{{ ! empty($u['tingkat']) ? $u['tingkat'] : '—' }}</td>
                                                        <td class="max-w-[14rem] whitespace-pre-wrap px-2 py-2 text-zinc-700">{{ $u['kutipan'] ?? '—' }}</td>
                                                        <td class="max-w-[12rem] whitespace-pre-wrap px-2 py-2">{{ \Illuminate\Support\Str::limit($u['ringkasan'] ?? '', 220) }}</td>
                                                        <td class="max-w-[14rem] whitespace-pre-wrap px-2 py-2 text-zinc-700">{{ $u['alasan'] ?? '—' }}</td>
                                                        <td class="max-w-[14rem] whitespace-pre-wrap px-2 py-2 text-zinc-700">{{ $u['konfirmatori'] ?? '—' }}</td>
                                                        <td class="px-2 py-2">{{ isset($u['keyakinan']) ? number_format((float) $u['keyakinan'], 2) : '—' }}</td>
                                                        <td class="max-w-[14rem] whitespace-pre-wrap px-2 py-2">{{ $u['teks_perilaku'] ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-500">Perilaku kunci</h2>
        @if ($asesmen->keyBehaviors->isEmpty())
            <p class="text-sm text-zinc-600">Belum ada perilaku kunci.</p>
        @else
            <div class="overflow-x-auto rounded border border-zinc-100">
                <table class="min-w-[820px] w-full divide-y divide-zinc-100 text-left text-sm">
                    <thead class="bg-zinc-50 text-xs font-medium uppercase tracking-wide text-zinc-500">
                        <tr>
                            <th class="px-3 py-2">Alat</th>
                            <th class="px-3 py-2">Kompetensi</th>
                            <th class="px-3 py-2">Level indikator</th>
                            <th class="px-3 py-2">Teks perilaku</th>
                            <th class="px-3 py-2">Alasan (AI / asesor)</th>
                            <th class="px-3 py-2">Kutipan (referensi)</th>
                            <th class="px-3 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50">
                        @foreach ($asesmen->keyBehaviors as $pk)
                            <tr class="align-top text-zinc-800">
                                <td class="px-3 py-2 font-mono text-xs font-medium">{{ $pk->tool?->kode ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $pk->competency?->kode_kompetensi ?? '?' }}</td>
                                <td class="px-3 py-2">
                                    @if ($pk->competencyLevel)
                                        <span class="font-medium">Level {{ $pk->competencyLevel->tingkat }}</span>
                                        @if (! empty($pk->competencyLevel->etiket))
                                            <span class="block text-xs text-zinc-500">{{ $pk->competencyLevel->etiket }}</span>
                                        @endif
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="max-w-[18rem] whitespace-pre-wrap px-3 py-2">{{ $pk->teks_perilaku }}</td>
                                <td class="max-w-[16rem] whitespace-pre-wrap px-3 py-2 text-zinc-700">{{ $pk->alasan_pemilihan ?: '—' }}</td>
                                <td class="max-w-[18rem] whitespace-pre-wrap px-3 py-2 text-xs text-zinc-700">
                                    @if ($pk->kutipan_referensi)
                                        {{ $pk->kutipan_referensi }}
                                    @elseif ($pk->evidence)
                                        <span class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-zinc-500">Bukti #{{ $pk->evidence->id }}</span>
                                        {{ \Illuminate\Support\Str::limit($pk->evidence->teks_mentah, 500) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @can('update', $asesmen)
                                        <a href="{{ route('asesmen.perilaku.edit', [$asesmen, $pk]) }}" class="text-xs font-medium text-violet-800 underline decoration-violet-300 hover:text-violet-950">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @can('update', $asesmen)
        <form method="POST" action="{{ route('asesmen.perilaku.store', $asesmen) }}" class="mt-4 space-y-3 border-t border-zinc-100 pt-4">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="id_alat_penilaian_pk" class="block text-xs font-medium text-zinc-700">Alat</label>
                    <select name="id_alat_penilaian" id="id_alat_penilaian_pk" required @disabled(! $punyaAlatTersediaInput) class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm disabled:bg-zinc-100 disabled:text-zinc-500">
                        @if (! $punyaAlatTersediaInput)
                            <option value="">Tidak ada alat yang dipakai pada matriks ini</option>
                        @else
                            @foreach ($alatTersediaInput as $sel)
                                <option value="{{ $sel->id_alat_penilaian }}">{{ $sel->tool?->kode }} — {{ $sel->tool?->nama }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <label for="id_kompetensi_pk" class="block text-xs font-medium text-zinc-700">Kompetensi</label>
                    <select name="id_kompetensi" id="id_kompetensi_pk" required class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
                        @foreach ($kompetensi as $c)
                            <option value="{{ $c->id }}">{{ $c->kode_kompetensi }} — {{ $c->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="id_bukti_penilaian" class="block text-xs font-medium text-zinc-700">Bukti (opsional)</label>
                <select name="id_bukti_penilaian" id="id_bukti_penilaian" class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
                    <option value="">— tidak ada —</option>
                    @foreach ($asesmen->evidenceItems as $b)
                        <option value="{{ $b->id }}">#{{ $b->id }} {{ $b->tool?->kode }} · {{ \Illuminate\Support\Str::limit($b->teks_mentah, 40) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="id_tingkat_kompetensi" class="block text-xs font-medium text-zinc-700">Tingkat kompetensi (opsional)</label>
                <select name="id_tingkat_kompetensi" id="id_tingkat_kompetensi" class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
                    <option value="">— tidak ada —</option>
                    @foreach ($tingkatKompetensi as $tk)
                        <option value="{{ $tk->id }}">{{ $tk->competency?->kode_kompetensi }} · Level {{ $tk->tingkat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="teks_perilaku" class="block text-xs font-medium text-zinc-700">Teks perilaku</label>
                <textarea name="teks_perilaku" id="teks_perilaku" rows="3" required class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">{{ old('teks_perilaku') }}</textarea>
            </div>
            <div>
                <label for="alasan_pemilihan_pk" class="block text-xs font-medium text-zinc-700">Alasan pemilihan tingkat (opsional)</label>
                <textarea name="alasan_pemilihan" id="alasan_pemilihan_pk" rows="2" class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">{{ old('alasan_pemilihan') }}</textarea>
            </div>
            <div>
                <label for="kutipan_referensi_pk" class="block text-xs font-medium text-zinc-700">Kutipan referensi (opsional)</label>
                <textarea name="kutipan_referensi" id="kutipan_referensi_pk" rows="2" class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm" placeholder="Potongan teks dari bukti atau payload">{{ old('kutipan_referensi') }}</textarea>
            </div>
            <button type="submit" @disabled(! $punyaAlatTersediaInput) class="rounded-md bg-zinc-900 px-3 py-1.5 text-sm text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:bg-zinc-400">Tambah perilaku kunci</button>
        </form>
        @endcan
    </section>
@endsection
