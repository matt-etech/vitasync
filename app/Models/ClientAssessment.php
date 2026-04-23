<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'client_id',
    'version',
    'assessment_date',
    'assessor_name',
    'assessment_type',
    'overall_summary',
    'overall_risk_level',
    'recommendations',
    'next_review_date',
    'status',
    'submitted_at',
    'reviewed_at',
    'reviewed_by',
    'review_notes',
])]
class ClientAssessment extends Model
{
    /** @use HasFactory<\Database\Factories\ClientAssessmentFactory> */
    use HasFactory;

    public const STATUS_ONBOARDING = 'onboarding';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasOne<ClientNeedAssessment, $this>
     */
    public function needs(): HasOne
    {
        return $this->hasOne(ClientNeedAssessment::class);
    }

    /**
     * @return HasOne<ClientFunctionalAssessment, $this>
     */
    public function functional(): HasOne
    {
        return $this->hasOne(ClientFunctionalAssessment::class);
    }

    /**
     * @return HasOne<ClientMedicalAssessment, $this>
     */
    public function medical(): HasOne
    {
        return $this->hasOne(ClientMedicalAssessment::class);
    }

    /**
     * @return HasOne<ClientMentalCapacityAssessment, $this>
     */
    public function mentalCapacity(): HasOne
    {
        return $this->hasOne(ClientMentalCapacityAssessment::class);
    }

    /**
     * @return HasOne<ClientRiskAssessment, $this>
     */
    public function risk(): HasOne
    {
        return $this->hasOne(ClientRiskAssessment::class);
    }

    /**
     * @return HasOne<ClientCommunicationAssessment, $this>
     */
    public function communication(): HasOne
    {
        return $this->hasOne(ClientCommunicationAssessment::class);
    }

    /**
     * @return HasOne<ClientEqualityAssessment, $this>
     */
    public function equality(): HasOne
    {
        return $this->hasOne(ClientEqualityAssessment::class);
    }

    /**
     * @return HasOne<ClientSocialAssessment, $this>
     */
    public function social(): HasOne
    {
        return $this->hasOne(ClientSocialAssessment::class);
    }

    /**
     * @return HasOne<ClientEnvironmentalAssessment, $this>
     */
    public function environmental(): HasOne
    {
        return $this->hasOne(ClientEnvironmentalAssessment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'assessment_date' => 'date',
            'next_review_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
