<?php

namespace App\Enums;

enum AssessmentStatus: string
{
    case Draf = 'draf';
    case Berlangsung = 'berlangsung';
    case Terintegrasi = 'terintegrasi';
    case SelesaiFinal = 'selesai_final';
}
