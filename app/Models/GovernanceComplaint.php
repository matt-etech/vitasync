<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference',
    'client_id',
    'owner_user_id',
    'complainant_name',
    'complainant_contact',
    'source',
    'category',
    'severity',
    'status',
    'summary',
    'outcome',
    'received_at',
    'due_at',
    'closed_at',
])]
class GovernanceComplaint extends Model
{
    use Auditable;

    public const STATUSES = [
        'open' => 'Open',
        'investigating' => 'Investigating',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public const SEVERITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

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

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true);
    }

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'due_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }
}
