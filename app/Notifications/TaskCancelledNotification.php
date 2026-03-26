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

class TaskCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Task $task,
        public User $cancelledBy,
        public ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationSettingsService::class)->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_cancelled',
            'category' => $this->task->area_id ? 'organizational' : 'personal',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'cancelled_by' => $this->cancelledBy->name,
            'note' => $this->note,
            'message' => "La tarea \"{$this->task->title}\" ha sido cancelada por {$this->cancelledBy->name}.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(NotificationSettingsService::class);
        $template = $settings->getTemplate('task_cancelled');

        if ($template) {
            $rendered = $settings->renderTemplate($template, [
                'task_title' => $this->task->title,
                'user_name' => $notifiable->name,
                'cancelled_by' => $this->cancelledBy->name,
                'note' => $this->note ?? 'Sin motivo especificado',
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        $mail = (new MailMessage)
            ->subject("Tarea cancelada: {$this->task->title}")
            ->line("La tarea \"{$this->task->title}\" ha sido cancelada por {$this->cancelledBy->name}.");

        if ($this->note) {
            $mail->line("Motivo: {$this->note}");
        }

        return $mail;
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
