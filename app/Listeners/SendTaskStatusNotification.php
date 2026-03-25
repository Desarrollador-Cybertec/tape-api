<?php

namespace App\Listeners;

use App\Enums\TaskStatusEnum;
use App\Events\TaskStatusChanged;
use App\Models\User;
use App\Notifications\TaskApprovedNotification;
use App\Notifications\TaskRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskStatusNotification implements ShouldQueue
{
    public function handle(TaskStatusChanged $event): void
    {
        $task = $event->task;
        $responsible = $task->current_responsible_user_id
            ? User::find($task->current_responsible_user_id)
            : null;

        if (!$responsible) {
            return;
        }

        // Don't notify the person who made the change
        if ($responsible->id === $event->changedBy->id) {
            return;
        }

        match ($event->toStatus) {
            TaskStatusEnum::COMPLETED->value => $responsible->notify(
                new TaskApprovedNotification($task, $event->changedBy)
            ),
            TaskStatusEnum::REJECTED->value => $responsible->notify(
                new TaskRejectedNotification($task, $event->changedBy, $event->note ?? 'Sin motivo especificado')
            ),
            default => null, // Other transitions don't trigger notifications
        };
    }
}
