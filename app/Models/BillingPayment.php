<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'billing_profile_id',
    'billing_invoice_id',
    'received_by_user_id',
    'payment_number',
    'payment_date',
    'amount',
    'method',
    'reference',
    'notes',
])]
class BillingPayment extends Model
{
    use Auditable;

    public const METHODS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'card' => 'Card',
        'cheque' => 'Cheque',
        'insurance' => 'Insurance remittance',
        'government' => 'Government/social welfare remittance',
        'other' => 'Other',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class, 'billing_profile_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(BillingReceipt::class);
    }

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
