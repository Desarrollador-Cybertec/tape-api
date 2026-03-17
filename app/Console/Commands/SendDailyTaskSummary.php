<?php

namespace App\Console\Commands;

use App\Enums\TaskStatusEnum;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\TaskNotification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendDailyTaskSummary extends Command
{
    protected $signature = 'tasks:send-daily-summary';

    protected $description = 'Generate consolidated task summaries grouped by responsible user';

    public function handle(): int
    {
        if (!SystemSetting::getValue('daily_summary_enabled', true)) {
            $this->info('Resumen diario desactivado.');
            return self::SUCCESS;
        }

        $activeTasks = Task::whereNotIn('status', [
                TaskStatusEnum::COMPLETED->value,
                TaskStatusEnum::CANCELLED->value,
            ])
            ->whereNotNull('current_responsible_user_id')
            ->with('currentResponsible')
            ->get()
            ->groupBy('current_responsible_user_id');

        $count = 0;
        $alertDays = SystemSetting::getValue('alert_days_before_due', 3);

        foreach ($activeTasks as $userId => $tasks) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $overdue = $tasks->filter(fn (Task $t) => $t->isOverdue());
            $dueSoon = $tasks->filter(fn (Task $t) =>
                $t->due_date !== null
                && $t->due_date->isFuture()
                && $t->due_date->diffInDays(now()) <= $alertDays
            );

            $message = $this->buildSummaryMessage($user, $tasks, $overdue, $dueSoon, $alertDays);

            TaskNotification::create([
                'task_id' => $tasks->first()->id,
                'triggered_by' => $user->id,
                'notify_to_user_id' => $user->id,
                'channel' => 'database',
                'message' => $message,
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            $count++;
        }

        $this->info("Resúmenes generados para {$count} usuarios.");

        return self::SUCCESS;
    }

    private function buildSummaryMessage(User $user, Collection $tasks, Collection $overdue, Collection $dueSoon, int $alertDays = 3): string
    {
        $lines = ["Resumen diario para {$user->name}:"];
        $lines[] = "Total pendientes: {$tasks->count()}";

        if ($overdue->isNotEmpty()) {
            $lines[] = "Vencidas: {$overdue->count()}";
        }

        if ($dueSoon->isNotEmpty()) {
            $lines[] = "Próximas a vencer ({$alertDays} días): {$dueSoon->count()}";
        }

        $withoutUpdates = $tasks->filter(fn (Task $t) => $t->updates()->count() === 0);
        if ($withoutUpdates->isNotEmpty()) {
            $lines[] = "Sin avance reportado: {$withoutUpdates->count()}";
        }

        $lines[] = '';

        // Order by age: oldest first (created_at ascending)
        $sorted = $tasks->sortBy('created_at');

        foreach ($sorted->take(15) as $task) {
            $status = $task->status->value;
            $due = $task->due_date ? $task->due_date->toDateString() : 'Sin fecha';
            $ageDays = (int) $task->created_at->diffInDays(now());

            $lastUpdate = $task->updates()->latest()->first();
            $daysSinceUpdate = $lastUpdate
                ? (int) $lastUpdate->created_at->diffInDays(now())
                : $ageDays;

            $inactivityFlag = $daysSinceUpdate >= 7 ? ' ⚠' : '';
            $lines[] = "- [{$status}] {$task->title} (Antigüedad: {$ageDays}d, Sin avance: {$daysSinceUpdate}d, Vence: {$due}){$inactivityFlag}";
        }

        if ($tasks->count() > 15) {
            $remaining = $tasks->count() - 15;
            $lines[] = "... y {$remaining} más.";
        }

        return implode("\n", $lines);
    }
}
