<?php

namespace Tests\Unit;

use App\Enums\AssessmentPurpose;
use App\Services\Assessment\AlatAsesmenPreset;
use PHPUnit\Framework\TestCase;

class AlatAsesmenPresetTest extends TestCase
{
    public function test_promosi_includes_ra_not_in_talenta(): void
    {
        $promosi = AlatAsesmenPreset::kodeAlat(AssessmentPurpose::Promosi, false);
        $talenta = AlatAsesmenPreset::kodeAlat(AssessmentPurpose::PemetaanTalenta, false);

        $this->assertContains('RA', $promosi);
        $this->assertNotContains('RA', $talenta);
        $this->assertContains('MI', $promosi);
        $this->assertContains('MI', $talenta);
    }

    public function test_tanpa_intray_removes_intray(): void
    {
        $codes = AlatAsesmenPreset::kodeAlat(AssessmentPurpose::Promosi, true);

        $this->assertNotContains('INTRAY', $codes);
        $this->assertContains('BEI', $codes);
    }
}
