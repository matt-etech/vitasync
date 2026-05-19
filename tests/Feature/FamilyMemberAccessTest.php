<?php

namespace Tests\Feature;

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\BillingProfile;
use App\Models\BillingStatementEntry;
use App\Models\CarePlan;
use App\Models\Client;
use App\Models\ClientAssessment;
use App\Models\ClientMedicalAssessment;
use App\Models\ClientRiskAssessment;
use App\Models\FamilyMember;
use App\Models\FamilyPortalDocument;
use App\Models\FamilyPortalMessage;
use App\Models\Home;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitTaskRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        $secondClient = Client::create([
            'home_id' => $home->id,
            'first_name' => 'Nia',
            'last_name' => 'Shah',
            'email' => 'nia@example.test',
            'phone' => '07000000001',
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
        ClientRiskAssessment::create([
            'client_assessment_id' => $assessment->id,
            'safeguarding_risk' => 'Family should be informed about door safety concerns.',
            'control_measures' => 'Care team checks the door sensor at each visit.',
            'notes' => 'Share approved safeguarding summary only.',
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
        CarePlan::create([
            'home_id' => $home->id,
            'client_id' => $secondClient->id,
            'title' => 'Evening plan',
            'plan_type' => 'Initial',
            'start_date' => now()->toDateString(),
            'care_goals' => 'Maintain evening routine.',
            'status' => 'active',
        ]);
        $billingProfile = BillingProfile::create([
            'client_id' => $client->id,
            'billing_contact_name' => 'Maya Patel',
            'billing_contact_relationship' => 'Daughter',
            'billing_contact_email' => 'maya@example.test',
            'funding_source' => 'family_sponsor',
            'payment_terms' => 'Due on receipt',
            'currency' => 'USD',
            'tax_rate' => 0,
            'tax_exempt' => true,
            'status' => 'active',
        ]);
        $invoice = BillingInvoice::create([
            'billing_profile_id' => $billingProfile->id,
            'invoice_number' => 'INV-20260519-0001',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'currency' => 'USD',
            'subtotal' => 1170,
            'total_amount' => 1170,
            'paid_amount' => 700,
            'balance_due' => 470,
            'status' => 'partially_paid',
            'locked_at' => now()->subDays(10),
        ]);
        BillingPayment::create([
            'billing_profile_id' => $billingProfile->id,
            'billing_invoice_id' => $invoice->id,
            'payment_number' => 'PAY-20260519-0001',
            'payment_date' => now()->subDays(2)->toDateString(),
            'amount' => 700,
            'method' => 'bank_transfer',
            'reference' => 'BANK-001',
        ]);
        BillingStatementEntry::create([
            'billing_profile_id' => $billingProfile->id,
            'entry_date' => now()->subDay()->toDateString(),
            'entry_type' => 'invoice',
            'description' => 'Invoice INV-20260519-0001',
            'debit' => 1170,
            'credit' => 0,
            'running_balance' => 470,
        ]);
        FamilyPortalMessage::create([
            'client_id' => $client->id,
            'sent_by_user_id' => $manager->id,
            'subject' => 'Care update',
            'message' => 'The care team will review the plan on Friday.',
            'visible_to_family' => true,
            'sent_at' => now(),
        ]);
        FamilyPortalDocument::create([
            'client_id' => $client->id,
            'uploaded_by_user_id' => $manager->id,
            'display_name' => 'Welcome pack',
            'original_filename' => 'welcome-pack.pdf',
            'file_path' => 'family-portal-documents/welcome-pack.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
            'category' => 'Welcome',
            'is_sensitive' => false,
            'shared_with_family' => true,
            'uploaded_at' => now(),
        ]);
        FamilyPortalDocument::create([
            'client_id' => $client->id,
            'uploaded_by_user_id' => $manager->id,
            'display_name' => 'Sensitive care agreement',
            'original_filename' => 'care-agreement.pdf',
            'file_path' => 'family-portal-documents/care-agreement.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1200,
            'category' => 'Agreement',
            'is_sensitive' => true,
            'shared_with_family' => true,
            'uploaded_at' => now(),
        ]);
        $visit = Visit::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'assigned_user_id' => $manager->id,
            'title' => 'Morning visit',
            'scheduled_start_at' => now()->subDay()->setTime(9, 0),
            'scheduled_end_at' => now()->subDay()->setTime(10, 0),
            'status' => 'completed',
            'check_in_at' => now()->subDay()->setTime(8, 58),
            'check_out_at' => now()->subDay()->setTime(9, 52),
            'notes' => 'Client was settled and had breakfast.',
        ]);
        VisitTaskRecord::create([
            'visit_id' => $visit->id,
            'client_id' => $client->id,
            'carer_id' => $manager->id,
            'task_key' => 'medication',
            'title' => 'Medication support',
            'detail' => 'Medication given as planned.',
            'status' => 'completed',
            'completed_at' => now()->subDay()->setTime(9, 10),
        ]);
        Visit::create([
            'home_id' => $home->id,
            'client_id' => $client->id,
            'assigned_user_id' => $manager->id,
            'title' => 'Evening visit',
            'scheduled_start_at' => now()->addDay()->setTime(18, 0),
            'scheduled_end_at' => now()->addDay()->setTime(18, 30),
            'status' => 'scheduled',
        ]);

        $this->actingAs($manager)
            ->post(route('family-members.store'), [
                'client_id' => $client->id,
                'client_ids' => [$client->id, $secondClient->id],
                'name' => 'Maya Patel',
                'relationship' => 'Daughter',
                'email' => 'maya@example.test',
                'phone' => '07999999999',
                'password' => 'family-password',
                'password_confirmation' => 'family-password',
                'is_active' => '1',
                'can_view_care_updates' => '1',
                'can_view_medication' => '1',
                'can_receive_incident_alerts' => '0',
                'can_view_appointments' => '1',
                'can_view_visits' => '1',
                'can_view_invoices' => '1',
                'can_upload_documents' => '1',
                'can_view_staff_messages' => '1',
                'can_view_shared_documents' => '1',
                'can_view_sensitive_documents' => '1',
                'can_view_safeguarding' => '1',
            ])
            ->assertRedirect(route('family-members.index'));

        $familyMember = FamilyMember::firstOrFail();
        $this->assertNotNull($familyMember->login_created_at);
        $this->assertSame($manager->id, $familyMember->login_created_by);
        $this->assertCount(2, $familyMember->clients);

        $this->postJson(route('api.family.login'), [
            'email' => 'maya@example.test',
            'password' => 'family-password',
        ])
            ->assertOk()
            ->assertJsonCount(2, 'family_member.clients')
            ->assertJsonPath('family_member.clients.0.name', 'Asha Patel')
            ->assertJsonPath('family_member.permissions.can_view_care_updates', true)
            ->assertJsonPath('family_member.permissions.can_view_medication', true);

        $this->getJson(route('api.family.portal', ['family_member_id' => $familyMember->id]))
            ->assertOk()
            ->assertJsonPath('client.name', 'Asha Patel')
            ->assertJsonPath('care_plan_summary.title', 'Morning plan')
            ->assertJsonPath('upcoming_visits.0.title', 'Evening visit')
            ->assertJsonPath('upcoming_visits.0.assigned_worker_name', 'Manager')
            ->assertJsonPath('past_visits.0.title', 'Morning visit')
            ->assertJsonPath('past_visits.0.did_carer_attend', true)
            ->assertJsonPath('medication_records.0.status', 'completed')
            ->assertJsonPath('visit_notes_summary.0.summary', 'Client was settled and had breakfast.')
            ->assertJsonPath('appointments.0.title', 'Morning visit')
            ->assertJsonPath('medication_summary.support_needed', true)
            ->assertJsonPath('incident_notifications', [])
            ->assertJsonPath('finance_summary.outstanding_balance', 470.0)
            ->assertJsonPath('finance_summary.overdue_balance', 470.0)
            ->assertJsonPath('finance_summary.open_invoice_count', 1)
            ->assertJsonPath('finance_summary.recent_payments.0.amount', 700.0)
            ->assertJsonPath('invoices.0.invoice_number', 'INV-20260519-0001')
            ->assertJsonPath('invoices.0.balance_due', 470.0)
            ->assertJsonPath('messages.0.subject', 'Care update')
            ->assertJsonPath('documents.0.display_name', 'Welcome pack')
            ->assertJsonPath('sensitive_documents.0.display_name', 'Sensitive care agreement')
            ->assertJsonPath('safeguarding_summary.safeguarding_risk', 'Family should be informed about door safety concerns.')
            ->assertJsonPath('document_upload.allowed', true);

        $this->withSession(['family_member_id' => $familyMember->id])
            ->get(route('family-portal.show'))
            ->assertOk()
            ->assertSee('Visits')
            ->assertSee('Visit calendar')
            ->assertSee('Upcoming visits')
            ->assertSee('Past visits')
            ->assertSee('Evening visit')
            ->assertSee('Morning visit')
            ->assertSee('Finance')
            ->assertSee('Outstanding balance')
            ->assertSee('USD 470.00')
            ->assertSee('INV-20260519-0001')
            ->assertSee('Messages')
            ->assertSee('Documents')
            ->assertSee('Safeguarding')
            ->assertSee('Care update')
            ->assertSee('Welcome pack')
            ->assertSee('Sensitive care agreement')
            ->assertSee('Family should be informed about door safety concerns.');

        Storage::fake('local');
        $this->withSession(['family_member_id' => $familyMember->id])
            ->post(route('family-portal.documents.store'), [
                'client_id' => $client->id,
                'display_name' => 'Family notes',
                'category' => 'Family upload',
                'document' => UploadedFile::fake()->create('family-notes.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect(route('family-portal.show'));

        $this->assertDatabaseHas('family_portal_documents', [
            'client_id' => $client->id,
            'uploaded_by_family_member_id' => $familyMember->id,
            'display_name' => 'Family notes',
            'shared_with_family' => true,
            'is_sensitive' => false,
        ]);

        $this->getJson(route('api.family.portal', [
            'family_member_id' => $familyMember->id,
            'client_id' => $secondClient->id,
        ]))
            ->assertOk()
            ->assertJsonPath('client.name', 'Nia Shah')
            ->assertJsonPath('care_plan_summary.title', 'Evening plan')
            ->assertJsonPath('finance_summary.outstanding_balance', 0);

        $this->postJson(route('api.family.change-password'), [
            'family_member_id' => $familyMember->id,
            'current_password' => 'family-password',
            'password' => 'Newpass1',
            'password_confirmation' => 'Newpass1',
        ])->assertOk()->assertJsonPath('message', 'Password changed.');

        $this->assertTrue(Hash::check('Newpass1', $familyMember->fresh()->password));

        $this->assertDatabaseHas('audit_logs', ['action' => 'family.access_permissions_updated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'family.login_account_created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'family.login']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'family.portal_viewed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'family.password_changed']);
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
