<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        if ($user->id === $model->id) {
            return false;
        }

        if ($model->peran === 'admin') {
            $jumlahAdminAktif = User::query()
                ->where('peran', 'admin')
                ->where('aktif', true)
                ->count();

            return $jumlahAdminAktif > 1 || ! $model->aktif;
        }

        return true;
    }
}
