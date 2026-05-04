<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'carer_profile_id',
    'training_key',
    'training_name',
    'status',
    'expiry_date',
    'certificate_path',
])]
class CarerTrainingRecord extends Model
{
    use Auditable;

    public const MANDATORY_TRAINING = [
        'manual_handling' => 'Manual Handling',
        'infection_control' => 'Infection Control',
        'medication_handling' => 'Medication Handling',
        'first_aid' => 'First Aid',
        'safeguarding_adults' => 'Safeguarding Adults',
    ];

    public const STATUSES = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'expired' => 'Expired',
    ];

    /**
     * @return BelongsTo<CarerProfile, $this>
     */
    public function carerProfile(): BelongsTo
    {
        return $this->belongsTo(CarerProfile::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? str($this->status)->replace('_', ' ')->headline()->toString();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }
}
