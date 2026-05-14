<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\GdprCase;
use App\Models\GovernanceAction;
use App\Models\GovernanceComplaint;
use App\Models\GovernanceMeeting;
use App\Models\GovernancePolicy;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GovernanceController extends Controller
{
    public function index(): View
    {
        return view('governance.index', [
            'complaints' => GovernanceComplaint::with(['client.home', 'owner', 'actions.owner'])->latest('received_at')->get(),
            'gdprCases' => GdprCase::with(['client.home', 'owner', 'actions.owner'])->latest('received_at')->get(),
            'policies' => GovernancePolicy::with(['owner', 'actions.owner'])->orderBy('review_due_at')->get(),
            'meetings' => GovernanceMeeting::with(['chair', 'actions.owner'])->latest('scheduled_at')->get(),
            'actions' => GovernanceAction::with(['complaint', 'gdprCase', 'policy', 'meeting', 'owner'])->orderByRaw("case status when 'open' then 0 when 'in_progress' then 1 else 2 end")->orderBy('due_at')->get(),
            'clients' => Client::with('home')->orderBy('last_name')->orderBy('first_name')->get(),
            'owners' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'complaintStatuses' => GovernanceComplaint::STATUSES,
            'complaintSeverities' => GovernanceComplaint::SEVERITIES,
            'gdprStatuses' => GdprCase::STATUSES,
            'gdprTypes' => GdprCase::REQUEST_TYPES,
            'riskLevels' => GdprCase::RISK_LEVELS,
            'policyStatuses' => GovernancePolicy::STATUSES,
            'meetingStatuses' => GovernanceMeeting::STATUSES,
            'actionStatuses' => GovernanceAction::STATUSES,
            'actionPriorities' => GovernanceAction::PRIORITIES,
        ]);
    }

    public function storeComplaint(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validateComplaint($request);

        $complaint = DB::transaction(fn (): GovernanceComplaint => GovernanceComplaint::create($attributes + [
            'reference' => $this->nextReference('CMP', GovernanceComplaint::class),
        ]));

        $auditLogger->log('governance.complaint_opened', [
            'auditable' => $complaint,
            'event' => 'Complaint',
            'new_values' => $complaint->only(['reference', 'status', 'severity', 'category']),
            'metadata' => ['summary' => $complaint->summary],
        ]);

        return redirect()->route('governance.index')->with('status', 'Complaint opened and audit evidence recorded.');
    }

    public function updateComplaint(Request $request, GovernanceComplaint $complaint, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validateComplaint($request, updating: true);
        $oldStatus = $complaint->status;
        $attributes['closed_at'] = in_array($attributes['status'], ['resolved', 'closed'], true)
            ? ($complaint->closed_at ?: now())
            : null;

        $complaint->update($attributes);

        $auditLogger->log('governance.complaint_updated', [
            'auditable' => $complaint,
            'event' => 'Complaint',
            'old_values' => ['status' => $oldStatus],
            'new_values' => $complaint->only(['status', 'outcome', 'closed_at']),
        ]);

        if ($complaint->wasChanged('closed_at')) {
            $auditLogger->log('governance.complaint_closed', [
                'auditable' => $complaint,
                'event' => 'Complaint',
                'new_values' => ['outcome' => $complaint->outcome, 'closed_at' => $complaint->closed_at],
            ]);
        }

        return redirect()->route('governance.index')->with('status', 'Complaint updated.');
    }

    public function storeGdprCase(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validateGdprCase($request);

        $case = DB::transaction(fn (): GdprCase => GdprCase::create($attributes + [
            'reference' => $this->nextReference('GDP', GdprCase::class),
        ]));

        $auditLogger->log('governance.gdpr_case_opened', [
            'auditable' => $case,
            'event' => 'GDPRCase',
            'new_values' => $case->only(['reference', 'status', 'request_type', 'risk_level']),
            'metadata' => ['summary' => $case->summary],
        ]);

        return redirect()->route('governance.index')->with('status', 'GDPR case opened and audit evidence recorded.');
    }

    public function updateGdprCase(Request $request, GdprCase $gdprCase, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validateGdprCase($request, updating: true);
        $oldStatus = $gdprCase->status;
        $attributes['closed_at'] = in_array($attributes['status'], ['responded', 'closed'], true)
            ? ($gdprCase->closed_at ?: now())
            : null;

        $gdprCase->update($attributes);

        $auditLogger->log('governance.gdpr_case_updated', [
            'auditable' => $gdprCase,
            'event' => 'GDPRCase',
            'old_values' => ['status' => $oldStatus],
            'new_values' => $gdprCase->only(['status', 'outcome', 'closed_at']),
        ]);

        if ($gdprCase->wasChanged('closed_at')) {
            $auditLogger->log('governance.gdpr_case_closed', [
                'auditable' => $gdprCase,
                'event' => 'GDPRCase',
                'new_values' => ['outcome' => $gdprCase->outcome, 'closed_at' => $gdprCase->closed_at],
            ]);
        }

        return redirect()->route('governance.index')->with('status', 'GDPR case updated.');
    }

    public function storeAction(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validateAction($request);

        $action = GovernanceAction::create($attributes);

        $auditLogger->log('governance.action_created', [
            'auditable' => $action,
            'event' => 'GovernanceAction',
            'new_values' => $action->only(['title', 'status', 'priority', 'due_at']),
        ]);

        return redirect()->route('governance.index')->with('status', 'Governance action created.');
    }

    public function updateAction(Request $request, GovernanceAction $action, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validateAction($request, updating: true);
        $oldStatus = $action->status;
        $attributes['completed_at'] = $attributes['status'] === 'completed'
            ? ($action->completed_at ?: now())
            : null;

        $action->update($attributes);

        $auditLogger->log($action->status === 'completed' ? 'governance.action_completed' : 'governance.action_updated', [
            'auditable' => $action,
            'event' => 'GovernanceAction',
            'old_values' => ['status' => $oldStatus],
            'new_values' => $action->only(['status', 'outcome', 'completed_at']),
        ]);

        return redirect()->route('governance.index')->with('status', 'Governance action updated.');
    }

    public function storePolicy(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validatePolicy($request);

        $policy = GovernancePolicy::create($attributes + [
            'reference' => $this->nextReference('POL', GovernancePolicy::class),
        ]);

        $auditLogger->log('governance.policy_created', [
            'auditable' => $policy,
            'event' => 'Policy',
            'new_values' => $policy->only(['reference', 'title', 'status', 'version']),
        ]);

        return redirect()->route('governance.index')->with('status', 'Policy added to the governance library.');
    }

    public function updatePolicy(Request $request, GovernancePolicy $policy, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validatePolicy($request, updating: true);
        $oldStatus = $policy->status;
        $attributes['approved_at'] = $attributes['status'] === 'active'
            ? ($policy->approved_at ?: now())
            : ($attributes['status'] === 'draft' ? null : $policy->approved_at);
        $attributes['retired_at'] = $attributes['status'] === 'retired'
            ? ($policy->retired_at ?: now())
            : null;

        $policy->update($attributes);

        $auditLogger->log('governance.policy_updated', [
            'auditable' => $policy,
            'event' => 'Policy',
            'old_values' => ['status' => $oldStatus],
            'new_values' => $policy->only(['status', 'version', 'review_due_at', 'approved_at', 'retired_at']),
        ]);

        return redirect()->route('governance.index')->with('status', 'Policy updated.');
    }

    public function storeMeeting(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validateMeeting($request);

        $meeting = GovernanceMeeting::create($attributes + [
            'reference' => $this->nextReference('MTG', GovernanceMeeting::class),
        ]);

        $auditLogger->log('governance.meeting_scheduled', [
            'auditable' => $meeting,
            'event' => 'GovernanceMeeting',
            'new_values' => $meeting->only(['reference', 'meeting_type', 'status', 'scheduled_at']),
        ]);

        return redirect()->route('governance.index')->with('status', 'Governance meeting scheduled.');
    }

    public function updateMeeting(Request $request, GovernanceMeeting $meeting, AuditLogger $auditLogger): RedirectResponse
    {
        $attributes = $this->validateMeeting($request, updating: true);
        $oldStatus = $meeting->status;

        $meeting->update($attributes);

        $auditLogger->log($meeting->status === 'completed' ? 'governance.meeting_completed' : 'governance.meeting_updated', [
            'auditable' => $meeting,
            'event' => 'GovernanceMeeting',
            'old_values' => ['status' => $oldStatus],
            'new_values' => $meeting->only(['status', 'minutes', 'outcome']),
        ]);

        return redirect()->route('governance.index')->with('status', 'Governance meeting updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateComplaint(Request $request, bool $updating = false): array
    {
        $rules = [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'complainant_name' => ['required', 'string', 'max:255'],
            'complainant_contact' => ['nullable', 'string', 'max:255'],
            'source' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:100'],
            'severity' => ['required', Rule::in(array_keys(GovernanceComplaint::SEVERITIES))],
            'status' => ['required', Rule::in(array_keys(GovernanceComplaint::STATUSES))],
            'summary' => ['required', 'string', 'max:5000'],
            'outcome' => ['nullable', 'string', 'max:5000'],
            'received_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date'],
        ];

        $attributes = $request->validate($rules);

        if (! $updating) {
            $attributes['status'] = 'open';
        }

        if (in_array($attributes['status'], ['resolved', 'closed'], true) && blank($attributes['outcome'] ?? null)) {
            throw ValidationException::withMessages([
                'outcome' => 'Record outcome evidence before closing the complaint.',
            ]);
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGdprCase(Request $request, bool $updating = false): array
    {
        $attributes = $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_contact' => ['nullable', 'string', 'max:255'],
            'request_type' => ['required', Rule::in(array_keys(GdprCase::REQUEST_TYPES))],
            'risk_level' => ['required', Rule::in(array_keys(GdprCase::RISK_LEVELS))],
            'status' => ['required', Rule::in(array_keys(GdprCase::STATUSES))],
            'summary' => ['required', 'string', 'max:5000'],
            'outcome' => ['nullable', 'string', 'max:5000'],
            'received_at' => ['required', 'date'],
            'response_due_at' => ['nullable', 'date'],
        ]);

        if (! $updating) {
            $attributes['status'] = 'open';
        }

        if (in_array($attributes['status'], ['responded', 'closed'], true) && blank($attributes['outcome'] ?? null)) {
            throw ValidationException::withMessages([
                'outcome' => 'Record outcome evidence before closing the GDPR case.',
            ]);
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAction(Request $request, bool $updating = false): array
    {
        $attributes = $request->validate([
            'governance_complaint_id' => ['nullable', 'integer', 'exists:governance_complaints,id'],
            'gdpr_case_id' => ['nullable', 'integer', 'exists:gdpr_cases,id'],
            'governance_policy_id' => ['nullable', 'integer', 'exists:governance_policies,id'],
            'governance_meeting_id' => ['nullable', 'integer', 'exists:governance_meetings,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_keys(GovernanceAction::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(GovernanceAction::STATUSES))],
            'due_at' => ['nullable', 'date'],
            'outcome' => ['nullable', 'string', 'max:5000'],
        ]);

        if (
            blank($attributes['governance_complaint_id'] ?? null)
            && blank($attributes['gdpr_case_id'] ?? null)
            && blank($attributes['governance_policy_id'] ?? null)
            && blank($attributes['governance_meeting_id'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'governance_complaint_id' => 'Link the action to a governance record.',
            ]);
        }

        if (! $updating) {
            $attributes['status'] = 'open';
        }

        if ($attributes['status'] === 'completed' && blank($attributes['outcome'] ?? null)) {
            throw ValidationException::withMessages([
                'outcome' => 'Record outcome evidence before completing the action.',
            ]);
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePolicy(Request $request, bool $updating = false): array
    {
        $attributes = $request->validate([
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'version' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(array_keys(GovernancePolicy::STATUSES))],
            'summary' => ['required', 'string', 'max:5000'],
            'review_due_at' => ['nullable', 'date'],
        ]);

        if (! $updating) {
            $attributes['status'] = 'draft';
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMeeting(Request $request, bool $updating = false): array
    {
        $attributes = $request->validate([
            'chair_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'meeting_type' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(array_keys(GovernanceMeeting::STATUSES))],
            'scheduled_at' => ['required', 'date'],
            'attendees' => ['nullable', 'string', 'max:5000'],
            'agenda' => ['required', 'string', 'max:5000'],
            'minutes' => ['nullable', 'string', 'max:5000'],
            'outcome' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! $updating) {
            $attributes['status'] = 'scheduled';
        }

        if ($attributes['status'] === 'completed' && (blank($attributes['minutes'] ?? null) || blank($attributes['outcome'] ?? null))) {
            throw ValidationException::withMessages([
                'outcome' => 'Record minutes and outcome evidence before completing the meeting.',
            ]);
        }

        return $attributes;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     */
    private function nextReference(string $prefix, string $modelClass): string
    {
        $nextId = ((int) $modelClass::query()->max('id')) + 1;

        return $prefix.'-'.now()->format('Ymd').'-'.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT);
    }
}
