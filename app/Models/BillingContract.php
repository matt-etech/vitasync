<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'billing_profile_id',
    'billing_rate_plan_id',
    'start_date',
    'end_date',
    'billing_cycle',
    'due_day',
    'deposit_amount',
    'notice_period_days',
    'late_fee_type',
    'late_fee_amount',
    'care_level_pricing',
    'discount_type',
    'discount_amount',
    'status',
])]
class BillingContract extends Model
{
    use Auditable;

    public const STATUSES = [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'ended' => 'Ended',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class, 'billing_profile_id');
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(BillingRatePlan::class, 'billing_rate_plan_id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(BillingCharge::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'due_day' => 'integer',
            'deposit_amount' => 'decimal:2',
            'notice_period_days' => 'integer',
            'late_fee_amount' => 'decimal:2',
            'care_level_pricing' => 'array',
            'discount_amount' => 'decimal:2',
        ];
    }
}
