<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FamilyPortalRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\FamilyMember;
use App\Models\FamilyPortalDocument;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyPortalController extends Controller
{
    public function show(FamilyPortalRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $familyMember = FamilyMember::with([
            'client.home',
            'clients',
        ])->findOrFail($request->integer('family_member_id'));

        if (! $familyMember->is_active) {
            abort(403, 'This family access account is not active.');
        }

        $client = $this->selectedClient($familyMember, $request->integer('client_id') ?: null);
        $familyMember->setRelation('client', $client);

        $auditLogger->log('family.portal_viewed', [
            'auditable' => $familyMember,
            'event' => 'FamilyPortal',
            'metadata' => [
                'family_member_id' => $familyMember->id,
                'client_id' => $client->id,
                'permissions' => $familyMember->accessSummary(),
            ],
        ]);

        return response()->json([
            'client' => $this->clientProfile($familyMember),
            'permissions' => $familyMember->accessSummary(),
            'care_plan_summary' => $familyMember->canAccess('can_view_care_updates') ? $this->carePlanSummary($familyMember) : null,
            'upcoming_visits' => $familyMember->canAccess('can_view_appointments') ? $this->upcomingVisits($familyMember) : [],
            'past_visits' => $familyMember->canAccess('can_view_visits') ? $this->pastVisits($familyMember) : [],
            'visit_notes_summary' => $familyMember->canAccess('can_view_visits') ? $this->visitSummary($familyMember) : [],
            'medication_summary' => $familyMember->canAccess('can_view_medication') ? $this->medicationSummary($familyMember) : null,
            'medication_records' => $familyMember->canAccess('can_view_medication') ? $this->medicationRecords($familyMember) : [],
            'incident_notifications' => $familyMember->canAccess('can_receive_incident_alerts') ? $this->incidentNotifications($familyMember) : [],
            'appointments' => $familyMember->canAccess('can_view_appointments') ? $this->appointments($familyMember) : [],
            'finance_summary' => $familyMember->canAccess('can_view_invoices') ? $this->financeSummary($familyMember) : null,
            'invoices' => $familyMember->canAccess('can_view_invoices') ? $this->invoiceSummary($familyMember) : null,
            'messages' => $familyMember->canAccess('can_view_staff_messages') ? $this->staffMessages($familyMember) : null,
            'documents' => $familyMember->canAccess('can_view_shared_documents') ? $this->documents($familyMember, false) : null,
            'sensitive_documents' => $familyMember->canAccess('can_view_sensitive_documents') ? $this->documents($familyMember, true) : null,
            'safeguarding_summary' => $familyMember->canAccess('can_view_safeguarding') ? $this->safeguardingSummary($familyMember) : null,
            'document_upload' => [
                'allowed' => $familyMember->canAccess('can_upload_documents'),
                'accepted_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt'],
                'max_file_size_mb' => 10,
            ],
        ]);
    }

    public function uploadDocument(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $validated = $request->validate([
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,txt', 'max:10240'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $familyMember = FamilyMember::with('clients')->findOrFail($validated['family_member_id']);

        abort_unless($familyMember->is_active, 403);
        abort_unless($familyMember->canAccess('can_upload_documents'), 403);

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

        return response()->json([
            'message' => 'Document uploaded.',
            'document' => [
                'document_id' => $document->id,
                'display_name' => $document->display_name,
                'original_filename' => $document->original_filename,
                'category' => $document->category,
                'is_sensitive' => false,
                'uploaded_at' => $document->uploaded_at?->toIso8601String(),
                'uploaded_by' => $familyMember->name,
            ],
        ]);
    }

    private function selectedClient(FamilyMember $familyMember, ?int $clientId): Client
    {
        $assignedClientIds = $familyMember->clients->pluck('id')->push($familyMember->client_id)->unique();
        $selectedClientId = $clientId ?: $familyMember->client_id;

        if (! $assignedClientIds->contains($selectedClientId)) {
            abort(403, 'This family member is not assigned to the selected client.');
        }

        return Client::query()
            ->with([
                'home',
                'billingProfile.activeContract.ratePlan',
                'billingProfile.charges',
                'billingProfile.invoices.payments',
                'billingProfile.payments',
                'billingProfile.statementEntries',
                'carePlans' => fn ($query) => $query->where('status', 'active')->latest('start_date'),
                'visits' => fn ($query) => $query->with(['carePlan', 'assignedWorker', 'medicationAdministrations.carer'])->latest('scheduled_start_at')->limit(40),
                'assessment.medical',
                'assessment.risk',
                'familyPortalDocuments.familyMember',
                'familyPortalDocuments.staffUploader',
                'familyPortalMessages.sender',
            ])
            ->findOrFail($selectedClientId);
    }

    private function assignedClient(FamilyMember $familyMember, int $clientId): Client
    {
        $assignedClientIds = $familyMember->clients->pluck('id')->push($familyMember->client_id)->unique();

        abort_unless($assignedClientIds->contains($clientId), 403);

        return Client::findOrFail($clientId);
    }

    private function clientProfile(FamilyMember $familyMember): array
    {
        $client = $familyMember->client;

        return [
            'id' => $client->id,
            'name' => $client->fullName(),
            'home_name' => $client->home?->name,
            'status' => $client->status,
        ];
    }

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
            'review_date' => $plan->review_date?->toDateString(),
            'care_goals' => $plan->care_goals,
            'risk_level' => $plan->risk_level,
        ];
    }

    private function visitSummary(FamilyMember $familyMember): array
    {
        return $familyMember->client->visits
            ->filter(fn (Visit $visit): bool => filled($visit->notes))
            ->take(10)
            ->map(fn (Visit $visit): array => [
                'visit_id' => $visit->id,
                'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
                'status' => $visit->status,
                'summary' => $visit->notes,
            ])
            ->values()
            ->all();
    }

    private function upcomingVisits(FamilyMember $familyMember): array
    {
        return $familyMember->client->visits
            ->filter(fn (Visit $visit): bool => $visit->scheduled_start_at !== null && $visit->scheduled_start_at->greaterThanOrEqualTo(now()))
            ->sortBy('scheduled_start_at')
            ->take(10)
            ->map(fn (Visit $visit): array => $this->visitPayload($visit))
            ->values()
            ->all();
    }

    private function pastVisits(FamilyMember $familyMember): array
    {
        return $familyMember->client->visits
            ->filter(fn (Visit $visit): bool => $visit->scheduled_start_at !== null && $visit->scheduled_start_at->lessThan(now()))
            ->sortByDesc('scheduled_start_at')
            ->take(10)
            ->map(fn (Visit $visit): array => $this->visitPayload($visit))
            ->values()
            ->all();
    }

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

    private function medicationRecords(FamilyMember $familyMember): array
    {
        return $familyMember->client->visits
            ->flatMap(fn (Visit $visit) => $visit->medicationAdministrations
                ->map(fn ($administration): array => [
                    'visit_id' => $visit->id,
                    'visit_title' => $visit->title,
                    'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
                    'carer_name' => $administration->carer?->name ?? $visit->assignedWorker?->name,
                    'title' => $administration->medication_name,
                    'detail' => $administration->notes,
                    'status' => $administration->outcome,
                    'completed_at' => $administration->administered_at?->toIso8601String(),
                ]))
            ->sortByDesc('completed_at')
            ->values()
            ->all();
    }

    private function incidentNotifications(FamilyMember $familyMember): array
    {
        return AuditLog::query()
            ->where('action', 'admin.alert.carer_issue_report')
            ->where('metadata->client_id', $familyMember->client->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'reported_at' => $log->created_at?->toIso8601String(),
                'category' => data_get($log->metadata, 'category'),
                'severity' => data_get($log->metadata, 'severity'),
                'message' => 'A manager-approved incident notification is available.',
            ])
            ->all();
    }

    private function staffMessages(FamilyMember $familyMember): array
    {
        return $familyMember->client->familyPortalMessages
            ->filter(fn ($message): bool => (bool) $message->visible_to_family)
            ->sortByDesc('sent_at')
            ->map(fn ($message): array => [
                'subject' => $message->subject,
                'message' => $message->message,
                'sent_at' => $message->sent_at?->toIso8601String(),
                'sent_by' => $message->sender?->name,
            ])
            ->values()
            ->all();
    }

    private function documents(FamilyMember $familyMember, bool $sensitive): array
    {
        return $familyMember->client->familyPortalDocuments
            ->filter(fn ($document): bool => (bool) $document->shared_with_family && (bool) $document->is_sensitive === $sensitive)
            ->sortByDesc('uploaded_at')
            ->map(fn ($document): array => [
                'document_id' => $document->id,
                'display_name' => $document->display_name,
                'original_filename' => $document->original_filename,
                'category' => $document->category,
                'is_sensitive' => (bool) $document->is_sensitive,
                'uploaded_at' => $document->uploaded_at?->toIso8601String(),
                'uploaded_by' => $document->familyMember?->name ?? $document->staffUploader?->name,
            ])
            ->values()
            ->all();
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

    private function appointments(FamilyMember $familyMember): array
    {
        return $familyMember->client->visits
            ->map(fn (Visit $visit): array => [
                'visit_id' => $visit->id,
                'title' => $visit->title,
                'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
                'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
                'status' => $visit->status,
                'assigned_worker_name' => $visit->assignedWorker?->name,
            ])
            ->values()
            ->all();
    }

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
                'open_invoice_count' => 0,
                'recent_payments' => [],
                'statement_entries' => [],
            ];
        }

        $openInvoices = $profile->invoices
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
            'open_invoice_count' => $openInvoices->count(),
            'recent_payments' => $profile->payments
                ->sortByDesc('payment_date')
                ->take(5)
                ->map(fn ($payment): array => [
                    'payment_number' => $payment->payment_number,
                    'payment_date' => $payment->payment_date?->toDateString(),
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method,
                    'reference' => $payment->reference,
                ])
                ->values()
                ->all(),
            'statement_entries' => $profile->statementEntries
                ->sortByDesc('entry_date')
                ->take(8)
                ->map(fn ($entry): array => [
                    'entry_date' => $entry->entry_date?->toDateString(),
                    'entry_type' => $entry->entry_type,
                    'description' => $entry->description,
                    'debit' => (float) $entry->debit,
                    'credit' => (float) $entry->credit,
                    'running_balance' => (float) $entry->running_balance,
                ])
                ->values()
                ->all(),
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

    private function invoiceSummary(FamilyMember $familyMember): array
    {
        $profile = $familyMember->client->billingProfile;

        if (! $profile) {
            return [];
        }

        return $profile->invoices
            ->sortByDesc('issue_date')
            ->take(10)
            ->map(fn ($invoice): array => [
                'invoice_number' => $invoice->invoice_number,
                'period_start' => $invoice->period_start?->toDateString(),
                'period_end' => $invoice->period_end?->toDateString(),
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'currency' => $invoice->currency,
                'total_amount' => (float) $invoice->total_amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'balance_due' => (float) $invoice->balance_due,
                'status' => $invoice->status,
                'is_overdue' => $invoice->due_date !== null && $invoice->due_date->isPast() && (float) $invoice->balance_due > 0,
            ])
            ->values()
            ->all();
    }

    private function visitPayload(Visit $visit): array
    {
        return [
            'visit_id' => $visit->id,
            'title' => $visit->title,
            'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
            'status' => $visit->status,
            'assigned_worker_name' => $visit->assignedWorker?->name,
            'check_in_at' => $visit->check_in_at?->toIso8601String(),
            'check_out_at' => $visit->check_out_at?->toIso8601String(),
            'did_carer_attend' => $visit->check_in_at !== null || in_array($visit->status, ['in_progress', 'completed'], true),
            'notes' => $visit->notes,
        ];
    }
}
