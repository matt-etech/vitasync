<?php

namespace Tests\Feature;

use App\Models\CarePlan;
use App\Models\Client;
use App\Models\Home;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarePlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_care_plan_can_be_created_updated_and_disabled(): void
    {
        [$admin, $client] = $this->createCarePlanUserAndClient();

        $this->actingAs($admin)
            ->get(route('care-plans.index'))
            ->assertOk()
            ->assertSee('Care Plans')
            ->assertSee('New care plan');

        $this->actingAs($admin)
            ->post(route('care-plans.store'), $this->validPayload($client))
            ->assertRedirect(route('care-plans.index', absolute: false));

        $carePlan = CarePlan::where('client_id', $client->id)->firstOrFail();

        $this->assertSame($client->home_id, $carePlan->home_id);
        $this->assertSame('High', $carePlan->care_level);
        $this->assertSame('Two person assist', $carePlan->mobility_level);
        $this->assertSame('Administered by staff', $carePlan->medication_support_level);
        $this->assertSame('High', $carePlan->risk_level);

        $this->actingAs($admin)
            ->put(route('care-plans.update', $carePlan), array_merge($this->validPayload($client), [
                'title' => 'Morning and evening support plan',
                'status' => 'active',
                'review_date' => '2026-08-01',
                'risk_level' => 'Medium',
            ]))
            ->assertRedirect(route('care-plans.index', absolute: false));

        $carePlan->refresh();

        $this->assertSame('Morning and evening support plan', $carePlan->title);
        $this->assertSame('active', $carePlan->status);
        $this->assertSame('Medium', $carePlan->risk_level);

        $this->actingAs($admin)
            ->delete(route('care-plans.destroy', $carePlan))
            ->assertRedirect(route('care-plans.index', absolute: false));

        $this->assertSame('inactive', $carePlan->fresh()->status);

        $this->actingAs($admin)
            ->delete(route('care-plans.destroy', $carePlan))
            ->assertRedirect(route('care-plans.index', absolute: false));

        $this->assertSame('active', $carePlan->fresh()->status);
    }

    public function test_care_plan_dropdown_fields_reject_unapproved_values(): void
    {
        [$admin, $client] = $this->createCarePlanUserAndClient();

        $this->actingAs($admin)
            ->from(route('care-plans.index'))
            ->post(route('care-plans.store'), array_merge($this->validPayload($client), [
                'care_level' => 'Anything',
                'mobility_level' => 'Unknown support',
                'risk_level' => 'Extreme',
            ]))
            ->assertRedirect(route('care-plans.index', absolute: false))
            ->assertSessionHasErrors(['care_level', 'mobility_level', 'risk_level']);

        $this->assertSame(0, CarePlan::count());
    }

    /**
     * @return array{0: User, 1: Client}
     */
    private function createCarePlanUserAndClient(): array
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'care-plans@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->permissions()->attach(Permission::create([
            'name' => 'care_plans.manage',
            'description' => 'Manage care plans.',
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
            'first_name' => 'Mary',
            'last_name' => 'Jones',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);

        return [$admin, $client];
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(Client $client): array
    {
        return [
            'client_id' => (string) $client->id,
            'title' => 'Personal care and safety plan',
            'plan_type' => 'Initial',
            'care_level' => 'High',
            'visit_frequency' => 'Multiple daily',
            'review_frequency' => 'Monthly',
            'start_date' => '2026-04-15',
            'review_date' => '2026-05-15',
            'care_goals' => 'Maintain safe daily routines and independence where possible.',
            'personal_care_level' => 'Full support',
            'personal_care_support' => 'Support with washing, dressing, and continence care.',
            'mobility_level' => 'Two person assist',
            'mobility_support' => 'Use transfer aid and follow moving and handling guidance.',
            'nutrition_support_level' => 'Meal preparation',
            'nutrition_hydration_support' => 'Prepare meals and monitor fluid intake.',
            'medication_support_level' => 'Administered by staff',
            'medication_support' => 'Follow MAR chart and escalate missed medication immediately.',
            'communication_support_level' => 'Verbal',
            'communication_support' => 'Use short clear prompts and confirm understanding.',
            'risk_level' => 'High',
            'risk_management' => 'Falls sensor active overnight; keep walkway clear.',
            'preferences_routines' => 'Prefers morning care after breakfast.',
            'escalation_instructions' => 'Call manager and emergency contact if condition changes.',
            'review_notes' => 'Review after first month of care delivery.',
            'status' => 'draft',
        ];
    }
}
