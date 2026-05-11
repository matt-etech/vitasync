<?php

namespace Tests\Feature;

use App\Models\CarePlan;
use App\Models\Client;
use App\Models\Home;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarerTasksApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_carer_can_fetch_tasks_from_active_care_plans_for_their_home(): void
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
            'email' => 'carer-tasks@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'is_active' => true,
        ]);
        $carer->roles()->attach($carerRole);
        $client = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Asha',
            'last_name' => 'Patel',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);

        CarePlan::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'title' => 'Morning support plan',
            'plan_type' => 'Initial',
            'start_date' => '2026-05-01',
            'medication_support_level' => 'Administered by staff',
            'medication_support' => 'Follow MAR chart and escalate missed medication.',
            'personal_care_level' => 'Prompting',
            'personal_care_support' => 'Support washing and dressing routine.',
            'risk_level' => 'High',
            'risk_management' => 'Keep walking route clear.',
            'status' => 'active',
        ]);

        $response = $this->getJson(route('api.carer.tasks', [
            'carer_id' => $carer->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('tasks.0.client_name', 'Asha Patel')
            ->assertJsonPath('tasks.0.care_plan_title', 'Morning support plan')
            ->assertJsonFragment([
                'section' => 'Medication',
                'title' => 'Administered by staff',
                'instructions' => 'Follow MAR chart and escalate missed medication.',
                'status' => 'pending',
            ]);
    }
}
