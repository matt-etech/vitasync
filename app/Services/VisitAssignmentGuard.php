<?php

namespace App\Services;

use App\Models\CarerProfile;
use App\Models\CarerTrainingRecord;
use App\Models\User;
use App\Models\Visit;
use Carbon\CarbonInterface;

class VisitAssignmentGuard
{
    /**
     * @return list<string>
     */
    public function violations(
        User $worker,
        int $homeId,
        CarbonInterface $scheduledStartAt,
        CarbonInterface $scheduledEndAt,
        ?int $ignoreVisitId = null
    ): array {
        $worker->loadMissing(['carerProfile.trainingRecords']);

        $violations = [];
        $profile = $worker->carerProfile;

        if (! $worker->is_active) {
            $violations[] = 'Assigned worker account must be active.';
        }

        if (! $profile) {
            return [...$violations, 'Assigned worker must have an approved carer profile.'];
        }

        $violations = [
            ...$violations,
            ...$this->complianceViolations($profile, $homeId),
            ...$this->availabilityViolations($worker, $profile, $scheduledStartAt, $scheduledEndAt, $ignoreVisitId),
        ];

        return array_values(array_unique($violations));
    }

    /**
     * @return list<string>
     */
    private function complianceViolations(CarerProfile $profile, int $homeId): array
    {
        $violations = [];

        if ($profile->status !== CarerProfile::STATUS_APPROVED) {
            $violations[] = 'Assigned worker onboarding must be approved.';
        }

        if ($profile->account_status !== 'active') {
            $violations[] = 'Assigned worker account status must be active.';
        }

        if ((int) $profile->assigned_home_id !== $homeId) {
            $violations[] = 'Assigned worker must belong to the client home.';
        }

        $violations = [...$violations, ...$profile->criticalValidationFailures()];

        if ($profile->dbs_expiry_date?->isPast()) {
            $violations[] = 'DBS certificate must not be expired.';
        }

        $trainingRecords = $profile->trainingRecords->keyBy('training_key');

        foreach (CarerTrainingRecord::MANDATORY_TRAINING as $trainingKey => $trainingName) {
            $record = $trainingRecords->get($trainingKey);

            if ($record?->expiry_date?->isPast()) {
                $violations[] = "{$trainingName} training must not be expired.";
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    private function availabilityViolations(
        User $worker,
        CarerProfile $profile,
        CarbonInterface $scheduledStartAt,
        CarbonInterface $scheduledEndAt,
        ?int $ignoreVisitId
    ): array {
        $violations = [];

        if (blank($profile->availability_pattern) || blank($profile->max_weekly_hours) || blank($profile->shift_preference)) {
            $violations[] = 'Assigned worker must have availability recorded before scheduling.';
        }

        if (! $this->matchesShiftPreference($profile->shift_preference, $scheduledStartAt, $scheduledEndAt)) {
            $violations[] = 'Assigned worker is not available for this shift time.';
        }

        if ($this->hasOverlappingVisit($worker, $scheduledStartAt, $scheduledEndAt, $ignoreVisitId)) {
            $violations[] = 'Assigned worker already has a visit during this time.';
        }

        if ($profile->max_weekly_hours && $this->exceedsWeeklyHours($worker, $profile, $scheduledStartAt, $scheduledEndAt, $ignoreVisitId)) {
            $violations[] = 'Assigned worker would exceed their recorded weekly availability.';
        }

        return $violations;
    }

    private function matchesShiftPreference(?string $shiftPreference, CarbonInterface $scheduledStartAt, CarbonInterface $scheduledEndAt): bool
    {
        if ($shiftPreference === null || $shiftPreference === 'both') {
            return true;
        }

        $isNightShift = $scheduledStartAt->hour < 8 || $scheduledEndAt->hour > 20 || ! $scheduledStartAt->isSameDay($scheduledEndAt);

        return $shiftPreference === 'night' ? $isNightShift : ! $isNightShift;
    }

    private function hasOverlappingVisit(
        User $worker,
        CarbonInterface $scheduledStartAt,
        CarbonInterface $scheduledEndAt,
        ?int $ignoreVisitId
    ): bool {
        return $worker->assignedVisits()
            ->when($ignoreVisitId, fn ($query) => $query->whereKeyNot($ignoreVisitId))
            ->whereNotIn('status', ['cancelled', 'missed'])
            ->where('scheduled_start_at', '<', $scheduledEndAt)
            ->where('scheduled_end_at', '>', $scheduledStartAt)
            ->exists();
    }

    private function exceedsWeeklyHours(
        User $worker,
        CarerProfile $profile,
        CarbonInterface $scheduledStartAt,
        CarbonInterface $scheduledEndAt,
        ?int $ignoreVisitId
    ): bool {
        $weekStart = $scheduledStartAt->copy()->startOfWeek();
        $weekEnd = $scheduledStartAt->copy()->endOfWeek();
        $newVisitHours = $scheduledStartAt->diffInSeconds($scheduledEndAt) / 3600;

        $scheduledHours = $worker->assignedVisits()
            ->when($ignoreVisitId, fn ($query) => $query->whereKeyNot($ignoreVisitId))
            ->whereNotIn('status', ['cancelled', 'missed'])
            ->whereBetween('scheduled_start_at', [$weekStart, $weekEnd])
            ->get(['scheduled_start_at', 'scheduled_end_at'])
            ->sum(fn (Visit $visit): float => $visit->scheduled_start_at->diffInSeconds($visit->scheduled_end_at) / 3600);

        return ($scheduledHours + $newVisitHours) > $profile->max_weekly_hours;
    }
}
