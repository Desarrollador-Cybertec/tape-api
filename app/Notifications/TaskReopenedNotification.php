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

class TaskReopenedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $reopenedBy,
        public ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationSettingsService::class)->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_reopened',
            'category' => $this->task->area_id ? 'organizational' : 'personal',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'reopened_by' => $this->reopenedBy->name,
            'note' => $this->note,
            'message' => "La tarea \"{$this->task->title}\" ha sido reabierta por {$this->reopenedBy->name}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(NotificationSettingsService::class);
        $template = $settings->getTemplate('task_reopened');

        if ($template) {
            $rendered = $settings->renderTemplate($template, [
                'task_title' => $this->task->title,
                'user_name' => $notifiable->name,
                'reopened_by' => $this->reopenedBy->name,
                'note' => $this->note ?? '',
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        $mail = (new MailMessage)
            ->subject("Tarea reabierta: {$this->task->title}")
            ->line("La tarea \"{$this->task->title}\" ha sido reabierta por {$this->reopenedBy->name}.");

        if ($this->note) {
            $mail->line("Nota: {$this->note}");
        }

        return $mail;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
