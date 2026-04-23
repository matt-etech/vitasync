<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'home_id',
    'first_name',
    'last_name',
    'date_of_birth',
    'gender',
    'phone',
    'email',
    'address',
    'emergency_contact_name',
    'emergency_contact_phone',
    'status',
    'onboarding_status',
    'submitted_at',
    'reviewed_at',
    'reviewed_by',
    'review_notes',
])]
class Client extends Model
{
    public const ONBOARDING_STATUS_ONBOARDING = 'onboarding';
    public const ONBOARDING_STATUS_PENDING = 'pending';
    public const ONBOARDING_STATUS_APPROVED = 'approved';
    public const ONBOARDING_STATUS_DECLINED = 'declined';

    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Home, $this>
     */
    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasOne<ClientAssessment, $this>
     */
    public function assessment(): HasOne
    {
        return $this->hasOne(ClientAssessment::class)->latestOfMany();
    }

    /**
     * @return HasMany<ClientAssessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(ClientAssessment::class);
    }

    /**
     * @return HasMany<CarePlan, $this>
     */
    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class);
    }

    /**
     * @return HasMany<Visit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
