<?php

namespace Tests\Unit;

use App\Support\BulkTextNormalizer;
use Tests\TestCase;

class BulkTextNormalizerTest extends TestCase
{
    public function test_tidak_mengecilkan_spasi_berlebih(): void
    {
        $asli = "baris  dengan   dua spasi";
        $this->assertSame($asli, BulkTextNormalizer::normalizeForStorage($asli));
    }

    public function test_kutipan_curly_quote_cocok_dengan_teks_lurus(): void
    {
        $haystack = 'Peserta berkata "saya siap" untuk tugas.';
        $kutipan = 'Peserta berkata “saya siap” untuk tugas.';

        $this->assertTrue(BulkTextNormalizer::kutipanCocok($haystack, $kutipan));
        $verbatim = BulkTextNormalizer::findVerbatimSubstring($haystack, $kutipan);
        $this->assertNotNull($verbatim);
        $this->assertStringContainsString('"saya siap"', $verbatim);
    }

    public function test_pergantian_paragraf_diseragamkan_ke_baris_kosong(): void
    {
        $asli = "Paragraf satu.\r\n\r\nParagraf dua.\n\n\nParagraf tiga.";
        $norm = BulkTextNormalizer::normalizeParagraphBreaks($asli);
        $this->assertSame("Paragraf satu.\n\nParagraf dua.\n\nParagraf tiga.", $norm);
    }

    public function test_html_paragraf_menjadi_pemisah_ganda(): void
    {
        $html = '<p>Baris A</p><p>Baris B</p>';
        $plain = \App\Support\EvidenceTextNormalizer::toPlainText($html, '');
        $this->assertStringContainsString("Baris A\n\nBaris B", $plain);
    }

    public function test_selesaikan_kutipan_mengembalikan_potongan_asli(): void
    {
        $teks = "Bukti: KUTIPAN BULK tes perilaku.";
        $hasil = BulkTextNormalizer::selesaikanKutipan([$teks], 'KUTIPAN BULK tes perilaku.');
        $this->assertNotNull($hasil);
        $this->assertSame('KUTIPAN BULK tes perilaku.', $hasil[1]);
    }
}
