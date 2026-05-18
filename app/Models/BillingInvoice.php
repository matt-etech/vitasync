<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'billing_profile_id',
    'billing_contract_id',
    'invoice_number',
    'period_start',
    'period_end',
    'issue_date',
    'due_date',
    'currency',
    'subtotal',
    'discount_total',
    'credit_total',
    'tax_total',
    'total_amount',
    'paid_amount',
    'balance_due',
    'status',
    'sent_at',
    'locked_at',
])]
class BillingInvoice extends Model
{
    use Auditable;

    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'partially_paid' => 'Partially paid',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'void' => 'Void',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class, 'billing_profile_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BillingContract::class, 'billing_contract_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingInvoiceItem::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(BillingCharge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null || in_array($this->status, ['partially_paid', 'paid', 'void'], true);
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'credit_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'sent_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }
}
