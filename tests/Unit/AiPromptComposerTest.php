<?php

namespace Tests\Unit;

use App\Models\AiPromptTemplate;
use App\Models\Assessment;
use App\Support\AiPromptComposer;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class AiPromptComposerTest extends TestCase
{

    public function test_gabungkan_system_tanpa_template(): void
    {
        $this->seed(DatabaseSeeder::class);
        $asesmen = Assessment::query()->firstOrFail();

        $this->assertSame('Prompt dasar.', AiPromptComposer::gabungkanSystem('Prompt dasar.', $asesmen));
    }

    public function test_gabungkan_system_dengan_template(): void
    {
        $this->seed(DatabaseSeeder::class);

        $template = AiPromptTemplate::query()->where('kode', 'STAR')->firstOrFail();
        $asesmen = Assessment::query()->firstOrFail();
        $asesmen->update(['id_template_prompt_ai' => $template->id]);
        $asesmen->refresh();

        $bei = \App\Models\AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $hasil = AiPromptComposer::gabungkanSystem('Dasar.', $asesmen, (int) $bei->id);

        $this->assertStringContainsString('Dasar.', $hasil);
        $this->assertStringContainsString('Kerangka analisis tambahan', $hasil);
        $this->assertStringContainsString('STAR', $hasil);
    }
}
