<?php

namespace Tests\Feature;

use App\Models\Home;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_can_be_created_with_logo_and_manager(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();
        $manager = User::create([
            'name' => 'Home Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
        ]);
        $managerRole = Role::create([
            'name' => 'Home Manager',
            'description' => 'Can manage an assigned home and its users.',
        ]);

        $this->actingAs($admin)
            ->post(route('homes.store'), [
                'name' => 'Oak House',
                'registration_number' => 'REG-001',
                'care_type' => 'Residential care',
                'capacity' => 32,
                'phone' => '01234567890',
                'email' => 'oak@example.com',
                'website' => 'https://oak.example.com',
                'address_line_1' => '1 Care Street',
                'city' => 'Bristol',
                'postcode' => 'BS1 1AA',
                'country' => 'United Kingdom',
                'status' => 'active',
                'manager_id' => $manager->id,
                'logo' => UploadedFile::fake()->image('oak-logo.png', 120, 120),
            ])
            ->assertRedirect(route('homes.index', absolute: false));

        $home = Home::where('name', 'Oak House')->firstOrFail();

        $this->assertSame($manager->id, $home->manager_id);
        $this->assertSame($home->id, $manager->fresh()->home_id);
        $this->assertTrue($manager->roles()->whereKey($managerRole->id)->exists());
        $this->assertNotNull($home->logo_path);
        Storage::disk('public')->assertExists($home->logo_path);
    }

    public function test_home_user_can_be_created_with_roles_and_permissions(): void
    {
        $admin = $this->adminUser();
        $home = Home::create([
            'name' => 'Pine Lodge',
            'address_line_1' => '2 Care Street',
            'city' => 'Cardiff',
            'postcode' => 'CF1 1AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $role = Role::create([
            'name' => 'Home Manager',
            'description' => 'Can manage an assigned home and its users.',
        ]);
        $permission = Permission::firstOrCreate([
            'name' => 'home_users.manage',
        ], [
            'description' => 'Manage users assigned to a home.',
        ]);

        $this->actingAs($admin)
            ->post(route('homes.users.store', $home), [
                'name' => 'Pine Manager',
                'email' => 'pine.manager@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'job_title' => 'Registered Manager',
                'phone' => '07123456789',
                'is_active' => '1',
                'roles' => [$role->id],
                'permissions' => [$permission->id],
            ])
            ->assertRedirect(route('homes.users.index', $home, absolute: false));

        $createdUser = User::where('email', 'pine.manager@example.com')->firstOrFail();

        $this->assertSame($home->id, $createdUser->home_id);
        $this->assertSame($createdUser->id, $home->fresh()->manager_id);
        $this->assertTrue($createdUser->roles()->whereKey($role->id)->exists());
        $this->assertTrue($createdUser->permissions()->whereKey($permission->id)->exists());
    }

    public function test_administrator_can_impersonate_and_return_from_active_home_user(): void
    {
        $admin = $this->adminUser();
        $home = Home::create([
            'name' => 'Pine Lodge',
            'address_line_1' => '2 Care Street',
            'city' => 'Cardiff',
            'postcode' => 'CF1 1AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $homeUser = User::create([
            'home_id' => $home->id,
            'name' => 'Pine Worker',
            'email' => 'pine.worker@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('homes.users.impersonate', [$home, $homeUser]))
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('impersonator_id', $admin->id);

        $this->assertAuthenticatedAs($homeUser);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Impersonating Pine Worker')
            ->assertSee('Return to admin');

        $this->delete(route('impersonation.destroy'))
            ->assertRedirect(route('homes.users.index', $home, absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has('impersonator_id'));
    }

    public function test_inactive_home_user_cannot_be_impersonated(): void
    {
        $admin = $this->adminUser();
        $home = Home::create([
            'name' => 'Pine Lodge',
            'address_line_1' => '2 Care Street',
            'city' => 'Cardiff',
            'postcode' => 'CF1 1AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $homeUser = User::create([
            'home_id' => $home->id,
            'name' => 'Inactive Worker',
            'email' => 'inactive.worker@example.com',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('homes.users.impersonate', [$home, $homeUser]))
            ->assertForbidden();

        $this->assertAuthenticatedAs($admin);
    }

    private function adminUser(): User
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $user->permissions()->attach([
            Permission::firstOrCreate([
                'name' => 'homes.manage',
            ], [
                'description' => 'Manage homes.',
            ])->id,
            Permission::firstOrCreate([
                'name' => 'home_users.manage',
            ], [
                'description' => 'Manage home users.',
            ])->id,
            Permission::firstOrCreate([
                'name' => 'users.impersonate',
            ], [
                'description' => 'Impersonate home users.',
            ])->id,
        ]);

        return $user;
    }
}
