<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'billing_profile_id',
    'entry_date',
    'entry_type',
    'description',
    'debit',
    'credit',
    'running_balance',
    'source_type',
    'source_id',
])]
class BillingStatementEntry extends Model
{
    public function profile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class, 'billing_profile_id');
    }

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'running_balance' => 'decimal:2',
        ];
    }
}
