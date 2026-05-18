<?php

namespace App\Services\Ai;

use App\Models\AiLog;
use App\Models\CompetencyLevel;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvidenceAiAnalyzer
{
    public function __construct(
        private readonly OpenRouterClient $client,
    ) {}

    /**
     * @return array{berhasil: bool, pesan?: string}
     */
    public function analisisInkremental(Evidence $bukti, User $pengguna): array
    {
        if (! config('ai.aktif', false)) {
            return ['berhasil' => false, 'pesan' => 'Fitur AI tidak aktif (AI_AKTIF=false).'];
        }

        $bukti->loadMissing(['competency', 'tool', 'assessment']);

        $tingkatRows = CompetencyLevel::query()
            ->where('id_kompetensi', $bukti->id_kompetensi)
            ->orderBy('tingkat')
            ->get(['id', 'tingkat', 'indikator_perilaku', 'etiket', 'deskripsi']);

        $ringkasanTingkat = $tingkatRows->map(function (CompetencyLevel $t): string {
            $ind = $t->indikator_perilaku ?? $t->deskripsi ?? '';

            return "id_tingkat={$t->id}; tingkat={$t->tingkat}; etiket=".($t->etiket ?? '').'; indikator='.mb_substr((string) $ind, 0, 400);
        })->implode("\n");

        $kodeKompetensi = $bukti->competency?->kode_kompetensi ?? '';
        $namaKompetensi = $bukti->competency?->nama ?? '';
        $teksMentah = $bukti->teks_mentah_normalized ?: $bukti->teks_mentah;

        $sistem = <<<'SYS'
Anda membantu asesor menafsirkan SATU bukti penilaian. Aturan wajib:
1) Jangan mengarang fakta di luar teks mentah peserta.
2) Field "kutipan_dari_teks_mentah" HARUS berupa substring PERSIS (salinan verbatim) dari teks mentah. Jika tidak yakin, gunakan kutipan paling pendek yang masih verbatim.
3) Jawab HANYA dengan satu objek JSON valid (tanpa markdown) dengan kunci:
   tingkat (integer 1–6 sesuai skala yang diberikan untuk kompetensi ini, atau null jika tidak dapat ditentukan),
   id_tingkat_kompetensi (integer id dari daftar tingkat jika cocok, atau null),
   kutipan_dari_teks_mentah (string, substring verbatim dari teks mentah),
   alasan (string, Bahasa Indonesia, singkat),
   konfirmatori (string, jelaskan kaitan kutipan dengan indikator perilaku yang dipilih),
   keyakinan (angka 0 sampai 1).
