<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference',
    'chair_user_id',
    'meeting_type',
    'status',
    'scheduled_at',
    'attendees',
    'agenda',
    'minutes',
    'outcome',
])]
class GovernanceMeeting extends Model
{
    use Auditable;

    public const STATUSES = [
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function chair(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chair_user_id');
    }

    /**
     * @return HasMany<GovernanceAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(GovernanceAction::class);
    }

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }
}
