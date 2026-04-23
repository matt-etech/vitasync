<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_assessment_id',
    'living_arrangements',
    'family_support',
    'social_isolation_risk',
    'community_engagement',
    'employment_status',
    'financial_concerns',
    'notes',
])]
class ClientSocialAssessment extends Model
{
    /**
     * @return BelongsTo<ClientAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ClientAssessment::class, 'client_assessment_id');
    }
}
