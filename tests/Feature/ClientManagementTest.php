<?php

namespace Tests\Feature;

use App\Models\CarePlan;
use App\Models\Client;
use App\Models\ClientAssessment;
use App\Models\Home;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created_and_updated(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'clients.manage',
            'description' => 'Manage clients.',
        ]));
        $home = Home::create([
            'name' => 'Oak House',
            'address_line_1' => '1 Care Street',
            'city' => 'Bristol',
            'postcode' => 'BS1 1AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('clients.store'), [
                    'home_id' => $home->id,
                    'first_name' => 'Mary',
                    'last_name' => 'Jones',
                    'date_of_birth' => '1945-02-10',
                    'gender' => 'Female',
                    'phone' => '07123456789',
                    'email' => 'mary@example.com',
                    'address' => '10 Client Road',
                    'emergency_contact_name' => 'Alan Jones',
                    'emergency_contact_phone' => '07987654321',
                    'status' => 'active',
                ]);

        $client = Client::where('email', 'mary@example.com')->firstOrFail();

        $response->assertRedirect(route('clients.assessments.edit', $client, absolute: false));

        $this->assertSame($home->id, $client->home_id);
        $this->assertSame('Mary Jones', $client->fullName());

        $this->actingAs($admin)
            ->put(route('clients.update', $client), [
                'home_id' => $home->id,
                'first_name' => 'Mary',
                'last_name' => 'Jones',
                'date_of_birth' => '1945-02-10',
                'gender' => 'Female',
                'phone' => '07123456789',
                'email' => 'mary.updated@example.com',
                'address' => '10 Client Road',
                'emergency_contact_name' => 'Alan Jones',
                'emergency_contact_phone' => '07987654321',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('clients.index', absolute: false));

        $this->assertSame('inactive', $client->fresh()->status);
        $this->assertSame('mary.updated@example.com', $client->fresh()->email);
    }

    public function test_client_onboarding_assessment_can_be_submitted_declined_resubmitted_and_approved(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'reviewer@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'clients.manage',
            'description' => 'Manage clients.',
        ]));
        $home = Home::create([
            'name' => 'Oak House',
            'address_line_1' => '1 Care Street',
            'city' => 'Bristol',
            'postcode' => 'BS1 1AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $client = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Asha',
            'last_name' => 'Patel',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_ONBOARDING,
        ]);

        $payload = [
            'assessment' => [
                'assessment_date' => now()->toDateString(),
                'assessor_name' => 'Care Coordinator',
                'assessment_type' => 'initial',
                'overall_summary' => 'Initial evidence captured.',
                'overall_risk_level' => 'medium',
                'recommendations' => 'Create a support plan.',
            ],
            'needs' => [
                'physical_needs' => 'Support with morning routine.',
                'priority_needs' => 'Medication prompts.',
            ],
            'functional' => [
                'mobility_status' => 'Assistance',
                'bathing_ability' => 'Assistance',
            ],
            'medical' => [
                'diagnoses' => 'Diabetes',
                'medication_support_needed' => '1',
            ],
            'mental_capacity' => [
                'decision_type' => 'Care package agreement',
                'understands_information' => '1',
                'retains_information' => '1',
                'weighs_information' => '1',
                'communicates_decision' => '1',
                'imca_involved' => '0',
            ],
            'risk' => [
                'falls_risk' => 'Medium',
                'control_measures' => 'Falls sensor and clear walkways.',
            ],
            'communication' => [
                'preferred_language' => 'English',
                'hearing_impairment' => '0',
                'vision_impairment' => '0',
                'speech_difficulty' => '0',
                'interpreter_required' => '0',
            ],
            'equality' => [
                'religion' => 'Hindu',
                'reasonable_adjustments' => 'Respect prayer times.',
            ],
            'social' => [
                'living_arrangements' => 'Lives alone',
                'social_isolation_risk' => 'Medium',
            ],
            'environmental' => [
                'home_condition' => 'Safe',
                'fire_risk' => 'Low',
            ],
        ];

        $this->actingAs($admin)
            ->put(route('clients.assessments.update', $client), $payload)
            ->assertRedirect(route('clients.assessments.edit', $client, absolute: false));

        $assessment = ClientAssessment::where('client_id', $client->id)->firstOrFail();

        $this->assertSame(ClientAssessment::STATUS_ONBOARDING, $assessment->status);
        $this->assertSame('Support with morning routine.', $assessment->needs->physical_needs);
        $this->assertTrue($assessment->medical->medication_support_needed);

        $this->actingAs($admin)
            ->post(route('clients.assessments.submit', $client))
            ->assertRedirect(route('clients.assessments.edit', $client, absolute: false));

        $this->assertSame(Client::ONBOARDING_STATUS_PENDING, $client->fresh()->onboarding_status);

        $this->actingAs($admin)
            ->post(route('clients.assessments.decline', $client), [
                'review_notes' => 'Clarify medication support plan.',
            ])
            ->assertRedirect(route('clients.assessments.edit', $client, absolute: false));

        $declinedClient = $client->fresh();

        $this->assertSame(Client::ONBOARDING_STATUS_DECLINED, $declinedClient->onboarding_status);
        $this->assertSame('Clarify medication support plan.', $declinedClient->review_notes);

        $this->actingAs($admin)
            ->put(route('clients.assessments.update', $client), $payload)
            ->assertRedirect(route('clients.assessments.edit', $client, absolute: false));

        $this->assertSame(2, ClientAssessment::where('client_id', $client->id)->count());
        $this->assertSame(ClientAssessment::STATUS_DECLINED, ClientAssessment::where('client_id', $client->id)->where('version', 1)->firstOrFail()->status);
        $this->assertSame(ClientAssessment::STATUS_ONBOARDING, ClientAssessment::where('client_id', $client->id)->where('version', 2)->firstOrFail()->status);

        $this->actingAs($admin)
            ->get(route('clients.assessments.edit', $client))
            ->assertOk();

        $this->assertSame(2, ClientAssessment::where('client_id', $client->id)->count());

        $this->actingAs($admin)
            ->post(route('clients.assessments.submit', $client))
            ->assertRedirect(route('clients.assessments.edit', $client, absolute: false));

        $this->actingAs($admin)
            ->post(route('clients.assessments.approve', $client))
            ->assertRedirect(route('clients.index', absolute: false));

        $this->assertSame(Client::ONBOARDING_STATUS_APPROVED, $client->fresh()->onboarding_status);
        $this->assertSame(ClientAssessment::STATUS_APPROVED, ClientAssessment::where('client_id', $client->id)->where('version', 2)->firstOrFail()->status);

        $admin->permissions()->attach(Permission::create([
            'name' => 'care_plans.manage',
            'description' => 'Manage care plans.',
        ]));
        CarePlan::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'title' => 'Morning support plan',
            'plan_type' => 'Initial',
            'care_level' => 'Medium',
            'visit_frequency' => 'Daily',
            'review_frequency' => 'Monthly',
            'start_date' => now()->toDateString(),
            'review_date' => now()->addMonth()->toDateString(),
            'care_goals' => 'Keep morning routine safe and consistent.',
            'personal_care_level' => 'Prompting',
            'risk_level' => 'Medium',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Asha Patel')
            ->assertSee('Version 2')
            ->assertSee('Version 1')
            ->assertSee('Clarify medication support plan.')
            ->assertSee('Care Plans')
            ->assertSee('Morning support plan')
            ->assertSee('New care plan')
            ->assertSee('Edit Morning support plan');
    }

    public function test_open_onboarding_assessment_is_resumed_without_creating_another_version(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'resume@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'clients.manage',
            'description' => 'Manage clients.',
        ]));
        $home = Home::create([
            'name' => 'Rose House',
            'address_line_1' => '2 Care Street',
            'city' => 'Bristol',
            'postcode' => 'BS2 2AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $client = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Ruth',
            'last_name' => 'Green',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);

        $client->assessments()->create([
            'version' => 1,
            'assessment_type' => 'initial',
            'status' => ClientAssessment::STATUS_APPROVED,
            'assessment_date' => now()->subDay()->toDateString(),
        ]);
        $client->assessments()->create([
            'version' => 2,
            'assessment_type' => 'review',
            'status' => ClientAssessment::STATUS_ONBOARDING,
            'assessment_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('clients.assessments.edit', $client))
            ->assertOk()
            ->assertSee('Assessment progress');

        $this->assertSame(2, ClientAssessment::where('client_id', $client->id)->count());

        $this->actingAs($admin)
            ->get(route('clients.assessments.edit', $client))
            ->assertOk();

        $this->assertSame(2, ClientAssessment::where('client_id', $client->id)->count());
    }
}
