<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAreaManager();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->id === $model->id) {
            return true;
        }

        if ($user->isAreaManager()) {
            $managedAreaIds = $user->managedAreas()->pluck('id');
            return $model->activeAreas()->whereIn('areas.id', $managedAreaIds)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->id === $model->id;
    }

    public function updateRole(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->id !== $model->id;
    }
}
