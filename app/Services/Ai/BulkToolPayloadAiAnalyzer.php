<?php

namespace App\Services\Ai;

use App\Enums\PayloadAnalysisStatus;
use App\Models\AiLog;
use App\Models\AssessmentToolPayload;
use App\Models\Competency;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\KeyBehavior;
use App\Models\User;
use App\Support\AiModelCatalog;
use App\Support\BulkTextNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkToolPayloadAiAnalyzer
{
    public function __construct(
        private readonly OpenRouterClient $client,
    ) {}

    /**
     * @return array{berhasil: bool, pesan?: string, jumlah_perilaku_kunci?: int}
     */
    public function analisisPayload(AssessmentToolPayload $payload, User $pengguna, ?string $namaModel = null): array
    {
        if (! config('ai.aktif', false)) {
            return $this->gagal($payload, 'Fitur AI tidak aktif (AI_AKTIF=false).');
        }

        $payload->loadMissing(['assessment.matrixVersion', 'tool']);

        $namaModel = AiModelCatalog::selesaikan($namaModel);
        $kompetensi = $this->kompetensiDiperbolehkan($payload);
        if ($kompetensi->isEmpty()) {
            return $this->gagal($payload, 'Tidak ada kompetensi aktif pada pemetaan matriks untuk alat payload ini.');
        }
        $daftarKode = $kompetensi->map(fn (Competency $c): string => $c->kode_kompetensi.' — '.$c->nama)->implode("\n");

        $teks = BulkTextNormalizer::canonicalPayloadMuatan(
            $payload->teks_muatan_rich,
            $payload->teks_muatan_normalized,
            (string) ($payload->teks_muatan ?? ''),
        );
        if ($teks === '' && trim((string) ($payload->teks_muatan ?? '')) !== '') {
            $teks = BulkTextNormalizer::normalizeForStorage((string) $payload->teks_muatan);
        }
        $sumberTeks = [$teks];
        $sistem = <<<'SYS'
Anda adalah seorang konsultan dan psikolog handal yang membantu asesor memetakan SATU dump teks (mis. salinan log alat) ke beberapa potong usulan per kompetensi.
Aturan wajib:
1) Jangan mengarang fakta di luar teks muatan.
2) Setiap elemen di array "usulan" WAJIB memiliki "kutipan" yang merupakan substring PERSIS (verbatim) dari teks muatan.
3) Untuk setiap usulan, tentukan juga:
   - tingkat: integer 1 sampai 6 = level indikator perilaku (6 = paling dalam / unggul) yang paling sesuai dengan bukti pada kutipan.
   - teks_perilaku: satu kalimat ringkas Bahasa Indonesia, dapat diamati, yang menggambarkan perilaku kunci (boleh merujuk kutipan tanpa mengubah fakta).
4) Jawab HANYA dengan satu objek JSON valid dengan kunci:
   usulan (array of objects). Tiap objek wajib punya:
   kode_kompetensi (string, dari daftar), ringkasan (string), kutipan (string verbatim), alasan (string), keyakinan (0–1),
   tingkat (integer 1–6), teks_perilaku (string), konfirmatori (string, hubungan kutipan dengan indikator perilaku level terpilih).
