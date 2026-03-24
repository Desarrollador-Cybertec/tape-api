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

    private function isForeignPersonalTask(User $user, Task $task): bool
    {
        return is_null($task->area_id) && $task->created_by !== $user->id;
    }

    /**
     * Returns true if $manager manages the active area of the given user.
     * Used for cross-area task scenarios (e.g. Ventas assigns a task to a Desarrollo worker).
     */
    private function isManagerOfUserArea(User $manager, ?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        return DB::table('area_members')
            ->join('areas', 'area_members.area_id', '=', 'areas.id')
            ->where('area_members.user_id', $userId)
            ->where('area_members.is_active', true)
            ->where('areas.manager_user_id', $manager->id)
            ->exists();
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return !$this->isForeignPersonalTask($user, $task);
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
            return !$this->isForeignPersonalTask($user, $task);
        }

        if ($task->area_id && $user->isManagerOfArea($task->area_id)) {
            return true;
        }

        // Workers can edit their own personal tasks (no area)
        if (is_null($task->area_id) && $task->created_by === $user->id) {
            return true;
        }

        return false;
    }

    public function delegate(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return !$this->isForeignPersonalTask($user, $task);
        }

        if (!$task->area_id) {
            return false;
        }

        return $user->isManagerOfArea($task->area_id)
            || ($user->isAreaManager() && $task->current_responsible_user_id === $user->id)
            // Cross-area: allow if the current responsible belongs to one of the manager's areas
            || $this->isManagerOfUserArea($user, $task->current_responsible_user_id)
            // Creator of the task can delegate it
            || ($user->isAreaManager() && $task->created_by === $user->id);
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
            return !$this->isForeignPersonalTask($user, $task);
        }

        return $task->area_id && $user->isManagerOfArea($task->area_id);
    }

    public function reject(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return !$this->isForeignPersonalTask($user, $task);
        }

        return $task->area_id && $user->isManagerOfArea($task->area_id);
    }

    public function cancel(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return !$this->isForeignPersonalTask($user, $task);
        }

        return $task->created_by === $user->id;
    }

    public function reopen(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return !$this->isForeignPersonalTask($user, $task);
        }

        if ($task->area_id && $user->isManagerOfArea($task->area_id)) {
            return true;
        }

        // Workers can reopen tasks they created or are responsible for
        return $task->created_by === $user->id
            || $task->current_responsible_user_id === $user->id;
    }

    public function claim(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return !$this->isForeignPersonalTask($user, $task);
        }

        if ($task->area_id && $user->isManagerOfArea($task->area_id)) {
            return true;
        }

        // Cross-area: allow if the assigned user belongs to one of the manager's areas
        if ($this->isManagerOfUserArea($user, $task->assigned_to_user_id)) {
            return true;
        }

        // Creator of the task can claim it
        return $user->isAreaManager() && $task->created_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        if ($user->isSuperAdmin()) {
            return !$this->isForeignPersonalTask($user, $task);
        }

        // Workers can delete their own personal tasks (no area)
        return is_null($task->area_id) && $task->created_by === $user->id;
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
