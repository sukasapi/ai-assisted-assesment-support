<?php

namespace App\Policies;

use App\Enums\AssessorAssignmentType;
use App\Models\Assessment;
use App\Models\User;
use App\Support\ConsultantAccessSession;

class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'konsultan'], true);
    }

    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $this->konsultanBolehAkses($user, $assessment);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Assessment $assessment): bool
    {
        if ($user->role === 'konsultan') {
            return $this->konsultanBolehAkses($user, $assessment);
        }

        return $this->adminDitugaskan($user, $assessment);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->role === 'admin';
    }

    private function adminDitugaskan(User $user, Assessment $assessment): bool
    {
        return $assessment->assessorAssignments()
            ->where('id_pengguna', $user->id)
            ->where('jenis_penugasan', AssessorAssignmentType::Admin->value)
            ->exists();
    }

    private function konsultanBolehAkses(User $user, Assessment $assessment): bool
    {
        $penugasan = ConsultantAccessSession::penugasanAktif($user);
        if ($penugasan === null) {
            return false;
        }

        return $penugasan->assessments()->whereKey($assessment->id)->exists();
    }
}