5) Batasi paling banyak 12 usulan; gabungkan jika perlu.
6) Setiap kode_kompetensi hanya boleh muncul SEKALI dalam "usulan" (satu baris per kompetensi; jika ada beberapa bukti, pilih satu kutipan terbaik).
7) Field "alasan" wajib menjelaskan mengapa "tingkat" dipilih, dengan merujuk secara eksplisit ke bagian teks yang relevan di "kutipan" (kutipan ulang frasa singkat jika perlu). Dilarang alasan generik tanpa tautan ke isi kutipan.
8) Satu kode kompetensi tidak boleh dipecah menjadi beberapa usulan untuk alat yang sama—gabungkan ke satu baris terkuat.
SYS;

        $penggunaMsg = 'Alat: '.($payload->tool?->kode ?? '')."\n"
            .'Matriks: '.($payload->assessment?->matrixVersion?->kode_versi ?? '')."\n\n"
            ."Daftar kode kompetensi yang diperbolehkan:\n{$daftarKode}\n\n"
            ."Teks muatan:\n---\n{$teks}\n---";

        $logBaru = new AiLog([
            'id_pengguna' => $pengguna->id,
            'id_asesmen' => $payload->id_asesmen,
            'id_bukti_penilaian' => null,
            'id_payload_alat_asesmen' => $payload->id,
            'jalur' => 'pemetaan_payload_bulk',
            'nama_model' => $namaModel,
            'status' => 'gagal',
            'metadata' => [],
        ]);

        try {
            $hasilApi = $this->client->chatCompletionDenganFallback(
                [
                    ['role' => 'system', 'content' => $sistem],
                    ['role' => 'user', 'content' => $penggunaMsg],
                ],
                $namaModel,
                config('ai.openrouter.maks_token_keluaran_bulk'),
            );
            $response = $hasilApi['response'];
            $latency = $hasilApi['latency_ms'];
            $namaModel = $hasilApi['nama_model'];
            $logBaru->nama_model = $namaModel;
        } catch (\Throwable $e) {
            $logBaru->fill([
                'pesan_kesalahan' => $e->getMessage(),
                'metadata' => ['latency_ms' => null, 'dicoba_model' => AiModelCatalog::rantaiFallback($namaModel)],
            ]);
            $logBaru->dibuat_pada = now();
            $logBaru->save();
            Log::warning('OpenRouter bulk gagal', ['exception' => $e->getMessage()]);

            return $this->gagal($payload, 'Gagal menghubungi penyedia AI: '.$e->getMessage());
        }

        $usage = OpenRouterClient::metadataUsage($response);
        $logBaru->kode_http = $response->status();
        $logBaru->metadata = array_filter([
            'latency_ms' => $latency,
            'usage' => $usage,
            'dicoba_model' => $hasilApi['dicoba_model'] ?? [$namaModel],
            'model_berhasil' => $namaModel,
        ], static fn ($v) => $v !== null);

        if (! $response->successful()) {
            $logBaru->pesan_kesalahan = $response->body();
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            return $this->gagal($payload, 'Respons penyedia AI tidak berhasil (HTTP '.$response->status().').');
        }

        $jsonStr = OpenRouterClient::ekstrakIsiJson($response);
        $parsed = AiModelJsonParser::parseObjek($jsonStr);
        $daftarUsulan = $parsed !== null ? AiModelJsonParser::ekstrakArrayUsulanBulk($parsed) : null;
        if ($daftarUsulan === null) {
            $logBaru->pesan_kesalahan = 'JSON model tidak valid (wajib kunci usulan berupa array).';
            $logBaru->metadata = array_merge($logBaru->metadata ?? [], [
                'cuplikan_respons' => mb_substr($jsonStr, 0, 500),
            ]);
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            return $this->gagal($payload, 'Model mengembalikan format JSON yang tidak diharapkan.');
        }

        $byKode = $kompetensi->keyBy('kode_kompetensi');
        $kodeValid = $kompetensi->pluck('kode_kompetensi')->all();
        $dibersihkan = [];
        foreach ($daftarUsulan as $item) {
            if (! is_array($item)) {
                continue;
            }
            $errorSchema = $this->validasiSchemaUsulan($item);
            if ($errorSchema !== null) {
                $logBaru->pesan_kesalahan = $errorSchema;
                $logBaru->dibuat_pada = now();
                $logBaru->save();

                return $this->gagal($payload, 'Model mengembalikan usulan bulk tidak valid: '.$errorSchema);
            }
            $kode = isset($item['kode_kompetensi']) ? (string) $item['kode_kompetensi'] : '';
            $kutipan = isset($item['kutipan']) ? trim((string) $item['kutipan']) : '';
            if ($kode === '' || $kutipan === '' || ! in_array($kode, $kodeValid, true)) {
                continue;
            }
            $kutipanDitemukan = BulkTextNormalizer::selesaikanKutipan($sumberTeks, $kutipan);
            if ($kutipanDitemukan === null) {
                Log::info('Bulk AI: kutipan dilewati (tidak verbatim)', [
                    'id_payload' => $payload->id,
                    'kode_kompetensi' => $kode,
                    'panjang_kutipan' => mb_strlen($kutipan),
                    'cuplikan_kutipan' => mb_substr($kutipan, 0, 120),
                ]);

<<<<<<< HEAD
                continue;
=======
                return $this->gagal($payload, 'Kutipan dalam usulan bulk tidak cocok dengan teks muatan (wajib substring verbatim).');
>>>>>>> 559c54242bf45abfd25473f7698056782648b5e7
            }
            [, $kutipan] = $kutipanDitemukan;

            $tingkatAngka = isset($item['tingkat']) && is_numeric($item['tingkat']) ? (int) $item['tingkat'] : null;
            if ($tingkatAngka !== null && ($tingkatAngka < 1 || $tingkatAngka > 6)) {
                $tingkatAngka = null;
            }

            $teksPerilaku = isset($item['teks_perilaku']) ? trim((string) $item['teks_perilaku']) : '';
            if ($teksPerilaku === '') {
                $teksPerilaku = isset($item['ringkasan']) ? trim((string) $item['ringkasan']) : '';
            }
            if ($teksPerilaku === '') {
                continue;
            }

            /** @var Competency|null $kompetensiRow */
            $kompetensiRow = $byKode->get($kode);
            $idTingkat = null;
            if ($kompetensiRow !== null && $tingkatAngka !== null) {
                $level = CompetencyLevel::query()
                    ->where('id_kompetensi', $kompetensiRow->id)
                    ->where('tingkat', $tingkatAngka)
                    ->whereNull('dihapus_pada')
                    ->first();
                $idTingkat = $level?->id;
            }

            $dibersihkan[] = [
                'kode_kompetensi' => $kode,
                'ringkasan' => isset($item['ringkasan']) ? (string) $item['ringkasan'] : '',
                'kutipan' => $kutipan,
                'alasan' => isset($item['alasan']) ? (string) $item['alasan'] : '',
                'keyakinan' => isset($item['keyakinan']) ? max(0.0, min(1.0, (float) $item['keyakinan'])) : null,
                'tingkat' => $tingkatAngka,
                'id_tingkat_kompetensi' => $idTingkat,
                'teks_perilaku' => $teksPerilaku,
                'konfirmatori' => isset($item['konfirmatori']) ? trim((string) $item['konfirmatori']) : '',
            ];
            if (count($dibersihkan) >= 12) {
                break;
            }
        }

        $dibersihkan = $this->dedupeUsulanPerKompetensi($dibersihkan);

        if ($dibersihkan === []) {
            $logBaru->pesan_kesalahan = 'Tidak ada usulan valid setelah validasi.';
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            return ['berhasil' => false, 'pesan' => 'Tidak ada usulan valid (periksa kode kompetensi, kutipan verbatim, tingkat 1–6, dan teks perilaku).'];
        }

        $jumlahPk = 0;
        $hasil = [
            'usulan' => $dibersihkan,
            'dibuat_pada' => now()->toIso8601String(),
            'perilaku_kunci_dibuat' => [],
        ];

        DB::transaction(function () use ($payload, $hasil, $logBaru, $byKode, $dibersihkan, &$jumlahPk): void {
            $hasilLama = $payload->hasil_analisis_ai;

            $this->hapusDuplikatPerAlatKompetensi($payload->id_asesmen, $payload->id_alat_penilaian);

            $kodeLama = collect($hasilLama['usulan'] ?? [])
                ->pluck('kode_kompetensi')
                ->map(fn ($k): string => is_string($k) ? trim($k) : '')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $kodeBaru = collect($dibersihkan)->pluck('kode_kompetensi')->all();

            foreach (array_diff($kodeLama, $kodeBaru) as $kodeHapus) {
                /** @var Competency|null $comp */
                $comp = $byKode->get($kodeHapus);
                if ($comp === null) {
                    continue;
                }
                KeyBehavior::query()
                    ->where('id_asesmen', $payload->id_asesmen)
                    ->where('id_alat_penilaian', $payload->id_alat_penilaian)
                    ->where('id_kompetensi', $comp->id)
                    ->delete();
            }

            $idsBaru = [];
            foreach ($hasil['usulan'] as $row) {
                /** @var Competency|null $c */
                $c = $byKode->get($row['kode_kompetensi']);
                if ($c === null) {
                    continue;
                }
                $alasan = trim((string) ($row['alasan'] ?? ''));
                $kutipan = trim((string) ($row['kutipan'] ?? ''));
                $idTingkatRow = $row['id_tingkat_kompetensi'] !== null ? (int) $row['id_tingkat_kompetensi'] : null;
                $teksPk = CompetencyLevel::teksIndikatorResmi($idTingkatRow) ?? $row['teks_perilaku'];

                $pk = KeyBehavior::query()->firstOrNew([
                    'id_asesmen' => $payload->id_asesmen,
                    'id_alat_penilaian' => $payload->id_alat_penilaian,
                    'id_kompetensi' => $c->id,
                ]);

                $pk->fill([
                    'id_bukti_penilaian' => null,
                    'id_tingkat_kompetensi' => $idTingkatRow,
                    'teks_perilaku' => $teksPk,
                    'alasan_pemilihan' => $alasan !== '' ? $alasan : null,
                    'kutipan_referensi' => $kutipan !== '' ? $kutipan : null,
                ]);

                if (! $pk->exists) {
                    $pk->tervalidasi = false;
                }

                $pk->save();

                $idsBaru[] = $pk->id;
                $jumlahPk++;
            }

            $hasil['perilaku_kunci_dibuat'] = $idsBaru;

            $payload->update([
                'hasil_analisis_ai' => $hasil,
                'diproses_pada' => now(),
                'status_analisis' => PayloadAnalysisStatus::Berhasil,
                'pesan_status_analisis' => null,
            ]);
            $logBaru->status = 'berhasil';
            $logBaru->pesan_kesalahan = null;
            $logBaru->dibuat_pada = now();
            $logBaru->save();
        });

        return ['berhasil' => true, 'jumlah_perilaku_kunci' => $jumlahPk];
    }

    /**
     * Kompetensi yang dipetakan ke alat + versi matriks asesmen (lebih ringan daripada seluruh kamus).
     *
     * @return Collection<int, Competency>
     */
    private function kompetensiDiperbolehkan(AssessmentToolPayload $payload): Collection
    {
        $idVersi = $payload->assessment?->id_versi_matriks;
        $idAlat = (int) $payload->id_alat_penilaian;

        if ($idVersi === null) {
            return Competency::query()
                ->where('aktif', true)
                ->orderBy('kode_kompetensi')
                ->get(['id', 'kode_kompetensi', 'nama']);
        }

        $idKompetensi = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $idVersi)
            ->where('id_alat_penilaian', $idAlat)
            ->where('aktif', true)
            ->whereNull('dihapus_pada')
            ->pluck('id_kompetensi')
            ->unique()
            ->values();

        if ($idKompetensi->isEmpty()) {
            $idKompetensi = CompetencyToolMapping::query()
                ->where('id_versi_matriks', $idVersi)
                ->where('aktif', true)
                ->whereNull('dihapus_pada')
                ->pluck('id_kompetensi')
                ->unique()
                ->values();
        }

        if ($idKompetensi->isEmpty()) {
            return new Collection;
        }

        return Competency::query()
            ->whereIn('id', $idKompetensi)
            ->where('aktif', true)
            ->orderBy('kode_kompetensi')
            ->get(['id', 'kode_kompetensi', 'nama']);
    }

    /**
     * Satu baris per kode_kompetensi; prioritas: keyakinan lebih tinggi, lalu tingkat, lalu alasan lebih panjang.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function dedupeUsulanPerKompetensi(array $rows): array
    {
        $byKode = [];
        foreach ($rows as $row) {
            $kode = $row['kode_kompetensi'];
            $prev = $byKode[$kode] ?? null;
            if ($prev === null || $this->bandingkanPrioritasUsulan($row, $prev) > 0) {
                $byKode[$kode] = $row;
            }
        }

        return array_values($byKode);
    }

    /**
     * Positif jika $a lebih baik dari $b (gantikan $b).
     *
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private function bandingkanPrioritasUsulan(array $a, array $b): int
    {
        $ka = $a['keyakinan'];
        $kb = $b['keyakinan'];
        $scoreA = $ka !== null ? (float) $ka : -1.0;
        $scoreB = $kb !== null ? (float) $kb : -1.0;
        if ($scoreA !== $scoreB) {
            return $scoreA <=> $scoreB;
        }

        $ta = $a['tingkat'] ?? 0;
        $tb = $b['tingkat'] ?? 0;
        if ((int) $ta !== (int) $tb) {
            return ((int) $ta) <=> ((int) $tb);
        }

        return mb_strlen((string) ($a['alasan'] ?? '')) <=> mb_strlen((string) ($b['alasan'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function validasiSchemaUsulan(array $item): ?string
    {
        foreach (['kode_kompetensi', 'kutipan', 'alasan', 'keyakinan', 'tingkat', 'teks_perilaku', 'konfirmatori'] as $kunci) {
            if (! array_key_exists($kunci, $item)) {
                return 'Kunci wajib usulan hilang: '.$kunci;
            }
        }
        if ($this->nilaiStringWajib($item['kode_kompetensi']) === '') {
            return 'kode_kompetensi wajib string non-kosong.';
        }
        if ($this->nilaiStringWajib($item['kutipan']) === '') {
            return 'kutipan wajib string non-kosong.';
        }
        if ($this->nilaiStringWajib($item['alasan']) === '') {
            return 'alasan wajib string non-kosong.';
        }
        if ($this->nilaiStringWajib($item['teks_perilaku']) === '') {
            return 'teks_perilaku wajib string non-kosong.';
        }
        if ($this->nilaiStringWajib($item['konfirmatori']) === '') {
            return 'konfirmatori wajib string non-kosong.';
        }
        if (! is_numeric($item['keyakinan'])) {
            return 'keyakinan wajib angka 0 sampai 1.';
        }
        if (! is_numeric($item['tingkat'])) {
            return 'tingkat wajib integer 1-6.';
        }

        return null;
    }

    private function nilaiStringWajib(mixed $nilai): string
    {
        if (is_string($nilai)) {
            return trim($nilai);
        }
        if (is_int($nilai) || is_float($nilai)) {
            return trim((string) $nilai);
        }

        return '';
    }

    /**
     * Satu baris perilaku kunci per pasangan (asesmen, alat, kompetensi); hapus duplikat lama (mis. dari payload lain).
     */
    private function hapusDuplikatPerAlatKompetensi(int $idAsesmen, int $idAlatPenilaian): void
    {
        $grup = KeyBehavior::query()
            ->where('id_asesmen', $idAsesmen)
            ->where('id_alat_penilaian', $idAlatPenilaian)
            ->orderBy('id')
            ->get()
            ->groupBy('id_kompetensi');

        foreach ($grup as $barisUntukKompetensi) {
            if ($barisUntukKompetensi->count() <= 1) {
                continue;
            }
            $idHapus = $barisUntukKompetensi->skip(1)->pluck('id')->all();
            if ($idHapus !== []) {
                KeyBehavior::query()->whereIn('id', $idHapus)->delete();
            }
        }
    }

    /**
     * @return array{berhasil: false, pesan: string}
     */
    private function gagal(AssessmentToolPayload $payload, string $pesan): array
    {
        $payload->tandaiStatusAnalisis(PayloadAnalysisStatus::Gagal, $pesan);

        return ['berhasil' => false, 'pesan' => $pesan];
    }
}
