<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_assessment_id',
    'falls_risk',
    'pressure_ulcer_risk',
    'manual_handling_risk',
    'environmental_risk',
    'behaviour_risk',
    'safeguarding_risk',
    'control_measures',
    'notes',
])]
class ClientRiskAssessment extends Model
{
    /**
     * @return BelongsTo<ClientAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ClientAssessment::class, 'client_assessment_id');
    }
}
