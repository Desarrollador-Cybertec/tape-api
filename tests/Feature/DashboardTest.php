<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Enums\TaskStatusEnum;
use App\Models\Area;
use App\Models\AreaMember;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_superadmin_can_access_general_dashboard(): void
    {
        Task::create([
            'title' => 'T1',
            'created_by' => $this->admin->id,
            'area_id' => $this->area->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        Task::create([
            'title' => 'T2',
            'created_by' => $this->admin->id,
            'status' => TaskStatusEnum::COMPLETED,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/general');

        $response->assertOk()
            ->assertJsonStructure([
                'tasks_by_status',
                'overdue_tasks',
                'due_soon',
                'total_active',
                'total_completed',
                'total_all',
                'completion_rate',
                'top_responsibles',
            ]);

        $this->assertEquals(2, $response->json('total_all'));
        $this->assertEquals(1, $response->json('total_completed'));
    }

    public function test_worker_cannot_access_general_dashboard(): void
    {
        $response = $this->actingAs($this->worker, 'sanctum')
            ->getJson('/api/dashboard/general');

        $response->assertForbidden();
    }

    public function test_superadmin_can_access_area_dashboard(): void
    {
        Task::create([
            'title' => 'Tarea de área',
            'created_by' => $this->admin->id,
            'area_id' => $this->area->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/dashboard/area/{$this->area->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'area',
                'tasks_by_status',
                'overdue_tasks',
                'by_responsible',
                'total_tasks',
                'completion_rate',
                'without_progress',
            ]);

        $this->assertEquals(1, $response->json('total_tasks'));
    }

    public function test_manager_can_access_own_area_dashboard(): void
    {
        Task::create([
            'title' => 'Tarea del manager',
            'created_by' => $this->admin->id,
            'area_id' => $this->area->id,
            'status' => TaskStatusEnum::PENDING,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson("/api/dashboard/area/{$this->area->id}");

        $response->assertOk();
    }

    public function test_worker_cannot_access_area_dashboard(): void
    {
        $response = $this->actingAs($this->worker, 'sanctum')
            ->getJson("/api/dashboard/area/{$this->area->id}");

        $response->assertForbidden();
    }

    public function test_user_can_access_own_dashboard(): void
    {
        Task::create([
            'title' => 'Mi tarea',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'due_date' => now()->addDay(),
        ]);

        Task::create([
            'title' => 'Vencida',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'due_date' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->worker, 'sanctum')
            ->getJson('/api/dashboard/me');

        $response->assertOk()
            ->assertJsonStructure([
                'active_tasks',
                'overdue_tasks',
                'due_soon',
                'completed_tasks',
                'tasks_by_status',
                'upcoming_tasks',
            ]);

        $this->assertEquals(2, $response->json('active_tasks'));
        $this->assertEquals(1, $response->json('overdue_tasks'));
    }

    public function test_general_dashboard_shows_overdue_count(): void
    {
        Task::create([
            'title' => 'Vencida',
            'created_by' => $this->admin->id,
            'current_responsible_user_id' => $this->worker->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'due_date' => now()->subDays(5),
        ]);

        Task::create([
            'title' => 'A tiempo',
            'created_by' => $this->admin->id,
            'status' => TaskStatusEnum::IN_PROGRESS,
            'due_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/general');

        $response->assertOk();
        $this->assertEquals(1, $response->json('overdue_tasks'));
    }
}
