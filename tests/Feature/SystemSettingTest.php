<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\MessageTemplate;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemSettingTest extends TestCase
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

        // Seed default settings
        SystemSetting::create([
            'key' => 'emails_enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'notifications',
            'description' => 'Activar correos',
        ]);

        SystemSetting::create([
            'key' => 'alert_days_before_due',
            'value' => '3',
            'type' => 'integer',
            'group' => 'notifications',
            'description' => 'Días antes',
        ]);

        SystemSetting::create([
            'key' => 'detect_overdue_time',
            'value' => '06:00',
            'type' => 'string',
            'group' => 'automation',
            'description' => 'Hora detección',
        ]);
    }

    // ── Index ──

    public function test_superadmin_can_list_settings(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/settings');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_superadmin_can_filter_settings_by_group(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/settings?group=notifications');

        $response->assertOk();

        // Should only contain notifications group
        $data = $response->json('data');
        $this->assertArrayHasKey('notifications', $data);
        $this->assertArrayNotHasKey('automation', $data);
    }

    public function test_worker_cannot_list_settings(): void
    {
        $response = $this->actingAs($this->worker)->getJson('/api/settings');

        $response->assertForbidden();
    }

    // ── Update ──

    public function test_superadmin_can_update_settings(): void
    {
        $response = $this->actingAs($this->admin)->putJson('/api/settings', [
            'settings' => [
                ['key' => 'emails_enabled', 'value' => '0'],
                ['key' => 'alert_days_before_due', 'value' => '5'],
            ],
        ]);

        $response->assertOk();

        $this->assertFalse(SystemSetting::getValue('emails_enabled'));
        $this->assertEquals(5, SystemSetting::getValue('alert_days_before_due'));
    }

    public function test_update_rejects_invalid_key(): void
    {
        $response = $this->actingAs($this->admin)->putJson('/api/settings', [
            'settings' => [
                ['key' => 'nonexistent_key', 'value' => 'foo'],
            ],
        ]);

        $response->assertUnprocessable();
    }

    public function test_worker_cannot_update_settings(): void
    {
        $response = $this->actingAs($this->worker)->putJson('/api/settings', [
            'settings' => [
                ['key' => 'emails_enabled', 'value' => '0'],
            ],
        ]);

        $response->assertForbidden();
    }

    // ── Model helpers ──

    public function test_get_value_returns_cast_value(): void
    {
        $this->assertTrue(SystemSetting::getValue('emails_enabled'));
        $this->assertIsBool(SystemSetting::getValue('emails_enabled'));
        $this->assertIsInt(SystemSetting::getValue('alert_days_before_due'));
        $this->assertEquals(3, SystemSetting::getValue('alert_days_before_due'));
    }

    public function test_get_value_returns_default_when_not_found(): void
    {
        $this->assertEquals('default_val', SystemSetting::getValue('missing_key', 'default_val'));
    }

    public function test_set_value_updates_setting(): void
    {
        SystemSetting::setValue('emails_enabled', false);
        $this->assertFalse(SystemSetting::getValue('emails_enabled'));

        SystemSetting::setValue('emails_enabled', true);
        $this->assertTrue(SystemSetting::getValue('emails_enabled'));
    }

    public function test_get_group_returns_all_in_group(): void
    {
        $group = SystemSetting::getGroup('notifications');

        $this->assertArrayHasKey('emails_enabled', $group);
        $this->assertArrayHasKey('alert_days_before_due', $group);
        $this->assertArrayNotHasKey('detect_overdue_time', $group);
    }
}
