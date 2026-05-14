<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\GdprCase;
use App\Models\GovernanceAction;
use App\Models\GovernanceComplaint;
use App\Models\GovernanceMeeting;
use App\Models\GovernancePolicy;
use App\Models\Home;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GovernanceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_governance_workflows_create_cases_actions_and_audit_outcomes(): void
    {
        [$admin, $client, $owner] = $this->fixtures();

        $this->actingAs($admin)
            ->get(route('governance.index'))
            ->assertOk()
            ->assertSee('Governance Workbench')
            ->assertSee('New complaint')
            ->assertSee('New GDPR case')
            ->assertSee('New policy')
            ->assertSee('New meeting');

        $this->actingAs($admin)
            ->post(route('governance.complaints.store'), [
                'client_id' => $client->id,
                'owner_user_id' => $owner->id,
                'complainant_name' => 'Family Representative',
                'complainant_contact' => 'family@example.com',
                'source' => 'Family',
                'category' => 'Care quality',
                'severity' => 'high',
                'status' => 'open',
                'summary' => 'Medication support was late and family requested investigation.',
                'received_at' => '2026-05-14 09:00:00',
                'due_at' => '2026-05-21',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $complaint = GovernanceComplaint::firstOrFail();
        $this->assertSame('open', $complaint->status);
        $this->assertStringStartsWith('CMP-', $complaint->reference);

        $this->actingAs($admin)
            ->post(route('governance.gdpr-cases.store'), [
                'client_id' => $client->id,
                'owner_user_id' => $owner->id,
                'requester_name' => 'Data Subject',
                'requester_contact' => 'subject@example.com',
                'request_type' => 'sar',
                'risk_level' => 'medium',
                'status' => 'open',
                'summary' => 'Subject access request for visit and assessment records.',
                'received_at' => '2026-05-14 10:00:00',
                'response_due_at' => '2026-06-13',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $gdprCase = GdprCase::firstOrFail();
        $this->assertStringStartsWith('GDP-', $gdprCase->reference);

        $this->actingAs($admin)
            ->post(route('governance.policies.store'), [
                'owner_user_id' => $owner->id,
                'title' => 'Complaints and Duty of Candour Policy',
                'category' => 'Quality assurance',
                'version' => '1.0',
                'status' => 'draft',
                'summary' => 'Defines investigation, evidence, response, and review controls.',
                'review_due_at' => '2026-11-14',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $policy = GovernancePolicy::firstOrFail();
        $this->assertSame('draft', $policy->status);
        $this->assertStringStartsWith('POL-', $policy->reference);

        $this->actingAs($admin)
            ->post(route('governance.meetings.store'), [
                'chair_user_id' => $owner->id,
                'meeting_type' => 'Quality governance review',
                'status' => 'scheduled',
                'scheduled_at' => '2026-05-20 14:00:00',
                'attendees' => 'Registered Manager, Quality Lead',
                'agenda' => 'Review complaint themes, GDPR cases, audits, and overdue actions.',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $meeting = GovernanceMeeting::firstOrFail();
        $this->assertSame('scheduled', $meeting->status);
        $this->assertStringStartsWith('MTG-', $meeting->reference);

        $this->actingAs($admin)
            ->put(route('governance.meetings.update', $meeting), [
                'chair_user_id' => $owner->id,
                'meeting_type' => $meeting->meeting_type,
                'status' => 'completed',
                'scheduled_at' => '2026-05-20 14:00:00',
                'attendees' => $meeting->attendees,
                'agenda' => $meeting->agenda,
                'minutes' => 'Reviewed governance risks and agreed policy follow-up actions.',
                'outcome' => 'Quality action plan approved and assigned.',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $this->assertSame('completed', $meeting->fresh()->status);

        $this->actingAs($admin)
            ->post(route('governance.actions.store'), [
                'governance_complaint_id' => $complaint->id,
                'gdpr_case_id' => null,
                'governance_policy_id' => null,
                'governance_meeting_id' => null,
                'owner_user_id' => $owner->id,
                'title' => 'Review late medication evidence',
                'description' => 'Check visit notes, MAR evidence, and family update.',
                'priority' => 'high',
                'status' => 'open',
                'due_at' => '2026-05-16',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $action = GovernanceAction::firstOrFail();
        $this->assertSame($complaint->id, $action->governance_complaint_id);

        $this->actingAs($admin)
            ->post(route('governance.actions.store'), [
                'governance_complaint_id' => null,
                'gdpr_case_id' => null,
                'governance_policy_id' => $policy->id,
                'governance_meeting_id' => null,
                'owner_user_id' => $owner->id,
                'title' => 'Update policy after governance review',
                'description' => 'Add evidence handling expectations agreed in governance meeting.',
                'priority' => 'medium',
                'status' => 'open',
                'due_at' => '2026-05-30',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $this->assertDatabaseHas('governance_actions', [
            'governance_policy_id' => $policy->id,
            'title' => 'Update policy after governance review',
        ]);

        $this->actingAs($admin)
            ->put(route('governance.actions.update', $action), [
                'governance_complaint_id' => $complaint->id,
                'gdpr_case_id' => null,
                'governance_policy_id' => null,
                'governance_meeting_id' => null,
                'owner_user_id' => $owner->id,
                'title' => $action->title,
                'description' => $action->description,
                'priority' => 'high',
                'status' => 'completed',
                'due_at' => '2026-05-16',
                'outcome' => 'Evidence reviewed and family update sent.',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $this->assertSame('completed', $action->fresh()->status);
        $this->assertNotNull($action->fresh()->completed_at);

        $this->actingAs($admin)
            ->put(route('governance.complaints.update', $complaint), [
                'client_id' => $client->id,
                'owner_user_id' => $owner->id,
                'complainant_name' => $complaint->complainant_name,
                'complainant_contact' => $complaint->complainant_contact,
                'source' => $complaint->source,
                'category' => $complaint->category,
                'severity' => 'high',
                'status' => 'resolved',
                'summary' => $complaint->summary,
                'outcome' => 'Root cause recorded and remedial action completed.',
                'received_at' => '2026-05-14 09:00:00',
                'due_at' => '2026-05-21',
            ])
            ->assertRedirect(route('governance.index', absolute: false));

        $this->assertSame('resolved', $complaint->fresh()->status);
        $this->assertNotNull($complaint->fresh()->closed_at);

        $this->assertDatabaseHas('audit_logs', ['action' => 'governance.complaint_opened']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'governance.gdpr_case_opened']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'governance.policy_created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'governance.meeting_completed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'governance.action_completed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'governance.complaint_closed']);

        $this->actingAs($admin)
            ->get(route('audit-logs.index', ['action' => 'governance.complaint_closed']))
            ->assertOk()
            ->assertSee('Review model changes and workflow-specific evidence events in one place.')
            ->assertSee('Complaint')
            ->assertSee('Complaint Closed');
    }

    /**
     * @return array{0: User, 1: Client, 2: User}
     */
    private function fixtures(): array
    {
        $admin = User::create([
            'name' => 'Governance Admin',
            'email' => 'governance@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->permissions()->attach(Permission::where('name', 'governance.manage')->firstOrFail());
        $admin->permissions()->attach(Permission::where('name', 'audit_logs.view')->firstOrFail());

        $owner = User::create([
            'name' => 'Quality Lead',
            'email' => 'quality@example.com',
            'password' => Hash::make('password'),
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

        $client = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Asha',
            'last_name' => 'Patel',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);

        return [$admin, $client, $owner];
    }
}
