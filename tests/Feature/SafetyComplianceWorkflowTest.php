<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Home;
use App\Models\Medication;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SafetyComplianceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_record_phase_two_safety_workflows(): void
    {
        $role = Role::create(['name' => 'Administrator', 'is_active' => true]);
        $user = User::create([
            'name' => 'Safety Manager',
            'email' => 'safety-manager@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role);
        $permission = Permission::create([
            'name' => 'clients.manage',
            'description' => 'Manage clients and safety workflows.',
            'is_active' => true,
        ]);
        $role->permissions()->attach($permission);

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
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);

        $this->actingAs($user)
            ->post(route('safety.risk-reviews.store'), [
                'client_id' => $client->id,
                'risk_domain' => 'Falls',
                'risk_level' => 'High',
                'hazards' => 'Unsteady transfer from chair.',
                'control_measures' => 'Prompt walking frame and clear route.',
                'review_date' => now()->toDateString(),
                'status' => 'open',
            ])
            ->assertRedirect();

        $this->post(route('safety.capacity-reviews.store'), [
            'client_id' => $client->id,
            'decision_type' => 'Medication',
            'capacity_outcome' => 'Has capacity',
            'best_interest_status' => 'Not required',
            'review_date' => now()->toDateString(),
            'evidence' => 'Client explained medication support preference.',
        ])->assertRedirect();

        $this->post(route('safety.consent-records.store'), [
            'client_id' => $client->id,
            'consent_type' => 'Medication support',
            'decision' => 'Consented',
            'given_by' => 'Client',
            'recorded_at' => now()->toIso8601String(),
            'evidence' => 'Verbal consent recorded during review.',
        ])->assertRedirect();

        $this->post(route('safety.medications.store'), [
            'client_id' => $client->id,
            'name' => 'Paracetamol',
            'dose' => '500mg',
            'route' => 'Oral',
            'frequency' => 'Morning',
            'support_level' => 'Administration by carer',
            'status' => 'active',
            'instructions' => 'Administer with water.',
        ])->assertRedirect();

        $medication = Medication::firstOrFail();

        $this->post(route('safety.medication-administrations.store'), [
            'medication_id' => $medication->id,
            'outcome' => 'Administered',
            'administered_at' => now()->toIso8601String(),
            'notes' => 'No concern.',
        ])->assertRedirect();

        $this->post(route('safety.incidents.store'), [
            'client_id' => $client->id,
            'category' => 'Medication',
            'severity' => 'High',
            'occurred_at' => now()->toIso8601String(),
            'description' => 'Medication was refused and required manager review.',
            'immediate_actions' => 'Manager notified and MAR checked.',
            'status' => 'open',
            'safeguarding_required' => '1',
        ])->assertRedirect();

        $this->post(route('safety.safeguarding-cases.store'), [
            'client_id' => $client->id,
            'concern_type' => 'Medication concern',
            'risk_level' => 'High',
            'status' => 'open',
            'opened_at' => now()->toIso8601String(),
            'summary' => 'Safeguarding review opened from medication incident.',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'risk.review_recorded']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'capacity.review_recorded']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'consent.recorded']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'medication.administration_recorded']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'incident.safeguarding_required']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'safeguarding.case_opened']);
    }
}
