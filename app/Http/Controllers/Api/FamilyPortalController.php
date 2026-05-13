<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FamilyPortalRequest;
use App\Models\AuditLog;
use App\Models\FamilyMember;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;

class FamilyPortalController extends Controller
{
    public function show(FamilyPortalRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $familyMember = FamilyMember::with([
            'client.home',
            'client.carePlans' => fn ($query) => $query->where('status', 'active')->latest('start_date'),
            'client.visits' => fn ($query) => $query->with('carePlan')->latest('scheduled_start_at')->limit(20),
            'client.assessment.medical',
        ])->findOrFail($request->integer('family_member_id'));

        if (! $familyMember->is_active) {
            abort(403, 'This family access account is not active.');
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

        return response()->json([
            'client' => $this->clientProfile($familyMember),
            'permissions' => $familyMember->accessSummary(),
            'care_plan_summary' => $familyMember->canAccess('can_view_care_updates') ? $this->carePlanSummary($familyMember) : null,
            'visit_notes_summary' => $familyMember->canAccess('can_view_visits') ? $this->visitSummary($familyMember) : [],
            'medication_summary' => $familyMember->canAccess('can_view_medication') ? $this->medicationSummary($familyMember) : null,
            'incident_notifications' => $familyMember->canAccess('can_receive_incident_alerts') ? $this->incidentNotifications($familyMember) : [],
            'appointments' => $familyMember->canAccess('can_view_appointments') ? $this->appointments($familyMember) : [],
            'invoices' => $familyMember->canAccess('can_view_invoices') ? [] : null,
            'messages' => $familyMember->canAccess('can_view_staff_messages') ? [] : null,
            'documents' => $familyMember->canAccess('can_view_shared_documents') ? [] : null,
        ]);
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
        ];
    }

    private function incidentNotifications(FamilyMember $familyMember): array
    {
        return AuditLog::query()
            ->where('action', 'admin.alert.carer_issue_report')
            ->where('metadata->client_id', $familyMember->client_id)
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

    private function appointments(FamilyMember $familyMember): array
    {
        return $familyMember->client->visits
            ->map(fn (Visit $visit): array => [
                'visit_id' => $visit->id,
                'title' => $visit->title,
                'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
                'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
                'status' => $visit->status,
            ])
            ->values()
            ->all();
    }
}
