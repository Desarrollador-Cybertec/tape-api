<?php

namespace App\Notifications;

use App\Services\NotificationSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class TaskInactivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Collection<int, array{task_id: int, task_title: string, days_inactive: int, due_date: ?string}> $inactiveTasks
     */
    public function __construct(
        public Collection $inactiveTasks,
        public int $inactivityDays,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationSettingsService::class)->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inactivity_alert',
            'inactivity_days' => $this->inactivityDays,
            'task_count' => $this->inactiveTasks->count(),
            'tasks' => $this->inactiveTasks->toArray(),
            'message' => "Tienes {$this->inactiveTasks->count()} tarea(s) sin avance en los últimos {$this->inactivityDays} días.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(NotificationSettingsService::class);
        $template = $settings->getTemplate('inactivity_alert');

        $taskList = $this->inactiveTasks->map(
            fn (array $t) => "- {$t['task_title']} ({$t['days_inactive']} días sin avance)"
        )->implode("\n");

        if ($template) {
            $rendered = $settings->renderTemplate($template, [
                'user_name' => $notifiable->name,
                'inactivity_days' => $this->inactivityDays,
                'task_list' => $taskList,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject("Alerta: Tareas sin avance desde hace {$this->inactivityDays} días")
            ->line("Tienes {$this->inactiveTasks->count()} tarea(s) sin avance:")
            ->line($taskList);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
