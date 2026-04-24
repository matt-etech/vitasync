<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_carer_crud_creates_user_with_carer_role(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'carers.manage',
            'description' => 'Manage carers',
        ]));
        Role::create([
            'name' => 'Carer',
            'description' => 'Delivers visits',
        ]);

        $this->actingAs($admin)
            ->post(route('carers.store'), [
                'name' => 'Alex Carer',
                'email' => 'alex.carer@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'job_title' => 'Carer',
                'is_active' => '1',
            ])
            ->assertRedirect(route('carers.index', absolute: false));

        $carer = User::where('email', 'alex.carer@example.com')->firstOrFail();

        $this->assertTrue($carer->roles()->where('name', 'Carer')->exists());
    }
}
