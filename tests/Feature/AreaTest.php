<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Area;
use App\Models\AreaMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AreaTest extends TestCase
{
    use RefreshDatabase;

    private array $roles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->roles['superadmin'] = Role::create(['name' => 'Super Administrador', 'slug' => RoleEnum::SUPERADMIN->value]);
        $this->roles['manager'] = Role::create(['name' => 'Encargado de Área', 'slug' => RoleEnum::AREA_MANAGER->value]);
        $this->roles['worker'] = Role::create(['name' => 'Trabajador', 'slug' => RoleEnum::WORKER->value]);
    }

    private function createUser(RoleEnum $role, array $overrides = []): User
    {
        $roleKey = match($role) {
            RoleEnum::SUPERADMIN => 'superadmin',
            RoleEnum::AREA_MANAGER => 'manager',
            RoleEnum::WORKER => 'worker',
        };

        return User::factory()->create(array_merge([
            'role_id' => $this->roles[$roleKey]->id,
            'password' => Hash::make('Password1'),
        ], $overrides));
    }

    public function test_superadmin_can_create_area(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/areas', [
                'name' => 'Área de Test',
                'description' => 'Descripción del área',
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Área de Test');
    }

    public function test_worker_cannot_create_area(): void
    {
        $worker = $this->createUser(RoleEnum::WORKER);

        $response = $this->actingAs($worker, 'sanctum')
            ->postJson('/api/areas', [
                'name' => 'Área Ilegal',
            ]);

        $response->assertForbidden();
    }

    public function test_superadmin_can_assign_manager(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);
        $manager = $this->createUser(RoleEnum::AREA_MANAGER);
        $area = Area::create(['name' => 'Área']);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/areas/{$area->id}/manager", [
                'manager_user_id' => $manager->id,
            ]);

        $response->assertOk();
        $this->assertEquals($manager->id, $area->fresh()->manager_user_id);
    }

    public function test_manager_can_claim_worker(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);
        $manager = $this->createUser(RoleEnum::AREA_MANAGER);
        $worker = $this->createUser(RoleEnum::WORKER);
        $area = Area::create(['name' => 'Área', 'manager_user_id' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/areas/claim-worker', [
                'user_id' => $worker->id,
                'area_id' => $area->id,
            ]);

        $response->assertCreated();
        $this->assertTrue($worker->belongsToArea($area->id));
    }

    public function test_cannot_claim_worker_already_in_area(): void
    {
        $manager = $this->createUser(RoleEnum::AREA_MANAGER);
        $worker = $this->createUser(RoleEnum::WORKER);
        $area = Area::create(['name' => 'Área', 'manager_user_id' => $manager->id]);

        AreaMember::create([
            'area_id' => $area->id,
            'user_id' => $worker->id,
            'assigned_by' => $manager->id,
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/areas/claim-worker', [
                'user_id' => $worker->id,
                'area_id' => $area->id,
            ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_claim_non_worker_user(): void
    {
        $manager = $this->createUser(RoleEnum::AREA_MANAGER);
        $anotherManager = $this->createUser(RoleEnum::AREA_MANAGER);
        $area = Area::create(['name' => 'Área', 'manager_user_id' => $manager->id]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/areas/claim-worker', [
                'user_id' => $anotherManager->id,
                'area_id' => $area->id,
            ]);

        $response->assertUnprocessable();
    }

    public function test_superadmin_can_list_all_areas(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);
        Area::create(['name' => 'Área 1']);
        Area::create(['name' => 'Área 2']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/areas');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_superadmin_can_update_area(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);
        $area = Area::create(['name' => 'Old Name']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/areas/{$area->id}", [
                'name' => 'New Name',
            ]);

        $response->assertOk();
        $this->assertEquals('New Name', $area->fresh()->name);
    }
}
