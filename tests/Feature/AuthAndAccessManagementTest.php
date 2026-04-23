<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_root_redirects_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_user_can_login_and_see_access_menu(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->permissions()->attach([
            Permission::create(['name' => 'users.manage', 'description' => 'Manage users'])->id,
            Permission::create(['name' => 'roles.manage', 'description' => 'Manage roles'])->id,
            Permission::create(['name' => 'permissions.manage', 'description' => 'Manage permissions'])->id,
        ]);

        $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('User Management')
            ->assertSee('Users')
            ->assertSee('Roles')
            ->assertSee('Permissions')
            ->assertDontSee('Clients');
    }

    public function test_role_crud_links_permissions(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->permissions()->attach(Permission::create([
            'name' => 'roles.manage',
            'description' => 'Manage roles',
        ]));
        $permission = Permission::create([
            'name' => 'users.manage',
            'description' => 'Manage users',
        ]);

        $this->actingAs($user)
            ->post(route('roles.store'), [
                'name' => 'Manager',
                'description' => 'Manages identity records',
                'permissions' => [$permission->id],
            ])
            ->assertRedirect(route('roles.index', absolute: false));

        $role = Role::where('name', 'Manager')->firstOrFail();

        $this->assertTrue($role->permissions()->whereKey($permission->id)->exists());
    }

    public function test_user_crud_links_roles(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'users.manage',
            'description' => 'Manage users',
        ]));
        $role = Role::create([
            'name' => 'Coordinator',
            'description' => 'Coordinates care operations',
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Care Lead',
                'email' => 'care.lead@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'roles' => [$role->id],
            ])
            ->assertRedirect(route('users.index', absolute: false));

        $createdUser = User::where('email', 'care.lead@example.com')->firstOrFail();

        $this->assertTrue($createdUser->roles()->whereKey($role->id)->exists());
    }
}
