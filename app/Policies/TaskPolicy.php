<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($task->created_by === $user->id
            || $task->assigned_to_user_id === $user->id
            || $task->current_responsible_user_id === $user->id) {
            return true;
        }

        if ($task->area_id && $user->isManagerOfArea($task->area_id)) {
            // Workers' self-created tasks are personal and not visible to area managers
            return !DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.id', $task->created_by)
                ->where('roles.slug', RoleEnum::WORKER->value)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($task->area_id && $user->isManagerOfArea($task->area_id)) {
            return true;
        }

        return false;
    }

    public function delegate(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $task->area_id && $user->isManagerOfArea($task->area_id);
    }

    public function start(User $user, Task $task): bool
    {
        return $task->current_responsible_user_id === $user->id;
    }

    public function submitForReview(User $user, Task $task): bool
    {
        return $task->current_responsible_user_id === $user->id;
    }

    public function approve(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $task->area_id && $user->isManagerOfArea($task->area_id);
    }

    public function reject(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $task->area_id && $user->isManagerOfArea($task->area_id);
    }

    public function cancel(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $task->created_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isSuperAdmin();
    }

    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function addAttachment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }
}
