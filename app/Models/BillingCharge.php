<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'billing_profile_id',
    'billing_contract_id',
    'billing_invoice_id',
    'staff_user_id',
    'approved_by_user_id',
    'charge_type',
    'category',
    'description',
    'charge_date',
    'amount',
    'is_credit',
    'approval_status',
    'approved_at',
    'generated_for_period_start',
    'generated_for_period_end',
])]
class BillingCharge extends Model
{
    use Auditable;

    public const CHARGE_TYPES = [
        'recurring' => 'Recurring',
        'variable' => 'Variable',
        'late_fee' => 'Late fee',
        'discount' => 'Discount',
        'credit' => 'Credit',
    ];

    public const CATEGORIES = [
        'accommodation' => 'Accommodation fee',
        'nursing' => 'Nursing fee',
        'laundry' => 'Laundry fee',
        'meals' => 'Meal package',
        'medication_administration' => 'Medication administration',
        'care_assistance' => 'Care assistance package',
        'transport' => 'Transport',
        'doctor_consultation' => 'Doctor consultation',
        'ambulance_transport' => 'Ambulance transport',
        'medication_purchase' => 'Medication purchase',
        'hair_salon' => 'Hair salon service',
        'personal_shopping' => 'Personal shopping',
        'special_meals' => 'Special meals',
        'physiotherapy' => 'Physiotherapy',
        'other' => 'Other',
    ];

    public const APPROVAL_STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class, 'billing_profile_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BillingContract::class, 'billing_contract_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'charge_date' => 'date',
            'amount' => 'decimal:2',
            'is_credit' => 'boolean',
            'approved_at' => 'datetime',
            'generated_for_period_start' => 'date',
            'generated_for_period_end' => 'date',
        ];
    }
}
