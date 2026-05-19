<?php

namespace Tests\Feature;

use App\Models\BillingCharge;
use App\Models\BillingInvoice;
use App\Models\BillingProfile;
use App\Models\BillingRatePlan;
use App\Models\Client;
use App\Models\Home;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BillingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_billing_workflow_profiles_contract_charges_invoice_payment_receipt_and_audit(): void
    {
        [$admin, $client] = $this->fixtures();

        $this->actingAs($admin)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Care Home Billing Workbench')
            ->assertSee('Billing profile')
            ->assertSee('Generate invoice');

        $this->actingAs($admin)
            ->post(route('billing.profiles.store'), [
                'client_id' => $client->id,
                'admission_date' => '2026-05-01',
                'room_bed' => 'A1',
                'billing_contact_name' => 'Family Sponsor',
                'billing_contact_relationship' => 'Daughter',
                'billing_contact_email' => 'sponsor@example.com',
                'billing_contact_phone' => '555-0100',
                'funding_source' => 'family_sponsor',
                'payment_terms' => 'Due on the 1st of every month',
                'currency' => 'USD',
                'tax_rate' => '0',
                'tax_exempt' => '1',
                'status' => 'active',
            ])
            ->assertRedirect(route('billing.index', ['tab' => 'profiles'], false));

        $profile = BillingProfile::firstOrFail();
        $this->assertSame('family_sponsor', $profile->funding_source);

        $this->actingAs($admin)
            ->post(route('billing.rate-plans.store'), [
                'name' => 'Standard Residential Care',
                'description' => 'Monthly room and care agreement.',
                'currency' => 'USD',
                'room_fee' => '800',
                'care_fee' => '400',
                'meals_included' => '1',
                'billing_cycle' => 'monthly',
                'due_day' => '1',
                'deposit_amount' => '200',
                'notice_period_days' => '30',
                'late_fee_type' => 'fixed',
                'late_fee_amount' => '25',
                'discount_type' => 'fixed',
                'discount_amount' => '80',
                'status' => 'active',
            ])
            ->assertRedirect(route('billing.index', ['tab' => 'contracts'], false));

        $ratePlan = BillingRatePlan::firstOrFail();

        $this->actingAs($admin)
            ->post(route('billing.contracts.store'), [
                'billing_profile_id' => $profile->id,
                'billing_rate_plan_id' => $ratePlan->id,
                'start_date' => '2026-05-01',
                'end_date' => null,
                'billing_cycle' => 'monthly',
                'due_day' => '1',
                'deposit_amount' => '200',
                'notice_period_days' => '30',
                'late_fee_type' => 'fixed',
                'late_fee_amount' => '25',
                'care_level_pricing' => "Standard care: 400\nHigh care: 650",
                'discount_type' => 'fixed',
                'discount_amount' => '80',
                'status' => 'active',
            ])
            ->assertRedirect(route('billing.index', ['tab' => 'contracts'], false));

        $contract = $profile->contracts()->firstOrFail();
        $this->assertSame(['Standard care' => 400.0, 'High care' => 650.0], $contract->care_level_pricing);

        $this->actingAs($admin)
            ->post(route('billing.charges.store'), [
                'billing_profile_id' => $profile->id,
                'billing_contract_id' => $contract->id,
                'staff_user_id' => $admin->id,
                'charge_type' => 'variable',
                'category' => 'medication_purchase',
                'description' => 'Medication purchase',
                'charge_date' => '2026-05-10',
                'amount' => '50',
                'approval_status' => 'pending',
            ])
            ->assertRedirect(route('billing.index', ['tab' => 'charges'], false));

        $charge = BillingCharge::firstOrFail();
        $this->assertSame('pending', $charge->approval_status);

        $this->actingAs($admin)
            ->post(route('billing.charges.approve', $charge))
            ->assertRedirect(route('billing.index', ['tab' => 'charges'], false));

        $this->assertSame('approved', $charge->fresh()->approval_status);

        $this->actingAs($admin)
            ->post(route('billing.invoices.generate'), [
                'billing_contract_id' => $contract->id,
                'period_start' => '2026-05-01',
                'period_end' => '2026-05-31',
            ])
            ->assertRedirect(route('billing.index', ['tab' => 'invoices'], false));

        $invoice = BillingInvoice::with('items')->firstOrFail();
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame('2220.00', $invoice->total_amount);
        $this->assertSame('200.00', $invoice->paid_amount);
        $this->assertSame('2020.00', $invoice->balance_due);
        $this->assertNotNull($invoice->locked_at);
        $this->assertSame(6, $invoice->items()->count());

        $this->actingAs($admin)
            ->post(route('billing.payments.store'), [
                'billing_invoice_id' => $invoice->id,
                'payment_date' => '2026-05-15',
                'amount' => '700',
                'method' => 'bank_transfer',
                'reference' => 'BANK-001',
                'payer_name' => 'Family Sponsor',
                'notes' => 'Part payment received.',
            ])
            ->assertRedirect(route('billing.index', ['tab' => 'payments'], false));

        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);
        $this->assertSame('900.00', $invoice->paid_amount);
        $this->assertSame('1320.00', $invoice->balance_due);
        $this->assertDatabaseHas('billing_receipts', ['amount' => 700]);
        $this->assertDatabaseHas('billing_statement_entries', ['description' => 'Invoice '.$invoice->invoice_number]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.profile_created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.charge_approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.invoice_generated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.payment_recorded']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'billing.receipt_generated']);
    }

    public function test_payment_cannot_exceed_invoice_balance(): void
    {
        [$admin, $client] = $this->fixtures();
        $profile = BillingProfile::create([
            'client_id' => $client->id,
            'billing_contact_name' => 'Sponsor',
            'funding_source' => 'private_self_pay',
            'payment_terms' => 'Due on receipt',
            'currency' => 'USD',
            'tax_rate' => 0,
            'tax_exempt' => true,
            'status' => 'active',
        ]);

        $invoice = BillingInvoice::create([
            'billing_profile_id' => $profile->id,
            'invoice_number' => 'INV-20260515-9999',
            'issue_date' => '2026-05-15',
            'due_date' => '2026-06-01',
            'currency' => 'USD',
            'subtotal' => 100,
            'total_amount' => 100,
            'balance_due' => 100,
            'status' => 'sent',
            'locked_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('billing.payments.store'), [
                'billing_invoice_id' => $invoice->id,
                'payment_date' => '2026-05-15',
                'amount' => '101',
                'method' => 'cash',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_payment_requires_payer_reference_and_notes(): void
    {
        [$admin, $client] = $this->fixtures();
        $profile = BillingProfile::create([
            'client_id' => $client->id,
            'billing_contact_name' => 'Sponsor',
            'funding_source' => 'private_self_pay',
            'payment_terms' => 'Due on receipt',
            'currency' => 'USD',
            'tax_rate' => 0,
            'tax_exempt' => true,
            'status' => 'active',
        ]);

        $invoice = BillingInvoice::create([
            'billing_profile_id' => $profile->id,
            'invoice_number' => 'INV-20260515-9998',
            'issue_date' => '2026-05-15',
            'due_date' => '2026-06-01',
            'currency' => 'USD',
            'subtotal' => 100,
            'total_amount' => 100,
            'balance_due' => 100,
            'status' => 'sent',
            'locked_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('billing.payments.store'), [
                'billing_invoice_id' => $invoice->id,
                'payment_date' => '2026-05-15',
                'amount' => '50',
                'method' => 'cash',
                'payer_name' => '',
                'reference' => '',
                'notes' => '',
            ])
            ->assertSessionHasErrors(['payer_name', 'reference', 'notes']);
    }

    /**
     * @return array{0: User, 1: Client}
     */
    private function fixtures(): array
    {
        $admin = User::create([
            'name' => 'Billing Admin',
            'email' => 'billing@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->permissions()->attach(Permission::where('name', 'billing.manage')->firstOrFail());

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
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'active',
            'onboarding_status' => Client::ONBOARDING_STATUS_APPROVED,
        ]);

        return [$admin, $client];
    }
}
