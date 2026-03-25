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

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationSettingsService::class)->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'assigned_by' => $this->assignedBy->name,
            'priority' => $this->task->priority->value ?? $this->task->priority,
            'due_date' => $this->task->due_date?->toDateString(),
            'message' => "Se te ha asignado la tarea \"{$this->task->title}\".",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(NotificationSettingsService::class);
        $template = $settings->getTemplate('new_assignment');

        if ($template) {
            $rendered = $settings->renderTemplate($template, [
                'task_title' => $this->task->title,
                'user_name' => $notifiable->name,
                'priority' => $this->task->priority->value ?? $this->task->priority,
                'due_date' => $this->task->due_date?->toDateString() ?? 'Sin fecha',
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject("Nueva tarea asignada: {$this->task->title}")
            ->line("Se te ha asignado la tarea \"{$this->task->title}\".")
            ->line("Prioridad: " . ($this->task->priority->value ?? $this->task->priority))
            ->line("Fecha límite: " . ($this->task->due_date?->toDateString() ?? 'Sin fecha'));
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
