<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'visit_id',
    'client_id',
    'carer_id',
    'care_plan_id',
    'medication_name',
    'dose',
    'route',
    'outcome',
    'notes',
    'administered_at',
])]
class MedicationAdministration extends Model
{
    use Auditable;

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function carer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'carer_id');
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }

    protected function casts(): array
    {
        return [
            'administered_at' => 'datetime',
        ];
    }
}
