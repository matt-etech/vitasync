<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'client_id',
    'admission_date',
    'room_bed',
    'billing_contact_name',
    'billing_contact_relationship',
    'billing_contact_email',
    'billing_contact_phone',
    'funding_source',
    'payment_terms',
    'currency',
    'tax_rate',
    'tax_exempt',
    'status',
])]
class BillingProfile extends Model
{
    use Auditable;

    public const FUNDING_SOURCES = [
        'private_self_pay' => 'Private self-pay',
        'family_sponsor' => 'Family sponsor',
        'insurance' => 'Insurance',
        'government_social_welfare' => 'Government/social welfare',
        'ngo_church_support' => 'NGO/church support',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'closed' => 'Closed',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(BillingContract::class)->where('status', 'active')->latestOfMany();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(BillingContract::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(BillingCharge::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class);
    }

    public function statementEntries(): HasMany
    {
        return $this->hasMany(BillingStatementEntry::class);
    }

    protected function casts(): array
    {
        return [
            'admission_date' => 'date',
            'tax_rate' => 'decimal:2',
            'tax_exempt' => 'boolean',
        ];
    }
}
