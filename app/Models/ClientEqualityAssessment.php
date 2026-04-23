<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_assessment_id',
    'gender',
    'ethnicity',
    'religion',
    'disability_status',
    'sexual_orientation',
    'cultural_needs',
    'reasonable_adjustments',
    'notes',
])]
class ClientEqualityAssessment extends Model
{
    /**
     * @return BelongsTo<ClientAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ClientAssessment::class, 'client_assessment_id');
    }
}
