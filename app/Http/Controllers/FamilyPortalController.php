<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\FamilyMember;
use App\Models\FamilyPortalDocument;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyPortalController extends Controller
{
    public function show(Request $request, AuditLogger $auditLogger): View|RedirectResponse
    {
        $familyMemberId = $request->session()->get('family_member_id');

        if (! $familyMemberId) {
            return redirect()->route('login');
        }

        $familyMember = FamilyMember::with([
            'client.home',
            'client.billingProfile.activeContract.ratePlan',
            'client.billingProfile.charges',
            'client.billingProfile.invoices.payments',
            'client.billingProfile.payments',
            'client.billingProfile.statementEntries',
            'client.carePlans' => fn ($query) => $query->where('status', 'active')->latest('start_date'),
            'client.visits' => fn ($query) => $query->with(['carePlan', 'assignedWorker', 'medicationAdministrations.carer'])->latest('scheduled_start_at')->limit(80),
            'client.assessment.medical',
            'client.assessment.risk',
            'client.familyPortalDocuments.familyMember',
            'client.familyPortalDocuments.staffUploader',
            'client.familyPortalMessages.sender',
        ])->find($familyMemberId);

        if (! $familyMember || ! $familyMember->is_active) {
            $request->session()->forget('family_member_id');

            return redirect()->route('login')->withErrors(['email' => 'This family access account is not active.']);
        }

        $auditLogger->log('family.portal_viewed', [
            'auditable' => $familyMember,
            'event' => 'FamilyPortal',
            'metadata' => [
                'family_member_id' => $familyMember->id,
                'client_id' => $familyMember->client_id,
                'permissions' => $familyMember->accessSummary(),
            ],
        ]);

        return view('family-portal.show', [
            'familyMember' => $familyMember,
            'carePlanSummary' => $familyMember->canAccess('can_view_care_updates') ? $this->carePlanSummary($familyMember) : null,
            'visitNotes' => $familyMember->canAccess('can_view_visits') ? $this->visitNotes($familyMember) : collect(),
            'appointments' => $familyMember->canAccess('can_view_appointments') ? $this->appointments($familyMember) : collect(),
            'sharedVisits' => $familyMember->canAccess('can_view_appointments') || $familyMember->canAccess('can_view_visits') ? $this->sharedVisits($familyMember) : collect(),
            'medicationSummary' => $familyMember->canAccess('can_view_medication') ? $this->medicationSummary($familyMember) : null,
            'medicationRecords' => $familyMember->canAccess('can_view_medication') ? $this->medicationRecords($familyMember) : collect(),
            'incidentNotifications' => $familyMember->canAccess('can_receive_incident_alerts') ? $this->incidentNotifications($familyMember) : collect(),
            'financeSummary' => $familyMember->canAccess('can_view_invoices') ? $this->financeSummary($familyMember) : null,
            'staffMessages' => $familyMember->canAccess('can_view_staff_messages') ? $this->staffMessages($familyMember) : collect(),
            'sharedDocuments' => $familyMember->canAccess('can_view_shared_documents') ? $this->sharedDocuments($familyMember, false) : collect(),
            'sensitiveDocuments' => $familyMember->canAccess('can_view_sensitive_documents') ? $this->sharedDocuments($familyMember, true) : collect(),
            'safeguardingSummary' => $familyMember->canAccess('can_view_safeguarding') ? $this->safeguardingSummary($familyMember) : null,
        ]);
    }

    public function uploadDocument(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $familyMember = $this->activeFamilyMember($request);

        abort_unless($familyMember->canAccess('can_upload_documents'), 403);

        $validated = $request->validate([
            'client_id' => ['required', 'integer'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,txt', 'max:10240'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $client = $this->assignedClient($familyMember, (int) $validated['client_id']);
        $file = $request->file('document');
        $path = $file->store('family-portal-documents');

        $document = FamilyPortalDocument::create([
            'client_id' => $client->id,
            'uploaded_by_family_member_id' => $familyMember->id,
            'display_name' => $validated['display_name'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'category' => $validated['category'] ?? 'Family upload',
            'is_sensitive' => false,
            'shared_with_family' => true,
            'uploaded_at' => now(),
        ]);

        $auditLogger->log('family.document_uploaded', [
            'auditable' => $document,
            'event' => 'Family document',
            'friendly_action' => 'Uploaded document',
            'friendly_subject' => $document->display_name,
            'friendly_actor' => $familyMember->name,
            'friendly_summary' => "{$familyMember->name} uploaded {$document->display_name} for {$client->fullName()}.",
            'metadata' => [
                'family_member_id' => $familyMember->id,
                'client_id' => $client->id,
                'filename' => $document->original_filename,
            ],
        ]);

        return redirect()->route('family-portal.show')->with('status', 'Document uploaded for the care team.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function carePlanSummary(FamilyMember $familyMember): ?array
    {
        $plan = $familyMember->client->carePlans->first();

        if (! $plan) {
            return null;
        }

        return [
            'title' => $plan->title,
            'care_level' => $plan->care_level,
            'visit_frequency' => $plan->visit_frequency,
            'review_date' => $plan->review_date,
            'care_goals' => $plan->care_goals,
            'risk_level' => $plan->risk_level,
            'preferences_routines' => $plan->preferences_routines,
        ];
    }

    private function visitNotes(FamilyMember $familyMember)
    {
        return $familyMember->client->visits
            ->filter(fn (Visit $visit): bool => filled($visit->notes))
            ->take(10)
            ->values();
    }

    private function appointments(FamilyMember $familyMember)
    {
        return $familyMember->client->visits
            ->take(10)
            ->values();
    }

    private function sharedVisits(FamilyMember $familyMember)
    {
        return $familyMember->client->visits
            ->filter(fn (Visit $visit): bool => $visit->scheduled_start_at !== null)
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function medicationSummary(FamilyMember $familyMember): ?array
    {
        $medical = $familyMember->client->assessment?->medical;

        if (! $medical) {
            return null;
        }

        return [
            'support_needed' => (bool) $medical->medication_support_needed,
            'support_summary' => $medical->medications,
            'allergies' => $medical->allergies,
            'support_level' => $familyMember->client->carePlans->first()?->medication_support_level,
            'care_plan_instructions' => $familyMember->client->carePlans->first()?->medication_support,
        ];
    }

    private function medicationRecords(FamilyMember $familyMember)
    {
        return $familyMember->client->visits
            ->flatMap(fn (Visit $visit) => $visit->medicationAdministrations
                ->map(fn ($administration): array => [
                    'visit_title' => $visit->title,
                    'scheduled_start_at' => $visit->scheduled_start_at,
                    'carer_name' => $administration->carer?->name ?? $visit->assignedWorker?->name,
                    'medication_name' => $administration->medication_name,
                    'dose' => $administration->dose,
                    'route' => $administration->route,
                    'outcome' => $administration->outcome,
                    'notes' => $administration->notes,
                    'administered_at' => $administration->administered_at,
                ]))
            ->sortByDesc('administered_at')
            ->values();
    }

    private function incidentNotifications(FamilyMember $familyMember)
    {
        return AuditLog::query()
            ->where('action', 'admin.alert.carer_issue_report')
            ->where('metadata->client_id', $familyMember->client_id)
            ->latest()
            ->limit(10)
            ->get();
    }

    private function staffMessages(FamilyMember $familyMember)
    {
        return $familyMember->client->familyPortalMessages
            ->filter(fn ($message): bool => (bool) $message->visible_to_family)
            ->sortByDesc('sent_at')
            ->values();
    }

    private function sharedDocuments(FamilyMember $familyMember, bool $sensitive)
    {
        return $familyMember->client->familyPortalDocuments
            ->filter(fn ($document): bool => (bool) $document->shared_with_family && (bool) $document->is_sensitive === $sensitive)
            ->sortByDesc('uploaded_at')
            ->values();
    }

    private function safeguardingSummary(FamilyMember $familyMember): ?array
    {
        $risk = $familyMember->client->assessment?->risk;

        if (! $risk || blank($risk->safeguarding_risk)) {
            return null;
        }

        return [
            'safeguarding_risk' => $risk->safeguarding_risk,
            'control_measures' => $risk->control_measures,
            'notes' => $risk->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function financeSummary(FamilyMember $familyMember): array
    {
        $profile = $familyMember->client->billingProfile;

        if (! $profile) {
            return [
                'currency' => null,
                'outstanding_balance' => 0,
                'overdue_balance' => 0,
                'pending_contract_total' => 0,
                'deposit_applied' => 0,
                'open_invoices' => collect(),
                'recent_payments' => collect(),
                'statement_entries' => collect(),
            ];
        }

        $invoices = $profile->invoices->sortByDesc('issue_date')->values();
        $openInvoices = $invoices
            ->filter(fn ($invoice): bool => ! in_array($invoice->status, ['paid', 'void'], true) && (float) $invoice->balance_due > 0)
            ->values();

        return [
            'currency' => $profile->currency,
            'outstanding_balance' => round((float) $openInvoices->sum('balance_due'), 2),
            'overdue_balance' => round((float) $openInvoices
                ->filter(fn ($invoice): bool => $invoice->due_date !== null && $invoice->due_date->isPast())
                ->sum('balance_due'), 2),
            'pending_contract_total' => $this->pendingContractTotal($profile),
            'deposit_applied' => $this->depositApplied($profile),
            'open_invoices' => $openInvoices,
            'recent_payments' => $profile->payments->sortByDesc('payment_date')->take(5)->values(),
            'statement_entries' => $profile->statementEntries->sortByDesc('entry_date')->take(8)->values(),
        ];
    }

    private function pendingContractTotal($profile): float
    {
        $contract = $profile->activeContract;

        if (! $contract) {
            return 0;
        }

        $subtotal = $this->contractRecurringSubtotal($contract) + $this->approvedUnbilledChargeTotal($profile, $contract->id);
        $discount = $this->contractDiscountFor($contract, $subtotal);
        $taxableAmount = max(0, $subtotal - $discount);
        $taxTotal = $profile->tax_exempt ? 0.0 : round($taxableAmount * ((float) $profile->tax_rate / 100), 2);
        $total = round($taxableAmount + $taxTotal, 2);
        $deposit = $this->depositToApply($contract, $total);

        return round($total - $deposit, 2);
    }

    private function contractRecurringSubtotal($contract): float
    {
        $ratePlan = $contract->ratePlan;
        $subtotal = (float) ($ratePlan?->room_fee ?? 0) + (float) ($ratePlan?->care_fee ?? 0);

        foreach (($contract->care_level_pricing ?? []) as $amount) {
            $subtotal += (float) $amount;
        }

        return round($subtotal, 2);
    }

    private function approvedUnbilledChargeTotal($profile, int $contractId): float
    {
        return round((float) $profile->charges
            ->filter(fn ($charge): bool => $charge->billing_invoice_id === null
                && $charge->approval_status === 'approved'
                && ($charge->billing_contract_id === null || $charge->billing_contract_id === $contractId))
            ->sum(fn ($charge): float => ((bool) $charge->is_credit || in_array($charge->charge_type, ['discount', 'credit'], true) ? -1 : 1) * (float) $charge->amount), 2);
    }

    private function contractDiscountFor($contract, float $subtotal): float
    {
        if ((float) $contract->discount_amount <= 0 || blank($contract->discount_type)) {
            return 0.0;
        }

        if ($contract->discount_type === 'percentage') {
            return round($subtotal * ((float) $contract->discount_amount / 100), 2);
        }

        return min(round((float) $contract->discount_amount, 2), $subtotal);
    }

    private function depositToApply($contract, float $total): float
    {
        if ((float) $contract->deposit_amount <= 0 || $contract->invoices()->exists()) {
            return 0.0;
        }

        return min(round((float) $contract->deposit_amount, 2), max(0, $total));
    }

    private function depositApplied($profile): float
    {
        $contract = $profile->activeContract;

        if (! $contract) {
            return 0;
        }

        $subtotal = $this->contractRecurringSubtotal($contract) + $this->approvedUnbilledChargeTotal($profile, $contract->id);
        $discount = $this->contractDiscountFor($contract, $subtotal);
        $taxableAmount = max(0, $subtotal - $discount);
        $taxTotal = $profile->tax_exempt ? 0.0 : round($taxableAmount * ((float) $profile->tax_rate / 100), 2);

        return $this->depositToApply($contract, round($taxableAmount + $taxTotal, 2));
    }

    private function activeFamilyMember(Request $request): FamilyMember
    {
        $familyMember = FamilyMember::with('clients')->findOrFail($request->session()->get('family_member_id'));

        abort_unless($familyMember->is_active, 403);

        return $familyMember;
    }

    private function assignedClient(FamilyMember $familyMember, int $clientId): Client
    {
        $assignedClientIds = $familyMember->clients->pluck('id')->push($familyMember->client_id)->unique();

        abort_unless($assignedClientIds->contains($clientId), 403);

        return Client::findOrFail($clientId);
    }
}
