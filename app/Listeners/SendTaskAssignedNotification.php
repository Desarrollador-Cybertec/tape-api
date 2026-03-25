<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\NotificationSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskAssignedNotification implements ShouldQueue
{
    public function handle(TaskAssigned $event): void
    {
        $task = $event->task;
        $assignedTo = $task->current_responsible_user_id
            ? User::find($task->current_responsible_user_id)
            : null;

        if (!$assignedTo || $assignedTo->id === $event->assignedBy->id) {
            return; // Don't notify self-assignment
        }

        $assignedTo->notify(new TaskAssignedNotification($task, $event->assignedBy));

        // Copy to manager if enabled
        $settings = app(NotificationSettingsService::class);

        if ($settings->shouldCopyManager() && $task->area_id) {
            $manager = $task->area?->manager;
            if ($manager && $manager->id !== $assignedTo->id && $manager->id !== $event->assignedBy->id) {
                $manager->notify(new TaskAssignedNotification($task, $event->assignedBy));
            }
        }
    }
}
