<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Mail\ExternalTaskMail;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TaskCreationService
{
    public function create(array $data, User $creator): Task
    {
        return DB::transaction(function () use ($data, $creator) {
            $isExternalTask = !empty($data['external_email']);
            $isAreaAssignment = !empty($data['assigned_to_area_id']) && empty($data['assigned_to_user_id']);

            // Resolve area_id: use explicit area, or derive from the assigned user's active area.
            // Exception: self-assignment (creator assigns to themselves) creates a personal task
            // with no area, so it won't appear in any dashboard.
            $areaId = $data['assigned_to_area_id'] ?? null;
            $isSelfAssignment = !empty($data['assigned_to_user_id'])
                && (int) $data['assigned_to_user_id'] === $creator->id;
            if (!$areaId && !empty($data['assigned_to_user_id']) && !$isSelfAssignment) {
                $areaId = DB::table('area_members')
                    ->where('user_id', $data['assigned_to_user_id'])
                    ->where('is_active', true)
                    ->value('area_id');
            }

            // Determine status
            if ($isExternalTask) {
                $status = TaskStatusEnum::PENDING;
            } elseif ($isAreaAssignment) {
                $status = TaskStatusEnum::PENDING_ASSIGNMENT;
            } else {
                $status = TaskStatusEnum::PENDING;
            }

            $task = Task::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'created_by' => $creator->id,
                'assigned_by' => $creator->id,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
                'assigned_to_area_id' => $data['assigned_to_area_id'] ?? null,
                'external_email' => $data['external_email'] ?? null,
                'external_name' => $data['external_name'] ?? null,
                'area_id' => $areaId,
                'current_responsible_user_id' => $data['assigned_to_user_id'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => $status,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'requires_attachment' => $data['requires_attachment'] ?? false,
                'requires_completion_comment' => $data['requires_completion_comment'] ?? false,
                'requires_manager_approval' => $data['requires_manager_approval'] ?? false,
                'requires_completion_notification' => $data['requires_completion_notification'] ?? false,
                'requires_due_date' => $data['requires_due_date'] ?? false,
                'requires_progress_report' => $data['requires_progress_report'] ?? false,
                'notify_on_due' => $data['notify_on_due'] ?? false,
                'notify_on_overdue' => $data['notify_on_overdue'] ?? false,
                'notify_on_completion' => $data['notify_on_completion'] ?? false,
                'meeting_id' => $data['meeting_id'] ?? null,
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

            // Send email to external recipient
            if ($isExternalTask) {
                Mail::to($data['external_email'])->queue(new ExternalTaskMail($task));
            }

            return $task;
        });
    }
}
