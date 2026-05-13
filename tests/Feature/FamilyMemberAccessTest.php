<?php

namespace Tests\Feature;

use App\Models\CarePlan;
use App\Models\Client;
use App\Models\ClientAssessment;
use App\Models\ClientMedicalAssessment;
use App\Models\FamilyMember;
use App\Models\Home;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FamilyMemberAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_family_access_and_portal_filters_by_permissions(): void
    {
        $manager = $this->managerUser();
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
            'email' => 'asha@example.test',
            'phone' => '07123456789',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);
        $assessment = ClientAssessment::create([
            'client_id' => $client->id,
            'home_id' => $home->id,
            'version' => 1,
            'status' => 'approved',
        ]);
        ClientMedicalAssessment::create([
            'client_assessment_id' => $assessment->id,
            'medications' => 'Prompt only for morning medication.',
            'allergies' => 'Latex allergy.',
            'medication_support_needed' => true,
        ]);
        CarePlan::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'title' => 'Morning plan',
            'plan_type' => 'Initial',
            'start_date' => now()->toDateString(),
            'care_goals' => 'Stay safe at home.',
            'status' => 'active',
        ]);
        Visit::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'title' => 'Morning visit',
            'scheduled_start_at' => now()->setTime(9, 0),
            'scheduled_end_at' => now()->setTime(10, 0),
            'status' => 'completed',
            'notes' => 'Client was settled and had breakfast.',
        ]);

        $this->actingAs($manager)
            ->post(route('family-members.store'), [
                'client_id' => $client->id,
                'name' => 'Maya Patel',
                'relationship' => 'Daughter',
                'email' => 'maya@example.test',
                'phone' => '07999999999',
                'password' => 'family-password',
                'password_confirmation' => 'family-password',
                'is_active' => '1',
                'can_view_care_updates' => '1',
                'can_view_medication' => '0',
                'can_receive_incident_alerts' => '0',
                'can_view_appointments' => '1',
                'can_view_visits' => '1',
                'can_view_staff_messages' => '1',
            ])
            ->assertRedirect(route('family-members.index'));

        $familyMember = FamilyMember::firstOrFail();
        $this->assertNotNull($familyMember->login_created_at);
        $this->assertSame($manager->id, $familyMember->login_created_by);

        $this->postJson(route('api.family.login'), [
            'email' => 'maya@example.test',
            'password' => 'family-password',
        ])
            ->assertOk()
            ->assertJsonPath('family_member.permissions.can_view_care_updates', true)
            ->assertJsonPath('family_member.permissions.can_view_medication', false);

        $this->getJson(route('api.family.portal', ['family_member_id' => $familyMember->id]))
            ->assertOk()
            ->assertJsonPath('client.name', 'Asha Patel')
            ->assertJsonPath('care_plan_summary.title', 'Morning plan')
            ->assertJsonPath('visit_notes_summary.0.summary', 'Client was settled and had breakfast.')
            ->assertJsonPath('appointments.0.title', 'Morning visit')
            ->assertJsonPath('medication_summary', null)
            ->assertJsonPath('incident_notifications', []);

        $this->assertDatabaseHas('audit_logs', ['action' => 'family.access_permissions_updated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'family.login_account_created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'family.login']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'family.portal_viewed']);
    }

    private function managerUser(): User
    {
        $role = Role::create(['name' => 'Administrator', 'is_active' => true]);
        $permission = Permission::create([
            'name' => 'family_members.manage',
            'description' => 'Manage family access.',
            'is_active' => true,
        ]);
        $role->permissions()->attach($permission);
        $user = User::create([
            'name' => 'Manager',
            'email' => 'manager@example.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $user->roles()->attach($role);

        return $user;
    }
}
