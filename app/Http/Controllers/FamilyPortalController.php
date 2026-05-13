<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FamilyMember;
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
            'client.carePlans' => fn ($query) => $query->where('status', 'active')->latest('start_date'),
            'client.visits' => fn ($query) => $query->with('carePlan')->latest('scheduled_start_at')->limit(20),
            'client.assessment.medical',
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
            'medicationSummary' => $familyMember->canAccess('can_view_medication') ? $this->medicationSummary($familyMember) : null,
            'incidentNotifications' => $familyMember->canAccess('can_receive_incident_alerts') ? $this->incidentNotifications($familyMember) : collect(),
        ]);
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
        ];
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
}
