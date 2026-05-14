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
    'requester_name',
    'requester_contact',
    'request_type',
    'risk_level',
    'status',
    'summary',
    'outcome',
    'received_at',
    'response_due_at',
    'closed_at',
])]
class GdprCase extends Model
{
    use Auditable;

    public const REQUEST_TYPES = [
        'sar' => 'Subject access request',
        'correction' => 'Correction request',
        'deletion' => 'Deletion request',
        'breach' => 'Data breach',
        'dpia' => 'DPIA review',
    ];

    public const RISK_LEVELS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'reviewing' => 'Reviewing',
        'reported' => 'Reported',
        'responded' => 'Responded',
        'closed' => 'Closed',
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
        return in_array($this->status, ['responded', 'closed'], true);
    }

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'response_due_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }
}
