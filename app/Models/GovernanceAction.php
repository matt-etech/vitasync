<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'governance_complaint_id',
    'gdpr_case_id',
    'governance_policy_id',
    'governance_meeting_id',
    'owner_user_id',
    'title',
    'description',
    'priority',
    'status',
    'due_at',
    'completed_at',
    'outcome',
])]
class GovernanceAction extends Model
{
    use Auditable;

    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    /**
     * @return BelongsTo<GovernanceComplaint, $this>
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(GovernanceComplaint::class, 'governance_complaint_id');
    }

    /**
     * @return BelongsTo<GdprCase, $this>
     */
    public function gdprCase(): BelongsTo
    {
        return $this->belongsTo(GdprCase::class);
    }

    /**
     * @return BelongsTo<GovernancePolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(GovernancePolicy::class, 'governance_policy_id');
    }

    /**
     * @return BelongsTo<GovernanceMeeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(GovernanceMeeting::class, 'governance_meeting_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function parentLabel(): string
    {
        if ($this->complaint) {
            return 'Complaint '.$this->complaint->reference;
        }

        if ($this->gdprCase) {
            return 'GDPR '.$this->gdprCase->reference;
        }

        if ($this->policy) {
            return 'Policy '.$this->policy->reference;
        }

        if ($this->meeting) {
            return 'Meeting '.$this->meeting->reference;
        }

        return 'Standalone governance action';
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'completed_at' => 'datetime',
        ];
    }
}
