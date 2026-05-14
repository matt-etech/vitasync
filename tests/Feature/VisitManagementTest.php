<?php

namespace Tests\Feature;

use App\Models\CarePlan;
use App\Models\CarerProfile;
use App\Models\CarerTrainingRecord;
use App\Models\Client;
use App\Models\Home;
use App\Models\Permission;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VisitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_can_be_booked_updated_and_cancelled(): void
    {
        [$admin, $client, $carePlan, $worker] = $this->createVisitFixtures();

        $this->actingAs($admin)
            ->get(route('visits.index'))
            ->assertOk()
            ->assertSee('Visits')
            ->assertSee('Book visit');

        $this->actingAs($admin)
            ->post(route('visits.store'), $this->validPayload($client, $carePlan, $worker))
            ->assertRedirect(route('visits.index', absolute: false));

        $visit = Visit::where('client_id', $client->id)->firstOrFail();

        $this->assertSame($client->home_id, $visit->home_id);
        $this->assertSame($carePlan->id, $visit->care_plan_id);
        $this->assertSame($worker->id, $visit->assigned_user_id);
        $this->assertSame('scheduled', $visit->status);

        $this->actingAs($admin)
            ->put(route('visits.update', $visit), array_merge($this->validPayload($client, $carePlan, $worker), [
                'title' => 'Updated morning visit',
                'status' => 'in_progress',
                'notes' => 'Updated rota details.',
            ]))
            ->assertRedirect(route('visits.index', absolute: false));

        $visit->refresh();

        $this->assertSame('Updated morning visit', $visit->title);
        $this->assertSame('in_progress', $visit->status);
        $this->assertSame('Updated rota details.', $visit->notes);

        $this->actingAs($admin)
            ->delete(route('visits.destroy', $visit))
            ->assertRedirect(route('visits.index', absolute: false));

        $this->assertSame('cancelled', $visit->fresh()->status);
    }

    public function test_visit_requires_care_plan_to_match_client(): void
    {
        [$admin, $client, , $worker] = $this->createVisitFixtures();

        $otherHome = Home::create([
            'name' => 'Pine House',
            'address_line_1' => '2 Care Street',
            'city' => 'Bristol',
            'postcode' => 'BS2 2AA',
            'country' => 'United Kingdom',
            'status' => 'active',
        ]);
        $otherClient = Client::create([
            'home_id' => $otherHome->id,
            'first_name' => 'Other',
            'last_name' => 'Client',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);
        $otherCarePlan = CarePlan::create([
            'home_id' => $otherHome->id,
            'client_id' => $otherClient->id,
            'title' => 'Other plan',
            'plan_type' => 'Initial',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->from(route('visits.index'))
            ->post(route('visits.store'), array_merge($this->validPayload($client, null, $worker), [
                'care_plan_id' => $otherCarePlan->id,
            ]))
            ->assertRedirect(route('visits.index', absolute: false))
            ->assertSessionHasErrors(['care_plan_id']);

        $this->assertSame(0, Visit::count());
    }

    public function test_visit_blocks_non_compliant_worker_assignment(): void
    {
        [$admin, $client, $carePlan, $worker] = $this->createVisitFixtures();

        $worker->carerProfile->update([
            'dbs_check_status' => 'expired',
            'dbs_expiry_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->from(route('visits.index'))
            ->post(route('visits.store'), $this->validPayload($client, $carePlan, $worker))
            ->assertRedirect(route('visits.index', absolute: false))
            ->assertSessionHasErrors(['assigned_user_id']);

        $this->assertSame(0, Visit::count());
    }

    public function test_visit_blocks_unavailable_worker_assignment(): void
    {
        [$admin, $client, $carePlan, $worker] = $this->createVisitFixtures();

        Visit::create(array_merge($this->validPayload($client, $carePlan, $worker), [
            'home_id' => $client->home_id,
        ]));

        $this->actingAs($admin)
            ->from(route('visits.index'))
            ->post(route('visits.store'), array_merge($this->validPayload($client, $carePlan, $worker), [
                'scheduled_start_at' => '2026-04-24 08:30:00',
                'scheduled_end_at' => '2026-04-24 09:30:00',
            ]))
            ->assertRedirect(route('visits.index', absolute: false))
            ->assertSessionHasErrors(['assigned_user_id']);

        $this->assertSame(1, Visit::count());
    }

    /**
     * @return array{0: User, 1: Client, 2: CarePlan, 3: User}
     */
    private function createVisitFixtures(): array
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'visits@example.com',
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
            'first_name' => 'Mary',
            'last_name' => 'Jones',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);

        $carePlan = CarePlan::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'title' => 'Morning support plan',
            'plan_type' => 'Initial',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $worker = User::create([
            'name' => 'Care Worker',
            'email' => 'worker@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'is_active' => true,
        ]);
        $this->createCompliantProfile($worker, $home);

        return [$admin, $client, $carePlan, $worker];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Client $client, ?CarePlan $carePlan, User $worker): array
    {
        return [
            'client_id' => $client->id,
            'care_plan_id' => $carePlan?->id,
            'assigned_user_id' => $worker->id,
            'title' => 'Morning medication and wellbeing visit',
            'scheduled_start_at' => '2026-04-24 08:00:00',
            'scheduled_end_at' => '2026-04-24 09:00:00',
            'status' => 'scheduled',
            'notes' => 'Complete breakfast prompts and wellbeing check.',
        ];
    }

    private function createCompliantProfile(User $worker, Home $home): void
    {
        $profile = CarerProfile::create([
            'user_id' => $worker->id,
            'status' => CarerProfile::STATUS_APPROVED,
            'legal_name' => $worker->name,
            'assigned_home_id' => $home->id,
            'dbs_check_status' => 'verified',
            'dbs_certificate_number' => 'DBS-123',
            'dbs_expiry_date' => now()->addYear()->toDateString(),
            'safeguarding_training_completed' => 'yes',
            'last_safeguarding_training_date' => now()->subMonth()->toDateString(),
            'occupational_health_clearance' => 'fit',
            'fit_to_work_declaration' => true,
            'availability_pattern' => 'full_time',
            'max_weekly_hours' => 40,
            'shift_preference' => 'both',
            'account_status' => 'active',
            'data_processing_consent' => true,
            'data_processing_consented_at' => now(),
            'privacy_policy_accepted' => true,
            'privacy_policy_accepted_at' => now(),
            'data_retention_category' => 'active_staff',
            'reviewed_at' => now(),
        ]);

        foreach (CarerTrainingRecord::MANDATORY_TRAINING as $trainingKey => $trainingName) {
            CarerTrainingRecord::create([
                'carer_profile_id' => $profile->id,
                'training_key' => $trainingKey,
                'training_name' => $trainingName,
                'status' => 'completed',
                'expiry_date' => now()->addYear()->toDateString(),
            ]);
        }
    }
}
