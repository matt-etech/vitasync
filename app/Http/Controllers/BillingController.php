<?php

namespace App\Http\Controllers;

use App\Models\BillingCharge;
use App\Models\BillingContract;
use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\BillingProfile;
use App\Models\BillingRatePlan;
use App\Models\BillingStatementEntry;
use App\Models\Client;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Billing\InvoiceGenerationService;
use App\Services\Billing\PaymentAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(): View
    {
        return view('billing.index', [
            'profiles' => BillingProfile::with(['client.home', 'activeContract.ratePlan'])->latest()->get(),
            'ratePlans' => BillingRatePlan::latest()->get(),
            'contracts' => BillingContract::with(['profile.client', 'ratePlan'])->latest()->get(),
            'charges' => BillingCharge::with(['profile.client', 'contract.ratePlan', 'staff', 'approver', 'invoice'])->latest('charge_date')->get(),
            'invoices' => BillingInvoice::with(['profile.client', 'contract.ratePlan', 'payments.receipt'])->latest('issue_date')->get(),
            'payments' => BillingPayment::with(['profile.client', 'invoice', 'receipt', 'receiver'])->latest('payment_date')->get(),
            'statementEntries' => BillingStatementEntry::with('profile.client')->latest('entry_date')->latest('id')->get(),
            'clients' => Client::with(['home', 'billingProfile'])->where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get(),
            'staff' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'fundingSources' => BillingProfile::FUNDING_SOURCES,
            'profileStatuses' => BillingProfile::STATUSES,
            'billingCycles' => BillingRatePlan::BILLING_CYCLES,
            'lateFeeTypes' => BillingRatePlan::LATE_FEE_TYPES,
            'discountTypes' => BillingRatePlan::DISCOUNT_TYPES,
            'ratePlanStatuses' => BillingRatePlan::STATUSES,
            'contractStatuses' => BillingContract::STATUSES,
            'chargeTypes' => BillingCharge::CHARGE_TYPES,
            'chargeCategories' => BillingCharge::CATEGORIES,
            'approvalStatuses' => BillingCharge::APPROVAL_STATUSES,
            'paymentMethods' => BillingPayment::METHODS,
        ]);
    }

    public function storeProfile(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id', 'unique:billing_profiles,client_id'],
            'admission_date' => ['nullable', 'date'],
            'room_bed' => ['nullable', 'string', 'max:255'],
            'billing_contact_name' => ['required', 'string', 'max:255'],
            'billing_contact_relationship' => ['nullable', 'string', 'max:255'],
            'billing_contact_email' => ['nullable', 'email', 'max:255'],
            'billing_contact_phone' => ['nullable', 'string', 'max:255'],
            'funding_source' => ['required', Rule::in(array_keys(BillingProfile::FUNDING_SOURCES))],
            'payment_terms' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_exempt' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(array_keys(BillingProfile::STATUSES))],
        ]);
        $attributes['currency'] = strtoupper($attributes['currency']);
        $attributes['tax_exempt'] = $request->boolean('tax_exempt');

        $profile = BillingProfile::create($attributes);

        $auditLogger->log('billing.profile_created', [
            'auditable' => $profile,
            'event' => 'BillingProfile',
            'new_values' => $profile->only(['client_id', 'funding_source', 'payment_terms', 'currency', 'status']),
        ]);

        return redirect()->route('billing.index')->with('status', 'Resident billing profile created.');
    }

    public function storeRatePlan(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:billing_rate_plans,name'],
            'description' => ['nullable', 'string', 'max:5000'],
            'currency' => ['required', 'string', 'size:3'],
            'room_fee' => ['required', 'numeric', 'min:0'],
            'care_fee' => ['required', 'numeric', 'min:0'],
            'meals_included' => ['nullable', 'boolean'],
            'billing_cycle' => ['required', Rule::in(array_keys(BillingRatePlan::BILLING_CYCLES))],
            'due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'notice_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'late_fee_type' => ['required', Rule::in(array_keys(BillingRatePlan::LATE_FEE_TYPES))],
            'late_fee_amount' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(array_keys(BillingRatePlan::DISCOUNT_TYPES))],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(BillingRatePlan::STATUSES))],
        ]);
        $attributes['currency'] = strtoupper($attributes['currency']);
        $attributes['meals_included'] = $request->boolean('meals_included');

        $ratePlan = BillingRatePlan::create($attributes);

        $auditLogger->log('billing.rate_plan_created', [
            'auditable' => $ratePlan,
            'event' => 'BillingRatePlan',
            'new_values' => $ratePlan->only(['name', 'room_fee', 'care_fee', 'billing_cycle', 'status']),
        ]);

        return redirect()->route('billing.index')->with('status', 'Rate plan created.');
    }

    public function storeContract(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $request->validate([
            'billing_profile_id' => ['required', 'integer', 'exists:billing_profiles,id'],
            'billing_rate_plan_id' => ['required', 'integer', 'exists:billing_rate_plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'billing_cycle' => ['required', Rule::in(array_keys(BillingRatePlan::BILLING_CYCLES))],
            'due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'notice_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'late_fee_type' => ['required', Rule::in(array_keys(BillingRatePlan::LATE_FEE_TYPES))],
            'late_fee_amount' => ['required', 'numeric', 'min:0'],
            'care_level_pricing' => ['nullable', 'string', 'max:5000'],
            'discount_type' => ['nullable', Rule::in(array_keys(BillingRatePlan::DISCOUNT_TYPES))],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(BillingContract::STATUSES))],
        ]);
        $attributes['care_level_pricing'] = $this->decodeOptionalJson($attributes['care_level_pricing'] ?? null, 'care_level_pricing');

        if ($attributes['status'] === 'active') {
            BillingContract::where('billing_profile_id', $attributes['billing_profile_id'])
                ->where('status', 'active')
                ->update(['status' => 'ended', 'end_date' => now()->toDateString()]);
        }

        $contract = BillingContract::create($attributes);

        $auditLogger->log('billing.contract_created', [
            'auditable' => $contract,
            'event' => 'BillingContract',
            'new_values' => $contract->only(['billing_profile_id', 'billing_rate_plan_id', 'start_date', 'billing_cycle', 'status']),
        ]);

        return redirect()->route('billing.index')->with('status', 'Billing contract created and linked to its rate plan.');
    }

    public function storeCharge(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $request->validate([
            'billing_profile_id' => ['required', 'integer', 'exists:billing_profiles,id'],
            'billing_contract_id' => ['nullable', 'integer', 'exists:billing_contracts,id'],
            'staff_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'charge_type' => ['required', Rule::in(array_keys(BillingCharge::CHARGE_TYPES))],
            'category' => ['required', Rule::in(array_keys(BillingCharge::CATEGORIES))],
            'description' => ['required', 'string', 'max:255'],
            'charge_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'is_credit' => ['nullable', 'boolean'],
            'approval_status' => ['required', Rule::in(array_keys(BillingCharge::APPROVAL_STATUSES))],
        ]);

        $attributes['staff_user_id'] = ($attributes['staff_user_id'] ?? null) ?: Auth::id();
        $attributes['is_credit'] = $request->boolean('is_credit') || in_array($attributes['charge_type'], ['discount', 'credit'], true);

        if ($attributes['approval_status'] === 'approved') {
            $attributes['approved_by_user_id'] = Auth::id();
            $attributes['approved_at'] = now();
        }

        $charge = BillingCharge::create($attributes);

        $auditLogger->log('billing.charge_created', [
            'auditable' => $charge,
            'event' => 'BillingCharge',
            'new_values' => $charge->only(['billing_profile_id', 'charge_type', 'category', 'amount', 'approval_status']),
        ]);

        return redirect()->route('billing.index')->with('status', 'Charge recorded with audit evidence.');
    }

    public function approveCharge(BillingCharge $charge, AuditLogger $auditLogger): RedirectResponse
    {
        if ($charge->billing_invoice_id !== null) {
            throw ValidationException::withMessages([
                'charge' => 'Charges already attached to an invoice cannot be changed.',
            ]);
        }

        $charge->update([
            'approval_status' => 'approved',
            'approved_by_user_id' => Auth::id(),
            'approved_at' => now(),
        ]);

        $auditLogger->log('billing.charge_approved', [
            'auditable' => $charge,
            'event' => 'BillingCharge',
            'new_values' => $charge->only(['approval_status', 'approved_by_user_id', 'approved_at']),
        ]);

        return redirect()->route('billing.index')->with('status', 'Charge approved for invoicing.');
    }

    public function generateInvoice(Request $request, InvoiceGenerationService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $request->validate([
            'billing_contract_id' => ['required', 'integer', 'exists:billing_contracts,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $contract = BillingContract::with(['profile', 'ratePlan'])->findOrFail($attributes['billing_contract_id']);

        if ($contract->status !== 'active') {
            throw ValidationException::withMessages([
                'billing_contract_id' => 'Invoices can only be generated for active contracts.',
            ]);
        }

        $invoice = $service->generate(
            $contract,
            Carbon::parse($attributes['period_start']),
            Carbon::parse($attributes['period_end'])
        );

        $auditLogger->log('billing.invoice_generated', [
            'auditable' => $invoice,
            'event' => 'BillingInvoice',
            'new_values' => $invoice->only(['invoice_number', 'total_amount', 'balance_due', 'status']),
            'metadata' => ['period_start' => $attributes['period_start'], 'period_end' => $attributes['period_end']],
        ]);

        return redirect()->route('billing.index')->with('status', 'Invoice '.$invoice->invoice_number.' generated and locked.');
    }

    public function recordPayment(Request $request, PaymentAllocationService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $request->validate([
            'billing_invoice_id' => ['required', 'integer', 'exists:billing_invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(array_keys(BillingPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:255'],
            'payer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $invoice = BillingInvoice::with('profile')->findOrFail($attributes['billing_invoice_id']);
        $result = $service->record($invoice, $attributes + ['received_by_user_id' => Auth::id()]);
        $payment = $result['payment'];
        $receipt = $result['receipt'];

        $auditLogger->log('billing.payment_recorded', [
            'auditable' => $payment,
            'event' => 'BillingPayment',
            'new_values' => $payment->only(['payment_number', 'billing_invoice_id', 'amount', 'method']),
        ]);
        $auditLogger->log('billing.receipt_generated', [
            'auditable' => $receipt,
            'event' => 'BillingReceipt',
            'new_values' => $receipt->only(['receipt_number', 'amount', 'currency']),
        ]);

        return redirect()->route('billing.index')->with('status', 'Payment recorded and receipt '.$receipt->receipt_number.' generated.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeOptionalJson(?string $value, string $field): ?array
    {
        if (blank($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => 'Enter valid JSON for care level pricing.',
            ]);
        }

        return $decoded;
    }
}
