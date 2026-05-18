<?php

namespace Tests\Unit;

use App\Services\Ai\AiModelJsonParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AiModelJsonParserTest extends TestCase
{
    #[DataProvider('objekValidProvider')]
    public function test_parse_objek_menerima_variasi_format(string $input): void
    {
        $hasil = AiModelJsonParser::parseObjek($input);
        $this->assertNotNull($hasil);
        $this->assertSame('nilai', $hasil['kunci']);
    }

    /**
     * @return list<array{string}>
     */
    public static function objekValidProvider(): array
    {
        return [
            ['{"kunci":"nilai"}'],
            ["```json\n{\"kunci\":\"nilai\"}\n```"],
            ["Berikut hasilnya:\n{\"kunci\":\"nilai\"}\nTerima kasih."],
        ];
    }

    public function test_ekstrak_usulan_dari_kunci_usulan(): void
    {
        $objek = ['usulan' => [['kode_kompetensi' => 'K1', 'kutipan' => 'x']]];
        $this->assertCount(1, AiModelJsonParser::ekstrakArrayUsulanBulk($objek));
    }

    public function test_ekstrak_usulan_dari_array_root(): void
    {
        $root = [['kode_kompetensi' => 'K1', 'kutipan' => 'x']];
        $this->assertSame($root, AiModelJsonParser::ekstrakArrayUsulanBulk($root));
    }
}
