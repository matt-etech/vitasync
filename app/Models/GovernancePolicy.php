<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference',
    'owner_user_id',
    'title',
    'category',
    'version',
    'status',
    'summary',
    'review_due_at',
    'approved_at',
    'retired_at',
])]
class GovernancePolicy extends Model
{
    use Auditable;

    public const STATUSES = [
        'draft' => 'Draft',
        'active' => 'Active',
        'review_due' => 'Review due',
        'retired' => 'Retired',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
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
            'review_due_at' => 'date',
            'approved_at' => 'datetime',
            'retired_at' => 'datetime',
        ];
    }
}
