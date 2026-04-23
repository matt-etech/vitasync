<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_assessment_id',
    'preferred_language',
    'communication_method',
    'hearing_impairment',
    'vision_impairment',
    'speech_difficulty',
    'interpreter_required',
    'communication_aids',
    'notes',
])]
class ClientCommunicationAssessment extends Model
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
            'hearing_impairment' => 'boolean',
            'vision_impairment' => 'boolean',
            'speech_difficulty' => 'boolean',
            'interpreter_required' => 'boolean',
        ];
    }
}
