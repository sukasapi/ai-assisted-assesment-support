<?php

namespace App\Policies;

use App\Models\AssessmentSession;
use App\Models\User;

class AssessmentSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, AssessmentSession $session): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, AssessmentSession $session): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, AssessmentSession $session): bool
    {
        return $user->role === 'admin' && $session->kode_sesi !== 'SES-LEGACY';
    }
}
