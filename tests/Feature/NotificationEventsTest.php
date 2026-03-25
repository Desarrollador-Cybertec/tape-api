<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Enums\TaskStatusEnum;
use App\Events\TaskAssigned;
use App\Events\TaskCommentAdded;
use App\Events\TaskDelegated;
use App\Events\TaskStatusChanged;
use App\Models\Area;
use App\Models\AreaMember;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationEventsTest extends TestCase
{
    use RefreshDatabase;

    private array $roles = [];
    private User $admin;
    private User $manager;
    private User $worker;
    private User $worker2;
    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles['superadmin'] = Role::create(['name' => 'Super Administrador', 'slug' => RoleEnum::SUPERADMIN->value]);
        $this->roles['area_manager'] = Role::create(['name' => 'Encargado de Área', 'slug' => RoleEnum::AREA_MANAGER->value]);
        $this->roles['worker'] = Role::create(['name' => 'Trabajador', 'slug' => RoleEnum::WORKER->value]);

        $this->admin = User::factory()->create([
            'role_id' => $this->roles['superadmin']->id,
            'password' => Hash::make('Password1'),
        ]);

        $this->manager = User::factory()->create([
            'role_id' => $this->roles['area_manager']->id,
            'password' => Hash::make('Password1'),
        ]);

        $this->worker = User::factory()->create([
            'role_id' => $this->roles['worker']->id,
            'password' => Hash::make('Password1'),
        ]);

        $this->worker2 = User::factory()->create([
            'role_id' => $this->roles['worker']->id,
            'password' => Hash::make('Password1'),
        ]);

        $this->area = Area::create([
            'name' => 'Área Test',
            'manager_user_id' => $this->manager->id,
        ]);

        AreaMember::create([
            'area_id' => $this->area->id,
            'user_id' => $this->worker->id,
            'assigned_by' => $this->admin->id,
            'joined_at' => now(),
            'is_active' => true,
        ]);

        AreaMember::create([
            'area_id' => $this->area->id,
            'user_id' => $this->worker2->id,
            'assigned_by' => $this->admin->id,
            'joined_at' => now(),
            'is_active' => true,
        ]);
    }

    // ── TaskAssigned Event ──

    public function test_task_creation_dispatches_task_assigned_event(): void
    {
        Event::fake([TaskAssigned::class]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Nueva tarea',
                'assigned_to_user_id' => $this->worker->id,
            ]);

        Event::assertDispatched(TaskAssigned::class, function ($event) {
            return $event->task->title === 'Nueva tarea'
                && $event->assignedBy->id === $this->admin->id;
        });
    }

    public function test_task_assigned_event_not_dispatched_for_area_assignment(): void
    {
        Event::fake([TaskAssigned::class]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Tarea de área',
                'assigned_to_area_id' => $this->area->id,
            ]);

        // Area assignments have no current_responsible_user_id → no event
        Event::assertNotDispatched(TaskAssigned::class);
    }

    // ── TaskDelegated Event ──

    public function test_delegation_dispatches_task_delegated_event(): void
    {
        Event::fake([TaskDelegated::class]);

        $task = Task::create([
            'title' => 'Tarea a delegar',
            'created_by' => $this->admin->id,
            'assigned_to_user_id' => $this->worker->id,
            'current_responsible_user_id' => $this->worker->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/delegate", [
                'to_user_id' => $this->worker2->id,
            ]);

        Event::assertDispatched(TaskDelegated::class, function ($event) {
            return $event->delegatedBy->id === $this->manager->id
                && $event->delegatedTo->id === $this->worker2->id;
        });
    }

    // ── TaskStatusChanged Event ──

    public function test_starting_task_dispatches_status_changed_event(): void
    {
        Event::fake([TaskStatusChanged::class]);

        $task = Task::create([
            'title' => 'Tarea pendiente',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/start");

        Event::assertDispatched(TaskStatusChanged::class, function ($event) {
            return $event->fromStatus === TaskStatusEnum::PENDING->value
                && $event->toStatus === TaskStatusEnum::IN_PROGRESS->value;
        });
    }

    public function test_approving_task_dispatches_status_changed_event(): void
    {
        Event::fake([TaskStatusChanged::class]);

        $task = Task::create([
            'title' => 'Tarea en revisión',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::IN_REVIEW,
            'requires_manager_approval' => true,
        ]);

        $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/approve");

        Event::assertDispatched(TaskStatusChanged::class, function ($event) {
            return $event->toStatus === TaskStatusEnum::COMPLETED->value;
        });
    }

    public function test_rejecting_task_dispatches_status_changed_event(): void
    {
        Event::fake([TaskStatusChanged::class]);

        $task = Task::create([
            'title' => 'Tarea en revisión',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::IN_REVIEW,
            'requires_manager_approval' => true,
        ]);

        $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/reject", [
                'note' => 'Falta evidencia',
            ]);

        Event::assertDispatched(TaskStatusChanged::class, function ($event) {
            return $event->toStatus === TaskStatusEnum::REJECTED->value
                && $event->note === 'Falta evidencia';
        });
    }

    // ── TaskCommentAdded Event ──

    public function test_adding_comment_dispatches_comment_added_event(): void
    {
        Event::fake([TaskCommentAdded::class]);

        $task = Task::create([
            'title' => 'Tarea con comentario',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
        ]);

        $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/comment", [
                'comment' => 'Esto es un avance',
            ]);

        Event::assertDispatched(TaskCommentAdded::class, function ($event) {
            return $event->comment->comment === 'Esto es un avance'
                && $event->commentBy->id === $this->worker->id;
        });
    }
}
