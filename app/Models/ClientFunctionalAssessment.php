<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_assessment_id',
    'mobility_status',
    'bathing_ability',
    'dressing_ability',
    'eating_ability',
    'toileting_ability',
    'transferring_ability',
    'continence_status',
    'independence_level',
    'notes',
])]
class ClientFunctionalAssessment extends Model
{
    /**
     * @return BelongsTo<ClientAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ClientAssessment::class, 'client_assessment_id');
    }
}
