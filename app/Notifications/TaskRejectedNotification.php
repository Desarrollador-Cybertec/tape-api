<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use App\Services\NotificationSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $rejectedBy,
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationSettingsService::class)->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_rejected',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'rejected_by' => $this->rejectedBy->name,
            'reason' => $this->reason,
            'message' => "La tarea \"{$this->task->title}\" ha sido rechazada: {$this->reason}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(NotificationSettingsService::class);
        $template = $settings->getTemplate('task_rejected');

        if ($template) {
            $rendered = $settings->renderTemplate($template, [
                'task_title' => $this->task->title,
                'user_name' => $notifiable->name,
                'rejection_reason' => $this->reason,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject("Tarea rechazada: {$this->task->title}")
            ->line("La tarea \"{$this->task->title}\" necesita correcciones.")
            ->line("Motivo: {$this->reason}");
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
