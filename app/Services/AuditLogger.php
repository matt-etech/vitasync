<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditLogger
{
    /**
     * @param array<string, mixed> $oldValues
     * @param array<string, mixed> $newValues
     * @param array<string, mixed> $metadata
     */
    public function logModelEvent(Model $model, string $action, array $oldValues = [], array $newValues = [], array $metadata = []): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $this->log($action, [
            'auditable' => $model,
            'event' => class_basename($model),
            'old_values' => $this->sanitizeModelValues($model, $oldValues),
            'new_values' => $this->sanitizeModelValues($model, $newValues),
            'metadata' => $metadata,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function log(string $action, array $payload = []): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        /** @var Request|null $request */
        $request = app()->bound('request') ? app('request') : null;
        /** @var Model|null $auditable */
        $auditable = $payload['auditable'] ?? null;

        $metadata = $this->sanitizePlainValues($payload['metadata'] ?? []);
        $friendlyContext = $this->friendlyContext($action, $payload, $auditable, $request);

        AuditLog::create([
            'actor_id' => $payload['actor_id'] ?? Auth::id(),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'action' => $action,
            'event' => $payload['event'] ?? ($auditable ? class_basename($auditable) : null),
            'route_name' => $payload['route_name'] ?? $request?->route()?->getName(),
            'method' => $payload['method'] ?? $request?->method(),
            'url' => $payload['url'] ?? $request?->fullUrl(),
            'ip_address' => $payload['ip_address'] ?? $request?->ip(),
            'user_agent' => $payload['user_agent'] ?? $request?->userAgent(),
            'old_values' => $this->sanitizePlainValues($payload['old_values'] ?? []),
            'new_values' => $this->sanitizePlainValues($payload['new_values'] ?? []),
            'metadata' => $friendlyContext + $metadata,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function friendlyContext(string $action, array $payload, ?Model $auditable, ?Request $request): array
    {
        $subject = $payload['friendly_subject']
            ?? $payload['event']
            ?? ($auditable ? class_basename($auditable) : null)
            ?? $request?->route()?->getName()
            ?? 'system';

        $actionLabel = $payload['friendly_action'] ?? $this->friendlyLabel($action);
        $subjectLabel = $payload['friendly_subject'] ?? $this->friendlyLabel((string) $subject);
        $actorLabel = $payload['friendly_actor']
            ?? Auth::user()?->name
            ?? ($payload['actor_id'] ?? null ? 'User #'.$payload['actor_id'] : 'System');

        return [
            'Plain English action' => $actionLabel,
            'Plain English subject' => $subjectLabel,
            'Plain English summary' => $payload['friendly_summary'] ?? "{$actorLabel} {$actionLabel} {$subjectLabel}.",
        ];
    }

    private function friendlyLabel(string $value): string
    {
        return Str::of($value)
            ->replace(['.', '_', '-'], ' ')
            ->replaceMatches('/(?<!^)([A-Z])/', ' $1')
            ->squish()
            ->title()
            ->toString();
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function sanitizeModelValues(Model $model, array $values): array
    {
        $blocked = array_flip(array_merge($model->getHidden(), ['password', 'remember_token']));

        return $this->sanitizePlainValues(Arr::except($values, array_keys($blocked)));
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function sanitizePlainValues(array $values): array
    {
        return collect($values)
            ->map(function (mixed $value): mixed {
                if ($value instanceof \DateTimeInterface) {
                    return $value->format(DATE_ATOM);
                }

                if (is_bool($value) || is_int($value) || is_float($value) || is_null($value) || is_string($value)) {
                    return $value;
                }

                if ($value instanceof \BackedEnum) {
                    return $value->value;
                }

                if (is_array($value)) {
                    return $this->sanitizePlainValues($value);
                }

                return (string) $value;
            })
            ->all();
    }
}
