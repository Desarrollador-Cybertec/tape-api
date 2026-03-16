<?php

namespace App\Console\Commands;

use App\Enums\TaskStatusEnum;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\TaskNotification;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'tasks:send-due-reminders';

    protected $description = 'Send reminders for tasks due soon or overdue with notify flags enabled';

    public function handle(): int
    {
        if (!SystemSetting::getValue('emails_enabled', true)) {
            $this->info('Correos automáticos desactivados.');
            return self::SUCCESS;
        }

        $count = 0;
        $alertDays = SystemSetting::getValue('alert_days_before_due', 3);

        // Due today or in N days with notify_on_due
        $dueSoon = Task::where('notify_on_due', true)
            ->whereNotIn('status', [
                TaskStatusEnum::COMPLETED->value,
                TaskStatusEnum::CANCELLED->value,
            ])
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now()->startOfDay())
            ->where('due_date', '<=', now()->addDays($alertDays)->endOfDay())
            ->whereNotNull('current_responsible_user_id')
            ->get();

        foreach ($dueSoon as $task) {
            $daysLeft = now()->startOfDay()->diffInDays($task->due_date, false);
            $message = $daysLeft === 0
                ? "La tarea \"{$task->title}\" vence hoy."
                : "La tarea \"{$task->title}\" vence en {$daysLeft} día(s).";

            TaskNotification::create([
                'task_id' => $task->id,
                'triggered_by' => $task->created_by,
                'notify_to_user_id' => $task->current_responsible_user_id,
                'channel' => 'database',
                'message' => $message,
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            $count++;
        }

        // Overdue with notify_on_overdue
        $overdue = Task::where('notify_on_overdue', true)
            ->whereNotIn('status', [
                TaskStatusEnum::COMPLETED->value,
                TaskStatusEnum::CANCELLED->value,
            ])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->whereNotNull('current_responsible_user_id')
            ->get();

        foreach ($overdue as $task) {
            $daysOverdue = $task->due_date->diffInDays(now());

            TaskNotification::create([
                'task_id' => $task->id,
                'triggered_by' => $task->created_by,
                'notify_to_user_id' => $task->current_responsible_user_id,
                'channel' => 'database',
                'message' => "La tarea \"{$task->title}\" está vencida por {$daysOverdue} día(s).",
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            $count++;
        }

        $this->info("Se enviaron {$count} recordatorios.");

        return self::SUCCESS;
    }
}
