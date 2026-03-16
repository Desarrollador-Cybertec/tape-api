<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAreaManager();
    }

    public function view(User $user, Meeting $meeting): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($meeting->area_id && $user->isManagerOfArea($meeting->area_id)) {
            return true;
        }

        return $meeting->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAreaManager();
    }

    public function update(User $user, Meeting $meeting): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $meeting->created_by === $user->id;
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->isSuperAdmin();
    }
}
