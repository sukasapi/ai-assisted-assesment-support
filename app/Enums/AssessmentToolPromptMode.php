<?php

namespace App\Enums;

enum AssessmentToolPromptMode: string
{
    case Master = 'master';
    case None = 'none';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Master => 'Ikuti master (default)',
            self::None => 'Tanpa template',
            self::Custom => 'Template khusus',
        };
    }
}
