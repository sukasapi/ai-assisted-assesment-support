<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $items = ActivityLog::query()
            ->with('user')
            ->latest('dibuat_pada')
            ->paginate(30);

        return view('master.activity-logs', compact('items'));
    }
}
