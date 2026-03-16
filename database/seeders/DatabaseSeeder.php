<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Area;
use App\Models\AreaMember;
use App\Models\Meeting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create roles
        $superadminRole = Role::create(['name' => 'Super Administrador', 'slug' => RoleEnum::SUPERADMIN->value]);
        $managerRole = Role::create(['name' => 'Encargado de Área', 'slug' => RoleEnum::AREA_MANAGER->value]);
        $workerRole = Role::create(['name' => 'Trabajador', 'slug' => RoleEnum::WORKER->value]);

        // Create superadmin
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@tape.test',
            'password' => Hash::make('Password1'),
            'role_id' => $superadminRole->id,
        ]);

        // Create area manager
        $manager = User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@tape.test',
            'password' => Hash::make('Password1'),
            'role_id' => $managerRole->id,
        ]);

        // Create workers
        $worker1 = User::factory()->create([
            'name' => 'Worker 1',
            'email' => 'worker1@tape.test',
            'password' => Hash::make('Password1'),
            'role_id' => $workerRole->id,
        ]);

        $worker2 = User::factory()->create([
            'name' => 'Worker 2',
            'email' => 'worker2@tape.test',
            'password' => Hash::make('Password1'),
            'role_id' => $workerRole->id,
        ]);

        // Create area
        $area = Area::create([
            'name' => 'Área de Desarrollo',
            'description' => 'Equipo de desarrollo de software',
            'manager_user_id' => $manager->id,
        ]);

        // Assign workers to area
        AreaMember::create([
            'area_id' => $area->id,
            'user_id' => $worker1->id,
            'assigned_by' => $admin->id,
            'joined_at' => now(),
            'is_active' => true,
        ]);

        AreaMember::create([
            'area_id' => $area->id,
            'user_id' => $worker2->id,
            'assigned_by' => $admin->id,
            'joined_at' => now(),
            'is_active' => true,
        ]);

        // Create sample meeting
        Meeting::create([
            'title' => 'Reunión de kickoff',
            'meeting_date' => now(),
            'area_id' => $area->id,
            'classification' => 'operational',
            'notes' => 'Primera reunión del equipo',
            'created_by' => $admin->id,
        ]);
    }
}
