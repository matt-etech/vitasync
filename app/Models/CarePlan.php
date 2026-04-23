<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'home_id',
    'client_id',
    'title',
    'plan_type',
    'care_level',
    'visit_frequency',
    'review_frequency',
    'start_date',
    'review_date',
    'care_goals',
    'personal_care_level',
    'personal_care_support',
    'mobility_level',
    'mobility_support',
    'nutrition_support_level',
    'nutrition_hydration_support',
    'medication_support_level',
    'medication_support',
    'communication_support_level',
    'communication_support',
    'risk_level',
    'risk_management',
    'preferences_routines',
    'escalation_instructions',
    'review_notes',
    'status',
])]
class CarePlan extends Model
{
    use HasFactory;

    /**
     * @return BelongsTo<Home, $this>
     */
    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<Visit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'review_date' => 'date',
        ];
    }
}
