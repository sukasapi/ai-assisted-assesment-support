<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\TableSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::query()
            ->with('user')
            ->latest('dibuat_pada');

        TableSearch::apply($query, $request->query('q'), [
            'aksi',
            'subjek_tipe',
            'alamat_ip',
            fn ($q, $term) => $q->orWhereHas('user', fn ($u) => $u
                ->where('nama', 'like', '%'.$term.'%')
                ->orWhere('alamat_surel', 'like', '%'.$term.'%')),
        ]);

        $items = $query->paginate(30)->withQueryString();

        return view('master.activity-logs', compact('items'));
    }
}
