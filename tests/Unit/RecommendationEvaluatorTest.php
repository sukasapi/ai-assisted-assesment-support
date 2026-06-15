<?php

namespace Tests\Unit;

use App\Models\CompetencyIntegration;
use App\Services\Recommendation\RecommendationEvaluator;
use Tests\TestCase;

class RecommendationEvaluatorTest extends TestCase
{
    private RecommendationEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new RecommendationEvaluator;
    }

    public function test_lolos_perilaku_tanpa_tingkat_1_dan_maks_3_tingkat_2(): void
    {
        $konfigurasi = $this->konfigurasiPerilaku();
        $meta = collect([
            1 => ['id' => 1, 'kode_kompetensi' => 'A', 'kode_kelompok' => 'INT'],
            2 => ['id' => 2, 'kode_kompetensi' => 'B', 'kode_kelompok' => 'INT'],
            3 => ['id' => 3, 'kode_kompetensi' => 'C', 'kode_kelompok' => 'INT'],
        ]);
        $baris = collect([
            $this->baris(1, 3),
            $this->baris(2, 2),
            $this->baris(3, 2),
        ]);
        $wajib = collect([1, 2, 3]);

        $hasil = $this->evaluator->evaluasi($baris, $konfigurasi, $meta, $wajib);

        $this->assertSame('qualified', $hasil['kode']);
        $this->assertTrue($hasil['dimensi']['kompetensi_perilaku']['lolos']);
    }

    public function test_gagal_jika_ada_tingkat_1(): void
    {
        $konfigurasi = $this->konfigurasiPerilaku();
        $meta = collect([
            1 => ['id' => 1, 'kode_kompetensi' => 'A', 'kode_kelompok' => 'INT'],
        ]);
        $baris = collect([$this->baris(1, 1)]);
        $wajib = collect([1]);

        $hasil = $this->evaluator->evaluasi($baris, $konfigurasi, $meta, $wajib);

        $this->assertSame('not_qualified', $hasil['kode']);
        $this->assertFalse($hasil['dimensi']['kompetensi_perilaku']['lolos']);
    }

    public function test_gagal_jika_lebih_dari_3_tingkat_2(): void
    {
        $konfigurasi = $this->konfigurasiPerilaku();
        $meta = collect([
            1 => ['id' => 1, 'kode_kompetensi' => 'A', 'kode_kelompok' => 'INT'],
            2 => ['id' => 2, 'kode_kompetensi' => 'B', 'kode_kelompok' => 'INT'],
            3 => ['id' => 3, 'kode_kompetensi' => 'C', 'kode_kelompok' => 'INT'],
            4 => ['id' => 4, 'kode_kompetensi' => 'D', 'kode_kelompok' => 'INT'],
        ]);
        $baris = collect([
            $this->baris(1, 2),
            $this->baris(2, 2),
            $this->baris(3, 2),
            $this->baris(4, 2),
        ]);
        $wajib = collect([1, 2, 3, 4]);

        $hasil = $this->evaluator->evaluasi($baris, $konfigurasi, $meta, $wajib);

        $this->assertSame('not_qualified', $hasil['kode']);
    }

    public function test_gagal_kompetensi_wajib_tanpa_integrasi(): void
    {
        $konfigurasi = $this->konfigurasiPerilaku();
        $meta = collect([
            1 => ['id' => 1, 'kode_kompetensi' => 'A', 'kode_kelompok' => 'INT'],
            2 => ['id' => 2, 'kode_kompetensi' => 'B', 'kode_kelompok' => 'INT'],
        ]);
        $baris = collect([$this->baris(1, 4)]);
        $wajib = collect([1, 2]);

        $hasil = $this->evaluator->evaluasi($baris, $konfigurasi, $meta, $wajib);

        $this->assertFalse($hasil['dimensi']['kompetensi_perilaku']['lolos']);
        $this->assertNotEmpty($hasil['dimensi']['kompetensi_perilaku']['pelanggaran']);
    }

    public function test_min_tingkat_semua(): void
    {
        $konfigurasi = [
            'dimensi' => [[
                'kode' => 'kual',
                'label' => 'Kualifikasi',
                'filter_kelompok_kode' => ['KUAL'],
                'kriteria_qualified' => [
                    'logika' => 'and',
                    'aturan' => [['jenis' => 'min_tingkat_semua', 'tingkat' => 2]],
                ],
            ]],
            'hasil_agregat' => [
                'logika' => 'and',
                'label_qualified' => 'Qualified',
                'label_not_qualified' => 'Not Qualified',
            ],
        ];
        $meta = collect([
            10 => ['id' => 10, 'kode_kompetensi' => 'K1', 'kode_kelompok' => 'KUAL'],
        ]);
        $baris = collect([$this->baris(10, 1)]);
        $wajib = collect([10]);

        $hasil = $this->evaluator->evaluasi($baris, $konfigurasi, $meta, $wajib);

        $this->assertSame('not_qualified', $hasil['kode']);
    }

    /**
     * @return array<string, mixed>
     */
    private function konfigurasiPerilaku(): array
    {
        return [
            'dimensi' => [[
                'kode' => 'kompetensi_perilaku',
                'label' => 'Kompetensi Perilaku',
                'filter_kelompok_kode' => ['INT'],
                'kriteria_qualified' => [
                    'logika' => 'and',
                    'aturan' => [
                        ['jenis' => 'forbid_tingkat', 'tingkat' => 1],
                        ['jenis' => 'max_count_tingkat', 'tingkat' => 2, 'maksimum' => 3],
                    ],
                ],
            ]],
            'hasil_agregat' => [
                'logika' => 'and',
                'label_qualified' => 'Qualified',
                'label_not_qualified' => 'Not Qualified',
            ],
        ];
    }

    private function baris(int $idKompetensi, int $tingkat): CompetencyIntegration
    {
        $baris = new CompetencyIntegration;
        $baris->id_kompetensi = $idKompetensi;
        $baris->tingkat_tercapai = $tingkat;

        return $baris;
    }
}