4) Jika kutipan tidak verbatim, set keyakinan rendah dan jelaskan di alasan.
SYS;

        $penggunaMsg = "Kompetensi: {$kodeKompetensi} — {$namaKompetensi}\n"
            .'Alat: '.($bukti->tool?->kode ?? '')."\n\n"
            ."Daftar tingkat untuk kompetensi ini (pilih id_tingkat_kompetensi yang paling sesuai):\n{$ringkasanTingkat}\n\n"
            ."Teks mentah bukti:\n---\n{$teksMentah}\n---";

        $namaModel = (string) config('ai.openrouter.nama_model');
        $logBaru = new AiLog([
            'id_pengguna' => $pengguna->id,
            'id_asesmen' => $bukti->id_asesmen,
            'id_bukti_penilaian' => $bukti->id,
            'jalur' => 'analisis_bukti_incremental',
            'nama_model' => $namaModel,
            'status' => 'gagal',
            'metadata' => [],
        ]);

        try {
            ['response' => $response, 'latency_ms' => $latency] = $this->client->chatCompletion([
                ['role' => 'system', 'content' => $sistem],
                ['role' => 'user', 'content' => $penggunaMsg],
            ]);
        } catch (\Throwable $e) {
            $logBaru->fill([
                'pesan_kesalahan' => $e->getMessage(),
                'metadata' => ['latency_ms' => null],
            ]);
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            Log::warning('OpenRouter incremental gagal', ['exception' => $e->getMessage()]);

            return ['berhasil' => false, 'pesan' => 'Gagal menghubungi penyedia AI: '.$e->getMessage()];
        }

        $usage = OpenRouterClient::metadataUsage($response);
        $meta = array_filter([
            'latency_ms' => $latency,
            'usage' => $usage,
        ], static fn ($v) => $v !== null);

        $logBaru->kode_http = $response->status();
        $logBaru->metadata = $meta;

        if (! $response->successful()) {
            $logBaru->pesan_kesalahan = $response->body();
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            return ['berhasil' => false, 'pesan' => 'Respons penyedia AI tidak berhasil (HTTP '.$response->status().').'];
        }

        $jsonStr = OpenRouterClient::ekstrakIsiJson($response);
        $parsed = AiModelJsonParser::parseObjek($jsonStr);
        if ($parsed === null) {
            $logBaru->pesan_kesalahan = 'JSON model tidak valid.';
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            return ['berhasil' => false, 'pesan' => 'Model mengembalikan JSON yang tidak dapat dibaca.'];
        }
        $errorSchema = $this->validasiSchemaHasil($parsed);
        if ($errorSchema !== null) {
            $logBaru->pesan_kesalahan = $errorSchema;
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            return ['berhasil' => false, 'pesan' => 'Model mengembalikan format yang tidak valid: '.$errorSchema];
        }

        $kutipan = isset($parsed['kutipan_dari_teks_mentah']) ? (string) $parsed['kutipan_dari_teks_mentah'] : '';
        if ($kutipan === '') {
            $logBaru->pesan_kesalahan = 'Kutipan kosong.';
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            return ['berhasil' => false, 'pesan' => 'Model wajib mengembalikan kutipan verbatim dari teks mentah.'];
        }
        if (! $this->kutipanAdaDiTeksMentah($teksMentah, $kutipan)) {
            $logBaru->pesan_kesalahan = 'Kutipan tidak ditemukan verbatim di teks mentah.';
            $logBaru->dibuat_pada = now();
            $logBaru->save();

            return ['berhasil' => false, 'pesan' => 'Kutipan dari model tidak cocok dengan teks mentah (wajib substring verbatim).'];
        }

        $tingkatAngka = $parsed['tingkat'] ?? null;
        $idTingkat = isset($parsed['id_tingkat_kompetensi']) ? $parsed['id_tingkat_kompetensi'] : null;
        $alasan = isset($parsed['alasan']) ? (string) $parsed['alasan'] : '';
        $keyakinan = isset($parsed['keyakinan']) ? (float) $parsed['keyakinan'] : null;

        if (is_numeric($idTingkat)) {
            $cocok = $tingkatRows->firstWhere('id', (int) $idTingkat);
            if ($cocok === null) {
                $idTingkat = null;
            }
        } else {
            $idTingkat = null;
        }

        if ($tingkatAngka !== null && is_numeric($tingkatAngka)) {
            $tingkatAngka = (int) $tingkatAngka;
        } else {
            $tingkatAngka = null;
        }

        $aiTingkat = $tingkatAngka !== null ? (string) $tingkatAngka : 'tidak_tahu';
        $keyakinanNorm = $keyakinan !== null ? max(0.0, min(1.0, $keyakinan)) : null;

        $muatan = [
            'id_tingkat_kompetensi_usulan' => is_numeric($idTingkat) ? (int) $idTingkat : null,
            'kutipan_dari_teks_mentah' => $kutipan,
            'kutipan_terverifikasi' => $kutipan !== '',
            'konfirmatori' => isset($parsed['konfirmatori']) ? (string) $parsed['konfirmatori'] : null,
        ];

        DB::transaction(function () use ($bukti, $aiTingkat, $alasan, $keyakinanNorm, $muatan, $logBaru): void {
            $bukti->update([
                'ai_tingkat' => $aiTingkat,
                'ai_alasan' => $alasan,
                'ai_keyakinan' => $keyakinanNorm,
                'ai_muatan' => $muatan,
                'ai_dinilai_pada' => now(),
            ]);
            $logBaru->status = 'berhasil';
            $logBaru->pesan_kesalahan = null;
            $logBaru->dibuat_pada = now();
            $logBaru->save();
        });

        return ['berhasil' => true];
    }

    private function kutipanAdaDiTeksMentah(string $mentah, string $kutipan): bool
    {
        if ($kutipan === '') {
            return true;
        }
        if (str_contains($mentah, $kutipan)) {
            return true;
        }

        return mb_stripos($mentah, $kutipan) !== false;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function validasiSchemaHasil(array $parsed): ?string
    {
        foreach (['tingkat', 'id_tingkat_kompetensi', 'kutipan_dari_teks_mentah', 'alasan', 'konfirmatori', 'keyakinan'] as $kunci) {
            if (! array_key_exists($kunci, $parsed)) {
                return 'Kunci wajib hilang: '.$kunci;
            }
        }
        if (! is_string($parsed['kutipan_dari_teks_mentah']) || trim($parsed['kutipan_dari_teks_mentah']) === '') {
            return 'kutipan_dari_teks_mentah wajib string non-kosong.';
        }
        if (! is_string($parsed['alasan']) || trim($parsed['alasan']) === '') {
            return 'alasan wajib string non-kosong.';
        }
        if (! is_string($parsed['konfirmatori']) || trim($parsed['konfirmatori']) === '') {
            return 'konfirmatori wajib string non-kosong.';
        }
        if (! is_numeric($parsed['keyakinan'])) {
            return 'keyakinan wajib angka 0 sampai 1.';
        }
        if (! is_numeric($parsed['tingkat']) && $parsed['tingkat'] !== null) {
            return 'tingkat wajib integer 1-6 atau null.';
        }
        if (! is_numeric($parsed['id_tingkat_kompetensi']) && $parsed['id_tingkat_kompetensi'] !== null) {
            return 'id_tingkat_kompetensi wajib integer atau null.';
        }

        return null;
    }
}
