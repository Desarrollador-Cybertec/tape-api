<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Enums\TaskStatusEnum;
use App\Models\Area;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduledCommandsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $superadminRole = Role::create(['name' => 'Super Administrador', 'slug' => RoleEnum::SUPERADMIN->value]);
        $workerRole = Role::create(['name' => 'Trabajador', 'slug' => RoleEnum::WORKER->value]);

        $this->admin = User::factory()->create([
            'role_id' => $superadminRole->id,
            'password' => Hash::make('Password1'),
        ]);

        $this->worker = User::factory()->create([
            'role_id' => $workerRole->id,
            'password' => Hash::make('Password1'),
        ]);
    }

    public function test_detect_overdue_marks_past_due_tasks(): void
    {
        $task = Task::create([
            'title' => 'Tarea vencida',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'due_date' => now()->subDays(3),
        ]);

        Artisan::call('tasks:detect-overdue');

        $this->assertEquals(TaskStatusEnum::OVERDUE, $task->fresh()->status);
        $this->assertDatabaseHas('task_status_history', [
            'task_id' => $task->id,
            'to_status' => TaskStatusEnum::OVERDUE->value,
        ]);
    }

    public function test_detect_overdue_ignores_completed_tasks(): void
    {
        $task = Task::create([
            'title' => 'Completada',
            'created_by' => $this->admin->id,
            'status' => TaskStatusEnum::COMPLETED,
            'due_date' => now()->subDays(3),
            'completed_at' => now()->subDay(),
        ]);

        Artisan::call('tasks:detect-overdue');

        $this->assertEquals(TaskStatusEnum::COMPLETED, $task->fresh()->status);
    }

    public function test_detect_overdue_ignores_future_due_tasks(): void
    {
        $task = Task::create([
            'title' => 'Futura',
            'created_by' => $this->admin->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'due_date' => now()->addDays(5),
        ]);

        Artisan::call('tasks:detect-overdue');

        $this->assertEquals(TaskStatusEnum::IN_PROGRESS, $task->fresh()->status);
    }

    public function test_daily_summary_creates_notifications(): void
    {
        Task::create([
            'title' => 'Pendiente 1',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
        ]);

        Task::create([
            'title' => 'Pendiente 2',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        Artisan::call('tasks:send-daily-summary');

        $this->assertDatabaseHas('task_notifications', [
            'notify_to_user_id' => $this->worker->id,
            'status' => 'sent',
        ]);
    }

    public function test_due_reminders_notifies_for_tasks_due_soon(): void
    {
        Task::create([
            'title' => 'Vence mañana',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'due_date' => now()->addDay(),
            'notify_on_due' => true,
        ]);

        Artisan::call('tasks:send-due-reminders');

        $this->assertDatabaseHas('task_notifications', [
            'notify_to_user_id' => $this->worker->id,
        ]);
    }

    public function test_due_reminders_ignores_tasks_without_flag(): void
    {
        Task::create([
            'title' => 'Sin flag',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'due_date' => now()->addDay(),
            'notify_on_due' => false,
        ]);

        Artisan::call('tasks:send-due-reminders');

        $this->assertDatabaseCount('task_notifications', 0);
    }
}
