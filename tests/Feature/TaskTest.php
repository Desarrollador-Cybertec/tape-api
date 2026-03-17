<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Enums\TaskStatusEnum;
use App\Models\Area;
use App\Models\AreaMember;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private array $roles = [];
    private User $admin;
    private User $manager;
    private User $worker;
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
    }

    // ── Creation ──

    public function test_superadmin_can_create_task_assigned_to_user(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Tarea directa',
                'description' => 'Descripción',
                'assigned_to_user_id' => $this->worker->id,
                'priority' => 'high',
            ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Tarea directa')
            ->assertJsonPath('status', TaskStatusEnum::PENDING->value);
    }

    public function test_superadmin_can_create_task_assigned_to_area(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Tarea de área',
                'assigned_to_area_id' => $this->area->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('status', TaskStatusEnum::PENDING_ASSIGNMENT->value);
    }

    public function test_worker_cannot_create_task(): void
    {
        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Tarea ilegal',
                'assigned_to_user_id' => $this->worker->id,
            ]);

        $response->assertForbidden();
    }

    public function test_task_requires_title(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/tasks', [
                'assigned_to_user_id' => $this->worker->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_task_requires_assignee_or_area(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Sin asignar',
            ]);

        $response->assertUnprocessable();
    }

    // ── Delegation ──

    public function test_manager_can_delegate_area_task(): void
    {
        $task = Task::create([
            'title' => 'Tarea de área',
            'created_by' => $this->admin->id,
            'assigned_by' => $this->admin->id,
            'assigned_to_area_id' => $this->area->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::PENDING_ASSIGNMENT,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/delegate", [
                'to_user_id' => $this->worker->id,
                'note' => 'Por favor encárgate',
            ]);

        $response->assertOk();
        $this->assertEquals($this->worker->id, $task->fresh()->current_responsible_user_id);
        $this->assertEquals(TaskStatusEnum::PENDING, $task->fresh()->status);
    }

    public function test_cannot_delegate_to_user_outside_area(): void
    {
        $outsideWorker = User::factory()->create([
            'role_id' => $this->roles['worker']->id,
        ]);

        $task = Task::create([
            'title' => 'Tarea de área',
            'created_by' => $this->admin->id,
            'assigned_to_area_id' => $this->area->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::PENDING_ASSIGNMENT,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/delegate", [
                'to_user_id' => $outsideWorker->id,
            ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_delegate_completed_task(): void
    {
        $task = Task::create([
            'title' => 'Tarea completada',
            'created_by' => $this->admin->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::COMPLETED,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/delegate", [
                'to_user_id' => $this->worker->id,
            ]);

        $response->assertUnprocessable();
    }

    // ── Status Transitions ──

    public function test_worker_can_start_assigned_task(): void
    {
        $task = Task::create([
            'title' => 'Mi tarea',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/start");

        $response->assertOk();
        $this->assertEquals(TaskStatusEnum::IN_PROGRESS, $task->fresh()->status);
    }

    public function test_worker_cannot_start_other_users_task(): void
    {
        $otherWorker = User::factory()->create([
            'role_id' => $this->roles['worker']->id,
        ]);

        $task = Task::create([
            'title' => 'No mía',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $otherWorker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/start");

        $response->assertForbidden();
    }

    public function test_worker_can_submit_for_review(): void
    {
        $task = Task::create([
            'title' => 'Para revisar',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'requires_manager_approval' => true,
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/submit-review");

        $response->assertOk();
        $this->assertEquals(TaskStatusEnum::IN_REVIEW, $task->fresh()->status);
    }

    public function test_task_completes_directly_without_approval_requirement(): void
    {
        $task = Task::create([
            'title' => 'Sin aprobación',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'requires_manager_approval' => false,
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/submit-review");

        $response->assertOk();
        $this->assertEquals(TaskStatusEnum::COMPLETED, $task->fresh()->status);
    }

    public function test_manager_can_approve_task(): void
    {
        $task = Task::create([
            'title' => 'En revisión',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::IN_REVIEW,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/approve", [
                'note' => 'Buen trabajo',
            ]);

        $response->assertOk();
        $this->assertEquals(TaskStatusEnum::COMPLETED, $task->fresh()->status);
    }

    public function test_manager_can_reject_task(): void
    {
        $task = Task::create([
            'title' => 'Rechazada',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::IN_REVIEW,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/reject", [
                'note' => 'Necesita correcciones',
            ]);

        $response->assertOk();
        $this->assertEquals(TaskStatusEnum::REJECTED, $task->fresh()->status);
    }

    public function test_reject_requires_note(): void
    {
        $task = Task::create([
            'title' => 'Rechazada sin nota',
            'created_by' => $this->admin->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::IN_REVIEW,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/reject", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['note']);
    }

    public function test_superadmin_can_cancel_task(): void
    {
        $task = Task::create([
            'title' => 'Cancelar',
            'created_by' => $this->admin->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/cancel");

        $response->assertOk();
        $this->assertEquals(TaskStatusEnum::CANCELLED, $task->fresh()->status);
    }

    // ── Requirements Validation ──

    public function test_cannot_submit_review_without_required_attachment(): void
    {
        $task = Task::create([
            'title' => 'Requiere adjunto',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'requires_attachment' => true,
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/submit-review");

        $response->assertUnprocessable();
    }

    public function test_cannot_submit_review_without_required_completion_comment(): void
    {
        $task = Task::create([
            'title' => 'Requiere comentario',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'requires_completion_comment' => true,
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/submit-review");

        $response->assertUnprocessable();
    }

    // ── Comments ──

    public function test_user_can_add_comment_to_visible_task(): void
    {
        $task = Task::create([
            'title' => 'Comentar',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/comment", [
                'comment' => 'Progreso reportado',
                'type' => 'progress',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'comment' => 'Progreso reportado',
        ]);
    }

    // ── Attachments ──

    public function test_user_can_upload_attachment(): void
    {
        Storage::fake('local');

        $task = Task::create([
            'title' => 'Adjuntar',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/attachments", [
                'file' => $file,
                'attachment_type' => 'evidence',
            ]);

        $response->assertCreated()
            ->assertJsonPath('file_name', 'document.pdf');
    }

    // ── Listing & Viewing ──

    public function test_superadmin_sees_all_tasks(): void
    {
        Task::create(['title' => 'T1', 'created_by' => $this->admin->id, 'status' => TaskStatusEnum::PENDING]);
        Task::create(['title' => 'T2', 'created_by' => $this->admin->id, 'status' => TaskStatusEnum::IN_PROGRESS]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_worker_sees_only_own_tasks(): void
    {
        $otherWorker = User::factory()->create(['role_id' => $this->roles['worker']->id]);

        Task::create([
            'title' => 'Mi tarea',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        Task::create([
            'title' => 'Otra tarea',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $otherWorker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_task_show_returns_full_details(): void
    {
        $task = Task::create([
            'title' => 'Detalle',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonStructure(['data' => [
                'id', 'title', 'status', 'priority',
                'creator', 'current_responsible',
                'comments', 'attachments', 'status_history',
            ]]);
    }

    // ── Status History ──

    public function test_status_transitions_are_logged(): void
    {
        $task = Task::create([
            'title' => 'Historial',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $this->actingAs($this->worker, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/start");

        $this->assertDatabaseHas('task_status_history', [
            'task_id' => $task->id,
            'from_status' => TaskStatusEnum::PENDING->value,
            'to_status' => TaskStatusEnum::IN_PROGRESS->value,
        ]);
    }

    // ── Delegation History ──

    public function test_delegation_creates_history_record(): void
    {
        $task = Task::create([
            'title' => 'Delegar historial',
            'created_by' => $this->admin->id,
            'assigned_to_area_id' => $this->area->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::PENDING_ASSIGNMENT,
        ]);

        $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/delegate", [
                'to_user_id' => $this->worker->id,
                'note' => 'Delegación de prueba',
            ]);

        $this->assertDatabaseHas('task_delegations', [
            'task_id' => $task->id,
            'from_user_id' => $this->manager->id,
            'to_user_id' => $this->worker->id,
        ]);
    }

    // ── Delete ──

    public function test_superadmin_can_delete_task(): void
    {
        $task = Task::create([
            'title' => 'Tarea a eliminar',
            'created_by' => $this->admin->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonFragment(['message' => 'Tarea eliminada correctamente.']);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_worker_cannot_delete_task(): void
    {
        $task = Task::create([
            'title' => 'Tarea protegida',
            'created_by' => $this->admin->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $this->actingAs($this->worker)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_delete_cascades_related_records(): void
    {
        $task = Task::create([
            'title' => 'Tarea con comentario',
            'created_by' => $this->admin->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $this->admin->id,
            'comment' => 'Un comentario',
            'type' => 'comment',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/tasks/{$task->id}")
            ->assertOk();

        $this->assertDatabaseMissing('task_comments', ['task_id' => $task->id]);
    }
}
