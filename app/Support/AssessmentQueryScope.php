<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class AssessmentQueryScope
{
    /**
     * @param  Builder<Assessment>  $query
     * @return Builder<Assessment>
     */
    public static function untukPengguna(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query;
        }

        $penugasan = ConsultantAccessSession::penugasanAktif($user);
        if ($penugasan === null) {
            return $query->whereRaw('0 = 1');
        }

        $ids = $penugasan->assessments()->pluck('ais_asesmen.id');

        return $query->whereIn('id', $ids->isEmpty() ? [-1] : $ids);
    }
}
