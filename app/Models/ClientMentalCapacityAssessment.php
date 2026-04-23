<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_assessment_id',
    'decision_type',
    'understands_information',
    'retains_information',
    'weighs_information',
    'communicates_decision',
    'capacity_outcome',
    'best_interest_decision',
    'imca_involved',
    'dols_lps_status',
    'notes',
])]
class ClientMentalCapacityAssessment extends Model
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
            'understands_information' => 'boolean',
            'retains_information' => 'boolean',
            'weighs_information' => 'boolean',
            'communicates_decision' => 'boolean',
            'imca_involved' => 'boolean',
        ];
    }
}
