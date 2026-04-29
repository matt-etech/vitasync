<?php

namespace App\Support;

use App\Services\AuditLogger;

trait Auditable
{
    protected array $auditOriginalValues = [];

    protected array $auditDirtyValues = [];

    public static function bootAuditable(): void
    {
        static::creating(function ($model): void {
            $model->auditDirtyValues = $model->getAttributes();
        });

        static::updating(function ($model): void {
            $dirtyKeys = array_keys($model->getDirty());

            $model->auditDirtyValues = $model->getDirty();
            $model->auditOriginalValues = array_intersect_key($model->getOriginal(), array_flip($dirtyKeys));
        });

        static::deleting(function ($model): void {
            $model->auditOriginalValues = $model->getAttributes();
        });

        static::created(function ($model): void {
            app(AuditLogger::class)->logModelEvent($model, 'created', [], $model->auditDirtyValues ?: $model->getAttributes());
        });

        static::updated(function ($model): void {
            $newValues = array_intersect_key($model->getAttributes(), $model->auditOriginalValues);

            app(AuditLogger::class)->logModelEvent($model, 'updated', $model->auditOriginalValues, $newValues);
        });

        static::deleted(function ($model): void {
            app(AuditLogger::class)->logModelEvent($model, 'deleted', $model->auditOriginalValues ?: $model->getAttributes(), []);
        });
    }
}
