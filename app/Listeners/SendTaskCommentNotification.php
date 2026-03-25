<?php

namespace App\Listeners;

use App\Events\TaskCommentAdded;
use App\Models\User;
use App\Notifications\TaskCommentAddedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTaskCommentNotification implements ShouldQueue
{
    public function handle(TaskCommentAdded $event): void
    {
        $task = $event->task;
        $commentBy = $event->commentBy;

        // Notify the responsible user (if different from commenter)
        $responsible = $task->current_responsible_user_id
            ? User::find($task->current_responsible_user_id)
            : null;

        if ($responsible && $responsible->id !== $commentBy->id) {
            $responsible->notify(
                new TaskCommentAddedNotification($task, $commentBy, $event->comment->comment)
            );
        }

        // Notify the creator (if different from commenter and responsible)
        $creator = User::find($task->created_by);
        if ($creator
            && $creator->id !== $commentBy->id
            && (!$responsible || $creator->id !== $responsible->id)
        ) {
            $creator->notify(
                new TaskCommentAddedNotification($task, $commentBy, $event->comment->comment)
            );
        }
    }
}
