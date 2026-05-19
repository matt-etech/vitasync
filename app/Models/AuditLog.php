<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[Fillable([
    'actor_id',
    'auditable_type',
    'auditable_id',
    'action',
    'event',
    'route_name',
    'method',
    'url',
    'ip_address',
    'user_agent',
    'old_values',
    'new_values',
    'metadata',
])]
class AuditLog extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabel(): string
    {
        return $this->metadata['Plain English action'] ?? $this->friendlyLabel($this->action);
    }

    public function subjectLabel(): string
    {
        return $this->metadata['Plain English subject'] ?? $this->friendlyLabel($this->event ?? 'System');
    }

    public function summary(): string
    {
        return $this->metadata['Plain English summary']
            ?? trim(($this->actor?->name ?? 'System').' '.$this->actionLabel().' '.$this->subjectLabel().'.');
    }

    /**
     * @return array<string, mixed>
     */
    public function readableOldValues(): array
    {
        return $this->readableValues($this->old_values ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function readableNewValues(): array
    {
        return $this->readableValues($this->new_values ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function readableMetadata(): array
    {
        return collect($this->metadata ?? [])
            ->reject(fn (mixed $value, string $key): bool => str_starts_with($key, 'Plain English'))
            ->pipe(fn ($values) => $this->readableValues($values->all()));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function readableValues(array $values): array
    {
        return collect($values)
            ->mapWithKeys(fn (mixed $value, string|int $key): array => [$this->friendlyLabel((string) $key) => $this->readableValue($value)])
            ->all();
    }

    private function readableValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_is_list($value)
                ? collect($value)->map(fn (mixed $item): mixed => $this->readableValue($item))->all()
                : $this->readableValues($value);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value === null || $value === '') {
            return 'Not provided';
        }

        return $value;
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
}
