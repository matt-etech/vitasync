<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_assessment_id',
    'diagnoses',
    'medical_conditions',
    'medications',
    'allergies',
    'vital_signs',
    'gp_details',
    'medication_support_needed',
    'notes',
])]
class ClientMedicalAssessment extends Model
{
    /**
     * @return BelongsTo<ClientAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ClientAssessment::class, 'client_assessment_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'medication_support_needed' => 'boolean',
        ];
    }
}
