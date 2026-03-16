<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskCreationService
{
    public function create(array $data, User $creator): Task
    {
        return DB::transaction(function () use ($data, $creator) {
            $isAreaAssignment = !empty($data['assigned_to_area_id']) && empty($data['assigned_to_user_id']);

            $task = Task::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'created_by' => $creator->id,
                'assigned_by' => $creator->id,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'assigned_to_area_id' => $data['assigned_to_area_id'] ?? null,
                'area_id' => $data['assigned_to_area_id'] ?? null,
                'current_responsible_user_id' => $data['assigned_to_user_id'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => $isAreaAssignment
                    ? TaskStatusEnum::PENDING_ASSIGNMENT
                    : TaskStatusEnum::PENDING,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'requires_attachment' => $data['requires_attachment'] ?? false,
                'requires_completion_comment' => $data['requires_completion_comment'] ?? false,
                'requires_manager_approval' => $data['requires_manager_approval'] ?? false,
                'requires_completion_notification' => $data['requires_completion_notification'] ?? false,
                'requires_due_date' => $data['requires_due_date'] ?? false,
            ]);

            TaskStatusHistory::create([
                'task_id' => $task->id,
                'changed_by' => $creator->id,
                'from_status' => null,
                'to_status' => $task->status,
                'note' => 'Tarea creada',
            ]);

            ActivityLog::create([
                'user_id' => $creator->id,
                'module' => 'tasks',
                'action' => 'created',
                'subject_type' => Task::class,
                'subject_id' => $task->id,
                'description' => "Tarea \"{$task->title}\" creada",
            ]);

            return $task;
        });
    }
}
