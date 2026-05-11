<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarerIssueReportRequest;
use App\Models\User;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class CarerIssueReportController extends Controller
{
    public function __invoke(CarerIssueReportRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $carer = User::query()->with('roles')->findOrFail($request->integer('carer_id'));

        if (! $carer->is_active || ! $carer->roles->contains(fn ($role): bool => $role->name === 'Carer' && $role->is_active)) {
            abort(403, 'This endpoint is only available to active carers.');
        }

        $validated = $request->validated();
        $reportedAt = isset($validated['reported_at']) ? Carbon::parse($validated['reported_at']) : now();
        $visit = isset($validated['visit_id']) ? Visit::with('client')->findOrFail($validated['visit_id']) : null;

        if ($visit && (int) $visit->assigned_user_id !== (int) $carer->id) {
            abort(403, 'This visit is not assigned to the carer.');
        }

        $auditLogger->log('admin.alert.carer_issue_report', [
            'actor_id' => $carer->id,
            'auditable' => $visit,
            'event' => 'NotificationEvent',
            'metadata' => [
                'alert_target' => 'admin',
                'visit_id' => $visit?->id,
                'client_id' => $visit?->client_id,
                'client_name' => $visit?->client?->fullName(),
                'carer_id' => $carer->id,
                'carer_name' => $carer->name,
                'category' => $validated['category'],
                'severity' => strtolower($validated['severity']),
                'notes' => $validated['notes'],
                'reported_at' => $reportedAt->format(DATE_ATOM),
            ],
        ]);

        return response()->json([
            'status' => 'queued',
            'sync_status' => 'synced',
            'reported_at' => $reportedAt->format('d/m/Y H:i'),
        ]);
    }
}
