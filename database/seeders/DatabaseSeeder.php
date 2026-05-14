<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = collect([
            ['name' => 'carers.manage', 'description' => 'Create, update, and manage carer accounts.'],
            ['name' => 'care_plans.manage', 'description' => 'Create, update, and manage client care plans.'],
            ['name' => 'clients.manage', 'description' => 'Create, update, and remove client records.'],
            ['name' => 'family_members.manage', 'description' => 'Create and manage permissioned family access.'],
            ['name' => 'governance.manage', 'description' => 'Manage complaints, GDPR cases, and governance actions.'],
            ['name' => 'homes.manage', 'description' => 'Create, update, and remove care homes.'],
            ['name' => 'home_users.manage', 'description' => 'Create, update, and remove users assigned to a home.'],
            ['name' => 'users.impersonate', 'description' => 'Impersonate active home users for support and verification.'],
            ['name' => 'users.manage', 'description' => 'Create, update, and remove user accounts.'],
            ['name' => 'roles.manage', 'description' => 'Create, update, and remove roles.'],
            ['name' => 'permissions.manage', 'description' => 'Create, update, and remove permissions.'],
            ['name' => 'audit_logs.view', 'description' => 'View the system audit trail.'],
        ])->map(fn (array $permission): Permission => Permission::firstOrCreate(
            ['name' => $permission['name']],
            ['description' => $permission['description']],
        ));

        $administrator = Role::firstOrCreate([
            'name' => 'Administrator',
        ], [
            'description' => 'Full access to identity and access control.',
        ]);
        $administrator->permissions()->sync($permissions->pluck('id')->all());

        $homeManager = Role::firstOrCreate([
            'name' => 'Home Manager',
        ], [
            'description' => 'Can manage an assigned home and its users.',
        ]);
        $homeManager->permissions()->sync(
            $permissions
                ->whereIn('name', ['homes.manage', 'home_users.manage', 'family_members.manage', 'governance.manage'])
                ->pluck('id')
                ->all()
        );

        $carer = Role::firstOrCreate([
            'name' => 'Carer',
        ], [
            'description' => 'Delivers scheduled care visits and records EVV activity.',
        ]);

        $user = User::firstOrCreate([
            'email' => 'admin@vitasync.local',
        ], [
            'name' => 'System Administrator',
            'password' => Hash::make('password'),
        ]);
        $user->roles()->syncWithoutDetaching([$administrator->id]);

        $defaultCarer = User::firstOrCreate([
            'email' => 'carer@vitasync.local',
        ], [
            'name' => 'Default Carer',
            'password' => Hash::make('password'),
            'job_title' => 'Carer',
            'is_active' => true,
        ]);
        $defaultCarer->roles()->syncWithoutDetaching([$carer->id]);
    }
}
