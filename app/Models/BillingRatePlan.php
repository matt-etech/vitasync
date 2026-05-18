<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'description',
    'currency',
    'room_fee',
    'care_fee',
    'meals_included',
    'billing_cycle',
    'due_day',
    'deposit_amount',
    'notice_period_days',
    'late_fee_type',
    'late_fee_amount',
    'discount_type',
    'discount_amount',
    'status',
])]
class BillingRatePlan extends Model
{
    use Auditable;

    public const BILLING_CYCLES = [
        'monthly' => 'Monthly',
        'weekly' => 'Weekly',
        'four_weekly' => 'Four-weekly',
    ];

    public const LATE_FEE_TYPES = [
        'none' => 'None',
        'fixed' => 'Fixed amount',
        'percentage' => 'Percentage',
    ];

    public const DISCOUNT_TYPES = [
        'fixed' => 'Fixed amount',
        'percentage' => 'Percentage',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(BillingContract::class);
    }

    protected function casts(): array
    {
        return [
            'room_fee' => 'decimal:2',
            'care_fee' => 'decimal:2',
            'meals_included' => 'boolean',
            'due_day' => 'integer',
            'deposit_amount' => 'decimal:2',
            'notice_period_days' => 'integer',
            'late_fee_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }
}
