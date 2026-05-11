<?php

namespace Tests\Feature;

use App\Models\Home;
use App\Models\LoginHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarerApiLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_carer_can_login_through_api(): void
    {
        $home = Home::create([
            'name' => 'Green View',
            'address_line_1' => '1 Care Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'status' => 'active',
        ]);
        $carerRole = Role::create([
            'name' => 'Carer',
            'description' => 'Delivers scheduled care visits.',
        ]);
        $carer = User::create([
            'name' => 'Alex Carer',
            'email' => 'alex.carer@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'job_title' => 'Carer',
            'is_active' => true,
        ]);
        $carer->roles()->attach($carerRole);
        $carer->carerProfile()->create([
            'status' => 'approved',
            'account_status' => 'active',
        ]);

        $this->postJson(route('api.carer.login'), [
            'email' => 'alex.carer@example.com',
            'password' => 'password',
            'device_timezone' => 'Africa/Windhoek',
            'device_datetime' => '2026-05-04T12:00:00.000',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Carer login verified.')
            ->assertJsonPath('carer.email', 'alex.carer@example.com')
            ->assertJsonPath('carer.home.name', 'Green View');

        $this->assertSame(1, LoginHistory::where('user_id', $carer->id)->count());
    }

    public function test_non_carer_cannot_use_carer_api_login(): void
    {
        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $manager->roles()->attach(Role::create([
            'name' => 'Home Manager',
            'description' => 'Manages a care home.',
        ]));

        $this->postJson(route('api.carer.login'), [
            'email' => 'manager@example.com',
            'password' => 'password',
        ])->assertForbidden();

        $this->assertSame(0, LoginHistory::where('user_id', $manager->id)->count());
    }
}
