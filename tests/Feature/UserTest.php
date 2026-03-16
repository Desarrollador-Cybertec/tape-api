<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
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
        return User::factory()->create(array_merge([
            'role_id' => $this->roles[$role->value]->id,
            'password' => Hash::make('Password1'),
        ], $overrides));
    }

    public function test_superadmin_can_list_users(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);
        $this->createUser(RoleEnum::WORKER);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_worker_cannot_list_users(): void
    {
        $worker = $this->createUser(RoleEnum::WORKER);

        $response = $this->actingAs($worker, 'sanctum')
            ->getJson('/api/users');

        $response->assertForbidden();
    }

    public function test_superadmin_can_create_user(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'New Worker',
                'email' => 'newworker@test.com',
                'password' => 'Password1',
                'role_id' => $this->roles['worker']->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'New Worker');

        $this->assertDatabaseHas('users', ['email' => 'newworker@test.com']);
    }

    public function test_worker_cannot_create_user(): void
    {
        $worker = $this->createUser(RoleEnum::WORKER);

        $response = $this->actingAs($worker, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Another',
                'email' => 'another@test.com',
                'password' => 'Password1',
                'role_id' => $this->roles['worker']->id,
            ]);

        $response->assertForbidden();
    }

    public function test_superadmin_can_update_user_role(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);
        $worker = $this->createUser(RoleEnum::WORKER);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$worker->id}/role", [
                'role_id' => $this->roles['manager']->id,
            ]);

        $response->assertOk();
        $this->assertEquals($this->roles['manager']->id, $worker->fresh()->role_id);
    }

    public function test_superadmin_cannot_change_own_role(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$admin->id}/role", [
                'role_id' => $this->roles['worker']->id,
            ]);

        $response->assertForbidden();
    }

    public function test_superadmin_can_toggle_user_active_status(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);
        $worker = $this->createUser(RoleEnum::WORKER);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$worker->id}/toggle-active");

        $response->assertOk();
        $this->assertFalse($worker->fresh()->active);
    }

    public function test_create_user_validates_password_strength(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Weak Pass',
                'email' => 'weak@test.com',
                'password' => '123',
                'role_id' => $this->roles['worker']->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_create_user_validates_unique_email(): void
    {
        $admin = $this->createUser(RoleEnum::SUPERADMIN, ['email' => 'taken@test.com']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Duplicate',
                'email' => 'taken@test.com',
                'password' => 'Password1',
                'role_id' => $this->roles['worker']->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
