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
            ['name' => 'care_plans.manage', 'description' => 'Create, update, and manage client care plans.'],
            ['name' => 'clients.manage', 'description' => 'Create, update, and remove client records.'],
            ['name' => 'homes.manage', 'description' => 'Create, update, and remove care homes.'],
            ['name' => 'home_users.manage', 'description' => 'Create, update, and remove users assigned to a home.'],
            ['name' => 'users.impersonate', 'description' => 'Impersonate active home users for support and verification.'],
            ['name' => 'users.manage', 'description' => 'Create, update, and remove user accounts.'],
            ['name' => 'roles.manage', 'description' => 'Create, update, and remove roles.'],
            ['name' => 'permissions.manage', 'description' => 'Create, update, and remove permissions.'],
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
                ->whereIn('name', ['homes.manage', 'home_users.manage'])
                ->pluck('id')
                ->all()
        );

        $user = User::firstOrCreate([
            'email' => 'admin@vitasync.local',
        ], [
            'name' => 'System Administrator',
            'password' => Hash::make('password'),
        ]);
        $user->roles()->syncWithoutDetaching([$administrator->id]);
    }
}
