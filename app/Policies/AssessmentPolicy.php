<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    private function boleh(User $user): bool
    {
        return in_array($user->role, ['admin', 'konsultan'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->boleh($user);
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $this->boleh($user);
    }

    public function create(User $user): bool
    {
        return $this->boleh($user);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $this->boleh($user);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->role === 'admin';
    }
}
