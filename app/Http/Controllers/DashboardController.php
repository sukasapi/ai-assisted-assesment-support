<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalAsesmen = Assessment::query()->count();
        $draf = Assessment::query()->where('status', AssessmentStatus::Draf)->count();
        $selesaiFinal = Assessment::query()->where('status', AssessmentStatus::SelesaiFinal)->count();

        return view('dashboard', [
            'totalAsesmen' => $totalAsesmen,
            'draf' => $draf,
            'selesaiFinal' => $selesaiFinal,
        ]);
    }
}
