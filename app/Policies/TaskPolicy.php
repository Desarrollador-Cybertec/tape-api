<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

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
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
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

    public function comment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function addAttachment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }
}
