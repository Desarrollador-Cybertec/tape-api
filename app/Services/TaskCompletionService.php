<?php

namespace App\Services;

use App\Enums\CommentTypeEnum;
use App\Enums\TaskStatusEnum;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskCompletionService
{
    public function __construct(
        private TaskStatusService $statusService,
    ) {}

    public function submitForReview(Task $task, User $user): Task
    {
        $this->validateRequirements($task);

        if ($task->requires_manager_approval) {
            return $this->statusService->transition(
                $task,
                TaskStatusEnum::IN_REVIEW,
                $user,
                'Enviada a revisión'
            );
        }

        return $this->statusService->transition(
            $task,
            TaskStatusEnum::COMPLETED,
            $user,
            'Tarea completada'
        );
    }

    public function approve(Task $task, User $approver, ?string $note = null): Task
    {
        return DB::transaction(function () use ($task, $approver, $note) {
            if ($note) {
                TaskComment::create([
                    'task_id' => $task->id,
                    'user_id' => $approver->id,
                    'comment' => $note,
                    'type' => CommentTypeEnum::COMPLETION_NOTE,
                ]);
            }

            return $this->statusService->transition(
                $task,
                TaskStatusEnum::COMPLETED,
                $approver,
                $note ?? 'Tarea aprobada'
            );
        });
    }

    public function reject(Task $task, User $rejector, string $note): Task
    {
        return DB::transaction(function () use ($task, $rejector, $note) {
            TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $rejector->id,
                'comment' => $note,
                'type' => CommentTypeEnum::REJECTION_NOTE,
            ]);

            return $this->statusService->transition(
                $task,
                TaskStatusEnum::REJECTED,
                $rejector,
                $note
            );
        });
    }

    private function validateRequirements(Task $task): void
    {
        $errors = [];

        if ($task->requires_attachment && $task->attachments()->count() === 0) {
            $errors['attachments'] = ['Se requiere al menos un adjunto para cerrar esta tarea.'];
        }

        if ($task->requires_completion_comment) {
            $hasCompletionNote = $task->comments()
                ->where('type', CommentTypeEnum::COMPLETION_NOTE)
                ->exists();

            if (!$hasCompletionNote) {
                $errors['comments'] = ['Se requiere un comentario de cierre para cerrar esta tarea.'];
            }
        }

        if ($task->requires_due_date && $task->due_date === null) {
            $errors['due_date'] = ['Se requiere fecha límite para cerrar esta tarea.'];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
