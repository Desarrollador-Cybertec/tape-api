<?php

namespace App\Listeners;

use App\Events\TaskDelegated;
use App\Notifications\TaskDelegatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskDelegatedNotification implements ShouldQueue
{
    public function handle(TaskDelegated $event): void
    {
        // Don't notify if delegating to yourself
        if ($event->delegatedTo->id === $event->delegatedBy->id) {
            return;
        }

        $event->delegatedTo->notify(
            new TaskDelegatedNotification($event->task, $event->delegatedBy)
        );
    }
}
