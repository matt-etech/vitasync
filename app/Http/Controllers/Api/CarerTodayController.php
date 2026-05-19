<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CarerClientsRequest;
use App\Http\Requests\Api\CarerVisitEvidenceRequest;
use App\Http\Requests\Api\CarerVisitActionRequest;
use App\Http\Requests\Api\CarerVisitLocationEventRequest;
use App\Http\Requests\Api\CarerVisitNotesRequest;
use App\Http\Requests\Api\CarerVisitTaskRecordRequest;
use App\Http\Requests\Api\CarerVisitVitalsRequest;
use App\Models\CarePlan;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitEvidenceRecord;
use App\Models\VisitTaskRecord;
use App\Models\VisitVitalRecord;
use App\Services\AuditLogger;
use App\Services\VisitTimeMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class CarerTodayController extends Controller
{
    public function show(CarerClientsRequest $request, VisitTimeMonitor $visitTimeMonitor): JsonResponse
    {
        $carer = $this->activeCarer($request->integer('carer_id'));
        $visitTimeMonitor->process();

        $visit = Visit::query()
            ->with(['client', 'carePlan.client'])
            ->where('assigned_user_id', $carer->id)
            ->whereIn('status', ['scheduled', 'in_progress', 'missed'])
            ->whereDate('scheduled_start_at', now()->toDateString())
            ->orderBy('scheduled_start_at')
            ->first();

        return response()->json([
            'visit' => $visit ? $this->visitPayload($visit) : null,
        ]);
    }

    public function checkIn(CarerVisitActionRequest $request, Visit $visit, AuditLogger $auditLogger): JsonResponse
    {
        $carer = $this->activeCarer($request->integer('carer_id'));
        $this->authorizeVisit($visit, $carer);

        $visit->update([
            'status' => 'in_progress',
            'check_in_at' => $visit->check_in_at ?? now(),
        ]);

        $visit = $visit->fresh(['client', 'carePlan.client']);

        $auditLogger->log('visit.checked_in', [
            'actor_id' => $carer->id,
            'auditable' => $visit,
            'event' => 'Visit check-in',
            'friendly_action' => 'Checked in for',
            'friendly_subject' => $visit->client->fullName().' visit',
            'friendly_actor' => $carer->name,
            'friendly_summary' => "{$carer->name} checked in for {$visit->client->fullName()}'s visit.",
            'new_values' => [
                'status' => $visit->status,
                'check_in_at' => $visit->check_in_at,
            ],
            'metadata' => $this->visitAuditMetadata($visit, $carer),
        ]);

        return response()->json(['visit' => $this->visitPayload($visit)]);
    }

    public function locationEvent(CarerVisitLocationEventRequest $request, Visit $visit, AuditLogger $auditLogger): JsonResponse
    {
        $carer = $this->activeCarer($request->integer('carer_id'));
        $this->authorizeVisit($visit, $carer);

        $validated = $request->validated();
        $eventType = $validated['event_type'];
        $recordedAt = isset($validated['recorded_at']) ? Carbon::parse($validated['recorded_at']) : now();

        if ($eventType === 'arrived') {
            $visit->update([
                'status' => 'in_progress',
                'check_in_at' => $visit->check_in_at ?? $recordedAt,
            ]);
        }

        if ($eventType === 'departed') {
            $visit->update([
                'status' => 'completed',
                'check_out_at' => $visit->check_out_at ?? $recordedAt,
            ]);
        }

        $visit = $visit->fresh(['client', 'carePlan.client']);
        $metadata = $this->locationMetadata($visit, $validated, $recordedAt);

        $auditLogger->log($eventType === 'arrived' ? 'visit.location_arrived' : 'visit.location_departed', [
            'actor_id' => $carer->id,
            'auditable' => $visit,
            'event' => 'EVVEvent',
            'new_values' => [
                'status' => $visit->status,
                'check_in_at' => $visit->check_in_at,
                'check_out_at' => $visit->check_out_at,
            ],
            'metadata' => $metadata,
        ]);

        if ($eventType === 'departed') {
            $auditLogger->log('admin.alert.visit_departure', [
                'actor_id' => $carer->id,
                'auditable' => $visit,
                'event' => 'NotificationEvent',
                'metadata' => $metadata + [
                    'alert_target' => 'admin',
                    'severity' => 'info',
                    'message' => "{$carer->name} left {$visit->client->fullName()}'s home.",
                ],
            ]);
        }

        return response()->json(['visit' => $this->visitPayload($visit)]);
    }

    public function checkOut(CarerVisitActionRequest $request, Visit $visit, AuditLogger $auditLogger): JsonResponse
    {
        $carer = $this->activeCarer($request->integer('carer_id'));
        $this->authorizeVisit($visit, $carer);

        $visit->update([
            'status' => 'completed',
            'check_out_at' => $visit->check_out_at ?? now(),
        ]);

        $visit = $visit->fresh(['client', 'carePlan.client']);

        $auditLogger->log('visit.checked_out', [
            'actor_id' => $carer->id,
            'auditable' => $visit,
            'event' => 'Visit check-out',
            'friendly_action' => 'Checked out from',
            'friendly_subject' => $visit->client->fullName().' visit',
            'friendly_actor' => $carer->name,
            'friendly_summary' => "{$carer->name} checked out from {$visit->client->fullName()}'s visit.",
            'new_values' => [
                'status' => $visit->status,
                'check_out_at' => $visit->check_out_at,
            ],
            'metadata' => $this->visitAuditMetadata($visit, $carer),
        ]);

        return response()->json(['visit' => $this->visitPayload($visit)]);
    }

    public function recordNotes(CarerVisitNotesRequest $request, Visit $visit, AuditLogger $auditLogger): JsonResponse
    {
        $carer = $this->activeCarer($request->integer('carer_id'));
        $this->authorizeVisit($visit, $carer);

        $validated = $request->validated();
        $recordedAt = isset($validated['recorded_at']) ? Carbon::parse($validated['recorded_at']) : now();

        $visit->update([
            'notes' => $validated['notes'],
        ]);

        $visit = $visit->fresh(['client', 'carePlan.client']);

        $auditLogger->log('visit.notes_recorded', [
            'actor_id' => $carer->id,
            'auditable' => $visit,
            'event' => 'VisitNote',
            'new_values' => [
                'notes' => $visit->notes,
            ],
            'metadata' => [
                'visit_id' => $visit->id,
                'client_id' => $visit->client_id,
                'client_name' => $visit->client->fullName(),
                'recorded_at' => $recordedAt->format(DATE_ATOM),
            ],
        ]);

        return response()->json(['visit' => $this->visitPayload($visit)]);
    }

    public function recordTask(CarerVisitTaskRecordRequest $request, Visit $visit, AuditLogger $auditLogger): JsonResponse
    {
        $carer = $this->activeCarer($request->integer('carer_id'));
        $this->authorizeVisit($visit, $carer);

        $validated = $request->validated();
        $completedAt = isset($validated['completed_at']) ? Carbon::parse($validated['completed_at']) : now();

        $record = VisitTaskRecord::create([
            'visit_id' => $visit->id,
            'client_id' => $visit->client_id,
            'carer_id' => $carer->id,
            'task_key' => $validated['task_key'],
            'title' => $validated['title'],
            'detail' => $validated['detail'] ?? null,
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? $completedAt : null,
        ]);

        $visit = $visit->fresh(['client', 'carePlan.client']);

        $auditLogger->log($record->status === 'completed' ? 'visit.task_completed' : 'visit.task_reopened', [
            'actor_id' => $carer->id,
            'auditable' => $visit,
            'event' => 'VisitTask',
            'new_values' => [
                'task_key' => $record->task_key,
                'title' => $record->title,
                'status' => $record->status,
                'completed_at' => $record->completed_at,
            ],
            'metadata' => $this->visitAuditMetadata($visit, $carer) + [
                'visit_task_record_id' => $record->id,
                'detail' => $record->detail,
            ],
        ]);

        return response()->json([
            'status' => 'recorded',
            'task_record_id' => $record->id,
            'visit' => $this->visitPayload($visit),
        ]);
    }

    public function recordVitals(CarerVisitVitalsRequest $request, Visit $visit, AuditLogger $auditLogger): JsonResponse
    {
        $carer = $this->activeCarer($request->integer('carer_id'));
        $this->authorizeVisit($visit, $carer);

        $validated = $request->validated();
        $recordedAt = isset($validated['recorded_at']) ? Carbon::parse($validated['recorded_at']) : now();

        $record = VisitVitalRecord::create([
            'visit_id' => $visit->id,
            'client_id' => $visit->client_id,
            'carer_id' => $carer->id,
            'bp_systolic' => $validated['bp_systolic'],
            'bp_diastolic' => $validated['bp_diastolic'],
            'pulse' => $validated['pulse'],
            'temperature' => $validated['temperature'],
            'blood_oxygen' => $validated['blood_oxygen'],
            'recorded_at' => $recordedAt,
        ]);

        $visit = $visit->fresh(['client', 'carePlan.client']);

        $auditLogger->log('visit.vitals_recorded', [
            'actor_id' => $carer->id,
            'auditable' => $visit,
            'event' => 'VisitVitals',
            'new_values' => [
                'bp_systolic' => $record->bp_systolic,
                'bp_diastolic' => $record->bp_diastolic,
                'pulse' => $record->pulse,
                'temperature' => $record->temperature,
                'blood_oxygen' => $record->blood_oxygen,
                'recorded_at' => $record->recorded_at,
            ],
            'metadata' => $this->visitAuditMetadata($visit, $carer) + [
                'visit_vital_record_id' => $record->id,
            ],
        ]);

        return response()->json([
            'status' => 'recorded',
            'vital_record_id' => $record->id,
            'visit' => $this->visitPayload($visit),
        ]);
    }

    public function recordEvidence(CarerVisitEvidenceRequest $request, Visit $visit, AuditLogger $auditLogger): JsonResponse
    {
        $carer = $this->activeCarer($request->integer('carer_id'));
        $this->authorizeVisit($visit, $carer);

        $validated = $request->validated();
        $capturedAt = isset($validated['captured_at']) ? Carbon::parse($validated['captured_at']) : now();

        $record = VisitEvidenceRecord::create([
            'visit_id' => $visit->id,
            'client_id' => $visit->client_id,
            'carer_id' => $carer->id,
            'evidence_type' => $validated['evidence_type'],
            'label' => $validated['label'],
            'file_name' => $validated['file_name'] ?? null,
            'metadata' => $validated['metadata'] ?? [],
            'captured_at' => $capturedAt,
        ]);

        $visit = $visit->fresh(['client', 'carePlan.client']);

        $auditLogger->log('visit.evidence_recorded', [
            'actor_id' => $carer->id,
            'auditable' => $visit,
            'event' => 'VisitEvidence',
            'new_values' => [
                'evidence_type' => $record->evidence_type,
                'label' => $record->label,
                'file_name' => $record->file_name,
                'captured_at' => $record->captured_at,
            ],
            'metadata' => $this->visitAuditMetadata($visit, $carer) + [
                'visit_evidence_record_id' => $record->id,
                'evidence_metadata' => $record->metadata,
            ],
        ]);

        return response()->json([
            'status' => 'recorded',
            'evidence_record_id' => $record->id,
            'visit' => $this->visitPayload($visit),
        ]);
    }

    private function activeCarer(int $carerId): User
    {
        $carer = User::query()->with('roles')->findOrFail($carerId);

        if (! $carer->is_active || ! $carer->roles->contains(fn ($role): bool => $role->name === 'Carer' && $role->is_active)) {
            abort(403, 'This endpoint is only available to active carers.');
        }

        return $carer;
    }

    private function authorizeVisit(Visit $visit, User $carer): void
    {
        if ((int) $visit->assigned_user_id !== (int) $carer->id) {
            abort(403, 'This visit is not assigned to the carer.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function visitPayload(Visit $visit): array
    {
        return [
            'id' => $visit->id,
            'client_name' => $visit->client->fullName(),
            'address' => $visit->client->address,
            'client_latitude' => $visit->client->getAttribute('latitude'),
            'client_longitude' => $visit->client->getAttribute('longitude'),
            'geofence_radius_meters' => $visit->client->getAttribute('geofence_radius_meters'),
            'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
            'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
            'time_window' => $visit->scheduled_start_at->format('H:i').' - '.$visit->scheduled_end_at->format('H:i'),
            'status' => $visit->status,
            'check_in_time' => $visit->check_in_at?->format('H:i'),
            'check_out_time' => $visit->check_out_at?->format('H:i'),
            'notes' => $visit->notes,
            'tasks' => $visit->carePlan ? $this->tasksForCarePlan($visit->carePlan)->values() : [],
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function locationMetadata(Visit $visit, array $validated, \DateTimeInterface $recordedAt): array
    {
        return [
            'visit_id' => $visit->id,
            'client_id' => $visit->client_id,
            'client_name' => $visit->client->fullName(),
            'event_type' => $validated['event_type'],
            'recorded_at' => $recordedAt->format(DATE_ATOM),
            'location' => [
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'accuracy_meters' => isset($validated['accuracy_meters']) ? (float) $validated['accuracy_meters'] : null,
                'distance_meters' => isset($validated['distance_meters']) ? (float) $validated['distance_meters'] : null,
                'geofence_radius_meters' => isset($validated['geofence_radius_meters'])
                    ? (int) $validated['geofence_radius_meters']
                    : $visit->client->geofence_radius_meters,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function visitAuditMetadata(Visit $visit, User $carer): array
    {
        return [
            'visit_id' => $visit->id,
            'client_id' => $visit->client_id,
            'client_name' => $visit->client->fullName(),
            'carer_id' => $carer->id,
            'carer_name' => $carer->name,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function tasksForCarePlan(CarePlan $carePlan): Collection
    {
        return collect([
            $this->taskPayload($carePlan, 'personal_care', 'Personal care', $carePlan->personal_care_level, $carePlan->personal_care_support),
            $this->taskPayload($carePlan, 'mobility', 'Mobility', $carePlan->mobility_level, $carePlan->mobility_support),
            $this->taskPayload($carePlan, 'nutrition', 'Nutrition and hydration', $carePlan->nutrition_support_level, $carePlan->nutrition_hydration_support),
            $this->taskPayload($carePlan, 'medication', 'Medication', $carePlan->medication_support_level, $carePlan->medication_support),
            $this->taskPayload($carePlan, 'communication', 'Communication', $carePlan->communication_support_level, $carePlan->communication_support),
            $this->taskPayload($carePlan, 'risk', 'Risk management', $carePlan->risk_level, $carePlan->risk_management),
        ])->filter();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function taskPayload(CarePlan $carePlan, string $sectionKey, string $section, ?string $level, ?string $instructions): ?array
    {
        if (blank($instructions) && blank($level)) {
            return null;
        }

        return [
            'id' => "{$carePlan->id}:{$sectionKey}",
            'client_name' => $carePlan->client->fullName(),
            'care_plan_title' => $carePlan->title,
            'section' => $section,
            'title' => $level ?: $section,
            'instructions' => $instructions ?: 'Follow the current care plan instructions.',
            'risk_level' => $carePlan->risk_level,
            'status' => 'pending',
        ];
    }
}
