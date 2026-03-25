<?php

namespace App\Listeners;

use App\Enums\TaskStatusEnum;
use App\Events\TaskStatusChanged;
use App\Models\User;
use App\Notifications\TaskApprovedNotification;
use App\Notifications\TaskCancelledNotification;
use App\Notifications\TaskCompletedNotification;
use App\Notifications\TaskRejectedNotification;
use App\Notifications\TaskReopenedNotification;
use App\Notifications\TaskSubmittedForReviewNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskStatusNotification implements ShouldQueue
{
    public function handle(TaskStatusChanged $event): void
    {
        $task = $event->task;
        $changedBy = $event->changedBy;

        match ($event->toStatus) {
            TaskStatusEnum::IN_REVIEW->value => $this->handleSubmittedForReview($task, $changedBy),
            TaskStatusEnum::COMPLETED->value => $this->handleCompleted($task, $changedBy),
            TaskStatusEnum::REJECTED->value => $this->handleRejected($task, $changedBy, $event->note),
            TaskStatusEnum::CANCELLED->value => $this->handleCancelled($task, $changedBy, $event->note),
            TaskStatusEnum::PENDING->value,
            TaskStatusEnum::IN_PROGRESS->value => $this->handlePossibleReopen($task, $changedBy, $event->fromStatus, $event->note),
            default => null,
        };
    }

    /**
     * Worker submits task for review → notify area manager.
     */
    private function handleSubmittedForReview($task, User $submittedBy): void
    {
        $this->notifyAreaManager(
            $task,
            $submittedBy,
            new TaskSubmittedForReviewNotification($task, $submittedBy)
        );
    }

    /**
     * Task completed:
     * - By someone else (manager approved) → notify responsible worker (TaskApproved).
     * - By the responsible user (no approval needed) → notify area manager (TaskCompleted).
     */
    private function handleCompleted($task, User $changedBy): void
    {
        $responsible = $this->getResponsible($task);

        if ($responsible && $responsible->id !== $changedBy->id) {
            // Manager/admin approved → notify the worker
            $responsible->notify(new TaskApprovedNotification($task, $changedBy));
        } elseif ($responsible && $responsible->id === $changedBy->id) {
            // Worker completed without approval → notify area manager
            $this->notifyAreaManager(
                $task,
                $changedBy,
                new TaskCompletedNotification($task, $changedBy)
            );
        }
    }

    /**
     * Task rejected → notify responsible worker.
     */
    private function handleRejected($task, User $changedBy, ?string $note): void
    {
        $responsible = $this->getResponsible($task);

        if ($responsible && $responsible->id !== $changedBy->id) {
            $responsible->notify(
                new TaskRejectedNotification($task, $changedBy, $note ?? 'Sin motivo especificado')
            );
        }
    }

    /**
     * Task cancelled → notify responsible if different from canceller.
     */
    private function handleCancelled($task, User $cancelledBy, ?string $note): void
    {
        $responsible = $this->getResponsible($task);

        if ($responsible && $responsible->id !== $cancelledBy->id) {
            $responsible->notify(new TaskCancelledNotification($task, $cancelledBy, $note));
        }
    }

    /**
     * Task reopened from a terminal state → notify responsible if different.
     */
    private function handlePossibleReopen($task, User $reopenedBy, string $fromStatus, ?string $note): void
    {
        $terminalStatuses = [
            TaskStatusEnum::COMPLETED->value,
            TaskStatusEnum::CANCELLED->value,
            TaskStatusEnum::OVERDUE->value,
        ];

        if (!in_array($fromStatus, $terminalStatuses)) {
            return; // Normal transition (e.g., PENDING → IN_PROGRESS), not a reopen
        }

        $responsible = $this->getResponsible($task);

        if ($responsible && $responsible->id !== $reopenedBy->id) {
            $responsible->notify(new TaskReopenedNotification($task, $reopenedBy, $note));
        }
    }

    /**
     * Notify the area manager of the task (if it's an organizational task).
     */
    private function notifyAreaManager($task, User $excludeUser, $notification): void
    {
        if (!$task->area_id) {
            return; // Personal task — no manager to notify
        }

        $manager = $task->area?->manager;

        if ($manager && $manager->id !== $excludeUser->id) {
            $manager->notify($notification);
        }
    }

    private function getResponsible($task): ?User
    {
        return $task->current_responsible_user_id
            ? User::find($task->current_responsible_user_id)
            : null;
    }
}
