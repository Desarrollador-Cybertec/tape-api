<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Services\NotificationSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $completedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationSettingsService::class)->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_completed',
            'category' => $this->task->area_id ? 'organizational' : 'personal',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'completed_by' => $this->completedBy->name,
            'message' => "{$this->completedBy->name} completó la tarea \"{$this->task->title}\".",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(NotificationSettingsService::class);
        $template = $settings->getTemplate('task_completed');

        if ($template) {
            $rendered = $settings->renderTemplate($template, [
                'task_title' => $this->task->title,
                'user_name' => $notifiable->name,
                'completed_by' => $this->completedBy->name,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject("Tarea completada: {$this->task->title}")
            ->line("{$this->completedBy->name} completó la tarea \"{$this->task->title}\".");
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
