<?php

namespace App\Notifications;

use App\Models\Task;
use App\Services\NotificationSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public int $daysOverdue,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationSettingsService::class)->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_overdue',
            'category' => $this->task->area_id ? 'organizational' : 'personal',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'days_overdue' => $this->daysOverdue,
            'due_date' => $this->task->due_date?->toDateString(),
            'message' => "La tarea \"{$this->task->title}\" está vencida por {$this->daysOverdue} día(s).",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(NotificationSettingsService::class);
        $template = $settings->getTemplate('task_overdue');

        if ($template) {
            $rendered = $settings->renderTemplate($template, [
                'task_title' => $this->task->title,
                'user_name' => $notifiable->name,
                'days_overdue' => $this->daysOverdue,
                'due_date' => $this->task->due_date?->toDateString() ?? 'Sin fecha',
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject("Tarea vencida: {$this->task->title}")
            ->line("La tarea \"{$this->task->title}\" está vencida por {$this->daysOverdue} día(s).");
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
