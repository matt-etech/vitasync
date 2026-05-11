<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Visit;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class VisitTimeMonitor
{
    private const REMINDER_ACTION = 'visit.reminder_15_minute_sent';

    private const MISSED_ACTION = 'admin.alert.visit_missed';

    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @return array{reminders_sent: int, missed_marked: int}
     */
    public function process(?DateTimeInterface $now = null): array
    {
        $clock = $now === null ? now() : Carbon::parse($now);

        return [
            'reminders_sent' => $this->sendUpcomingVisitReminders($clock),
            'missed_marked' => $this->markExpiredScheduledVisitsAsMissed($clock),
        ];
    }

    private function sendUpcomingVisitReminders(Carbon $now): int
    {
        $windowEnd = $now->copy()->addMinutes(15);
        $sent = 0;

        Visit::query()
            ->with(['client', 'assignedWorker', 'home'])
            ->where('status', 'scheduled')
            ->whereNull('check_in_at')
            ->whereBetween('scheduled_start_at', [$now, $windowEnd])
            ->orderBy('scheduled_start_at')
            ->get()
            ->each(function (Visit $visit) use ($now, &$sent): void {
                if ($this->auditExists($visit, self::REMINDER_ACTION)) {
                    return;
                }

                $this->auditLogger->log(self::REMINDER_ACTION, [
                    'auditable' => $visit,
                    'event' => 'NotificationEvent',
                    'metadata' => [
                        'alert_targets' => ['carer', 'admin'],
                        'severity' => 'warning',
                        'message' => $this->visitReminderMessage($visit),
                        'client_id' => $visit->client_id,
                        'client_name' => $this->clientName($visit),
                        'carer_id' => $visit->assigned_user_id,
                        'carer_name' => $visit->assignedWorker?->name,
                        'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
                        'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
                        'minutes_until_start' => max(0, (int) floor($now->diffInMinutes($visit->scheduled_start_at, false))),
                    ],
                ]);

                $sent++;
            });

        return $sent;
    }

    private function markExpiredScheduledVisitsAsMissed(Carbon $now): int
    {
        $marked = 0;

        Visit::query()
            ->with(['client', 'assignedWorker', 'home'])
            ->where('status', 'scheduled')
            ->whereNull('check_in_at')
            ->where('scheduled_end_at', '<', $now)
            ->orderBy('scheduled_end_at')
            ->get()
            ->each(function (Visit $visit) use (&$marked): void {
                $oldStatus = $visit->status;
                $visit->forceFill(['status' => 'missed'])->save();

                if (! $this->auditExists($visit, self::MISSED_ACTION)) {
                    $this->auditLogger->log(self::MISSED_ACTION, [
                        'auditable' => $visit,
                        'event' => 'NotificationEvent',
                        'old_values' => ['status' => $oldStatus],
                        'new_values' => ['status' => 'missed'],
                        'metadata' => [
                            'alert_targets' => ['admin'],
                            'severity' => 'critical',
                            'message' => $this->missedVisitMessage($visit),
                            'client_id' => $visit->client_id,
                            'client_name' => $this->clientName($visit),
                            'carer_id' => $visit->assigned_user_id,
                            'carer_name' => $visit->assignedWorker?->name,
                            'scheduled_start_at' => $visit->scheduled_start_at?->toIso8601String(),
                            'scheduled_end_at' => $visit->scheduled_end_at?->toIso8601String(),
                        ],
                    ]);
                }

                $marked++;
            });

        return $marked;
    }

    private function auditExists(Visit $visit, string $action): bool
    {
        if (! Schema::hasTable('audit_logs')) {
            return false;
        }

        return AuditLog::query()
            ->where('action', $action)
            ->where('auditable_type', $visit->getMorphClass())
            ->where('auditable_id', $visit->getKey())
            ->exists();
    }

    private function visitReminderMessage(Visit $visit): string
    {
        $client = $this->clientName($visit);
        $time = $visit->scheduled_start_at?->format('d/m/Y H:i') ?? 'the scheduled time';

        return "Visit for {$client} starts at {$time}.";
    }

    private function missedVisitMessage(Visit $visit): string
    {
        $client = $this->clientName($visit);
        $time = $visit->scheduled_end_at?->format('d/m/Y H:i') ?? 'the scheduled end time';

        return "Visit for {$client} was automatically marked missed after {$time}.";
    }

    private function clientName(Visit $visit): string
    {
        return $visit->client?->fullName() ?: 'the client';
    }
}
