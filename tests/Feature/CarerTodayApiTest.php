<?php

namespace Tests\Feature;

use App\Models\CarePlan;
use App\Models\Client;
use App\Models\Home;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CarerTodayApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_carer_can_load_today_visit_and_record_evv_actions(): void
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
            'email' => 'today-carer@example.com',
            'password' => Hash::make('password'),
            'home_id' => $home->id,
            'is_active' => true,
        ]);
        $carer->roles()->attach($carerRole);
        $client = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Asha',
            'last_name' => 'Patel',
            'address' => '10 Client Road',
            'latitude' => -22.560880,
            'longitude' => 17.065755,
            'geofence_radius_meters' => 75,
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);
        $carePlan = CarePlan::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'title' => 'Morning support plan',
            'plan_type' => 'Initial',
            'start_date' => now()->toDateString(),
            'medication_support_level' => 'Administered by staff',
            'medication_support' => 'Follow MAR chart.',
            'status' => 'active',
        ]);
        $visit = Visit::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'care_plan_id' => $carePlan->id,
            'assigned_user_id' => $carer->id,
            'title' => 'Morning visit',
            'scheduled_start_at' => now()->setTime(9, 30),
            'scheduled_end_at' => now()->setTime(10, 15),
            'status' => 'scheduled',
        ]);

        $this->getJson(route('api.carer.today', ['carer_id' => $carer->id]))
            ->assertOk()
            ->assertJsonPath('visit.client_name', 'Asha Patel')
            ->assertJsonPath('visit.client_latitude', -22.56088)
            ->assertJsonPath('visit.geofence_radius_meters', 75)
            ->assertJsonPath('visit.tasks.0.section', 'Medication');

        $this->postJson(route('api.carer.visits.location-event', $visit), [
            'carer_id' => $carer->id,
            'event_type' => 'arrived',
            'latitude' => -22.560880,
            'longitude' => 17.065755,
            'accuracy_meters' => 12,
            'distance_meters' => 2,
            'geofence_radius_meters' => 75,
            'recorded_at' => now()->setTime(9, 28)->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('visit.status', 'in_progress');

        $this->postJson(route('api.carer.visits.location-event', $visit), [
            'carer_id' => $carer->id,
            'event_type' => 'departed',
            'latitude' => -22.561900,
            'longitude' => 17.067000,
            'accuracy_meters' => 12,
            'distance_meters' => 185,
            'geofence_radius_meters' => 75,
            'recorded_at' => now()->setTime(10, 12)->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('visit.status', 'completed');

        $this->postJson(route('api.carer.visits.notes', $visit), [
            'carer_id' => $carer->id,
            'notes' => 'Client settled after morning medication support.',
            'recorded_at' => now()->setTime(10, 14)->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('visit.notes', 'Client settled after morning medication support.');

        $this->postJson(route('api.carer.visits.tasks', $visit), [
            'carer_id' => $carer->id,
            'task_key' => 'medication',
            'title' => 'Medication support completed',
            'detail' => 'Followed MAR chart.',
            'status' => 'completed',
            'completed_at' => now()->setTime(9, 45)->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('status', 'recorded');

        $this->postJson(route('api.carer.visits.vitals', $visit), [
            'carer_id' => $carer->id,
            'bp_systolic' => 128,
            'bp_diastolic' => 82,
            'pulse' => 76,
            'temperature' => 36.7,
            'blood_oxygen' => 98,
            'recorded_at' => now()->setTime(9, 50)->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('status', 'recorded');

        $this->postJson(route('api.carer.visits.evidence', $visit), [
            'carer_id' => $carer->id,
            'evidence_type' => 'signature',
            'label' => 'Client signature',
            'metadata' => ['source' => 'visit_audit_evidence'],
            'captured_at' => now()->setTime(10, 10)->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('status', 'recorded');

        $this->postJson(route('api.carer.issue-reports'), [
            'carer_id' => $carer->id,
            'visit_id' => $visit->id,
            'category' => 'Medication concern',
            'severity' => 'Critical',
            'notes' => 'Medication was refused and needs admin follow-up.',
            'reported_at' => now()->setTime(10, 11)->toIso8601String(),
        ])
            ->assertOk()
            ->assertJsonPath('status', 'queued');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'visit.location_arrived',
            'auditable_id' => $visit->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.alert.visit_departure',
            'auditable_id' => $visit->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'visit.notes_recorded',
            'auditable_id' => $visit->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'visit.task_completed',
            'auditable_id' => $visit->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'visit.vitals_recorded',
            'auditable_id' => $visit->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'visit.evidence_recorded',
            'auditable_id' => $visit->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.alert.carer_issue_report',
            'auditable_id' => $visit->id,
        ]);
    }
}
