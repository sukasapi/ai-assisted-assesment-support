<?php

namespace Tests\Unit;

use App\Enums\AssessmentToolPromptMode;
use App\Models\AiPromptTemplate;
use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Support\AiPromptTemplateResolver;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class AiPromptTemplateResolverTest extends TestCase
{

    public function test_master_star_hanya_untuk_bei(): void
    {
        $this->seed(DatabaseSeeder::class);

        $star = AiPromptTemplate::query()->where('kode', 'STAR')->firstOrFail();
        $bei = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $pa = AssessmentTool::query()->where('kode', 'PA')->first();

        $asesmen = Assessment::query()->firstOrFail();

        $teksBei = AiPromptTemplateResolver::teksInstruksiUntukAlat($asesmen, (int) $bei->id);
        $this->assertStringContainsString('STAR', $teksBei);

        if ($pa !== null) {
            $teksPa = AiPromptTemplateResolver::teksInstruksiUntukAlat($asesmen, (int) $pa->id);
            $this->assertSame('', $teksPa);
        }

        $this->assertSame($star->id, AiPromptTemplateResolver::templateEfektif($asesmen, (int) $bei->id)?->id);
    }

    public function test_override_per_asesmen_menonaktifkan_star_di_bei(): void
    {
        $this->seed(DatabaseSeeder::class);

        $bei = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $asesmen = Assessment::query()->firstOrFail();

        AiPromptTemplateResolver::simpanOverride(
            $asesmen->id,
            (int) $bei->id,
            AssessmentToolPromptMode::None,
        );

        $this->assertSame('', AiPromptTemplateResolver::teksInstruksiUntukAlat($asesmen, (int) $bei->id));
    }
}
