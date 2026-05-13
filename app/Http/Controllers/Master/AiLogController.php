<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AiLogController extends Controller
{
    public function index(): View
    {
        $status = request()->query('status');
        $jalur = request()->query('jalur');

        $query = AiLog::query()
            ->with('user')
            ->latest('dibuat_pada');

        if (is_string($status) && in_array($status, ['berhasil', 'gagal'], true)) {
            $query->where('status', $status);
        }
        if (is_string($jalur) && $jalur !== '') {
            $query->where('jalur', $jalur);
        }

        $items = $query->paginate(30)->withQueryString();

        $failedJobCount = DB::table('failed_jobs')->count();
        $jalurTersedia = AiLog::query()
            ->select('jalur')
            ->distinct()
            ->orderBy('jalur')
            ->pluck('jalur');

        return view('master.ai-logs', [
            'items' => $items,
            'failedJobCount' => $failedJobCount,
            'jalurTersedia' => $jalurTersedia,
        ]);
    }
}
