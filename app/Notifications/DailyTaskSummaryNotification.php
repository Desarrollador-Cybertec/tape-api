<?php

namespace App\Notifications;

use App\Services\NotificationSettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyTaskSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $summaryContent,
        public int $totalPending,
        public int $overdueCount,
        public int $dueSoonCount,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationSettingsService::class)->resolveChannels();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'daily_summary',
            'total_pending' => $this->totalPending,
            'overdue_count' => $this->overdueCount,
            'due_soon_count' => $this->dueSoonCount,
            'message' => "Resumen diario: {$this->totalPending} pendientes, {$this->overdueCount} vencidas, {$this->dueSoonCount} próximas a vencer.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $settings = app(NotificationSettingsService::class);
        $template = $settings->getTemplate('daily_summary');

        if ($template) {
            $rendered = $settings->renderTemplate($template, [
                'user_name' => $notifiable->name,
                'date' => now()->toDateString(),
                'summary_content' => $this->summaryContent,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject("Resumen diario de tareas — " . now()->toDateString())
            ->line($this->summaryContent);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
