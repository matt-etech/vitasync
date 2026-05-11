<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Home;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarerVisitsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_carer_can_fetch_their_calendar_visits(): void
    {
        $carerRole = Role::create([
            'name' => 'Carer',
            'description' => 'Delivers care visits.',
            'is_active' => true,
        ]);
        $home = Home::create([
            'name' => 'Oak House',
            'address_line_1' => '1 Care Street',
            'city' => 'Bristol',
            'postcode' => 'BS1 1AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $carer = User::create([
            'name' => 'Default Carer',
            'email' => 'schedule-carer@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'is_active' => true,
        ]);
        $otherCarer = User::create([
            'name' => 'Other Carer',
            'email' => 'other-schedule-carer@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'is_active' => true,
        ]);
        $carer->roles()->attach($carerRole);
        $otherCarer->roles()->attach($carerRole);

        $client = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Asha',
            'last_name' => 'Patel',
            'address' => '10 Client Road',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);

        Visit::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'assigned_user_id' => $carer->id,
            'title' => 'Morning visit',
            'scheduled_start_at' => now()->setTime(9, 30),
            'scheduled_end_at' => now()->setTime(10, 15),
            'status' => 'scheduled',
        ]);
        Visit::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'assigned_user_id' => $otherCarer->id,
            'title' => 'Other visit',
            'scheduled_start_at' => now()->setTime(11, 30),
            'scheduled_end_at' => now()->setTime(12, 15),
            'status' => 'scheduled',
        ]);

        $this->getJson(route('api.carer.visits', ['carer_id' => $carer->id]))
            ->assertOk()
            ->assertJsonCount(1, 'visits')
            ->assertJsonPath('visits.0.client_name', 'Asha Patel')
            ->assertJsonPath('visits.0.assigned_worker_name', 'Default Carer')
            ->assertJsonPath('visits.0.time_window', '09:30 - 10:15')
            ->assertJsonPath('visits.0.status', 'scheduled');
    }
}
