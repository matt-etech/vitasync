<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'billing_payment_id',
    'receipt_number',
    'issued_at',
    'amount',
    'currency',
    'payer_name',
])]
class BillingReceipt extends Model
{
    use Auditable;

    public function payment(): BelongsTo
    {
        return $this->belongsTo(BillingPayment::class, 'billing_payment_id');
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }
}
