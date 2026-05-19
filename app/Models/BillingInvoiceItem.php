<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'billing_invoice_id',
    'billing_charge_id',
    'item_type',
    'description',
    'quantity',
    'unit_amount',
    'line_subtotal',
    'tax_amount',
    'line_total',
    'sort_order',
])]
class BillingInvoiceItem extends Model
{
    use Auditable;

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(BillingCharge::class, 'billing_charge_id');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_amount' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }
}
