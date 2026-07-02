<?php

namespace App\Models;

use App\Support\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'home_id',
    'client_id',
    'recorded_by',
    'witness_user_id',
    'transaction_type',
    'occurred_at',
    'drug_name',
    'form',
    'strength',
    'unit',
    'stock_key',
    'quantity',
    'signed_quantity',
    'expected_balance_before',
    'expected_balance_after',
    'actual_balance_after',
    'discrepancy_amount',
    'discrepancy_reason',
    'reason',
    'source_or_destination',
    'batch_number',
    'expiry_date',
    'witness_required',
    'witness_name',
    'notes',
    'submitted_at',
])]
class ControlledDrugRegisterEntry extends Model
{
    use Auditable;

    public const TRANSACTION_TYPES = [
        'received' => 'Received into stock',
        'administered' => 'Administered',
        'wasted' => 'Wastage',
        'disposed' => 'Disposal',
        'returned' => 'Returned to pharmacy',
    ];

    public const STOCK_IN_TYPES = ['received'];

    public const STOCK_OUT_TYPES = ['administered', 'wasted', 'disposed', 'returned'];

    public const WITNESS_REQUIRED_TYPES = ['administered', 'wasted', 'disposed', 'returned'];

    public const OTHER_VALUE = '__other';

    public const DRUG_OPTIONS = [
        'Morphine Sulfate' => 'Morphine Sulfate',
        'Oxycodone' => 'Oxycodone',
        'Fentanyl' => 'Fentanyl',
        'Buprenorphine' => 'Buprenorphine',
        'Diazepam' => 'Diazepam',
        'Midazolam' => 'Midazolam',
        'Lorazepam' => 'Lorazepam',
        'Temazepam' => 'Temazepam',
        'Methylphenidate' => 'Methylphenidate',
        'Tramadol' => 'Tramadol',
    ];

    public const FORM_OPTIONS = [
        'Tablet' => 'Tablet',
        'Capsule' => 'Capsule',
        'Liquid' => 'Liquid',
        'Injection' => 'Injection',
        'Patch' => 'Patch',
        'Sublingual tablet' => 'Sublingual tablet',
        'Buccal tablet' => 'Buccal tablet',
        'Nasal spray' => 'Nasal spray',
    ];

    public const UNIT_OPTIONS = [
        'tablets' => 'tablets',
        'capsules' => 'capsules',
        'ml' => 'ml',
        'ampoules' => 'ampoules',
        'patches' => 'patches',
        'units' => 'units',
    ];

    public const SOURCE_DESTINATION_OPTIONS = [
        'Main pharmacy' => 'Main pharmacy',
        'Emergency pharmacy' => 'Emergency pharmacy',
        'Returned to pharmacy' => 'Returned to pharmacy',
        'Destroyed in home' => 'Destroyed in home',
        'Transferred from previous home' => 'Transferred from previous home',
        'Prescriber supplied stock' => 'Prescriber supplied stock',
    ];

    public const REASON_OPTIONS = [
        'Prescribed regular dose' => 'Prescribed regular dose',
        'Prescribed PRN dose' => 'Prescribed PRN dose',
        'End of treatment return' => 'End of treatment return',
        'Expired stock disposal' => 'Expired stock disposal',
        'Contaminated or damaged stock' => 'Contaminated or damaged stock',
        'Dropped or spoiled dose' => 'Dropped or spoiled dose',
        'Service user discharged' => 'Service user discharged',
        'Medication discontinued' => 'Medication discontinued',
    ];

    public const NEGATIVE_REASON_OPTIONS = [
        'Expired stock disposal',
        'Contaminated or damaged stock',
        'Dropped or spoiled dose',
        'Medication discontinued',
    ];

    public const DISCREPANCY_REASON_OPTIONS = [
        'Stock count correction' => 'Stock count correction',
        'Previous entry error identified' => 'Previous entry error identified',
        'Spillage witnessed and recorded' => 'Spillage witnessed and recorded',
        'Damaged dose witnessed and recorded' => 'Damaged dose witnessed and recorded',
        'Pharmacy quantity differs from label' => 'Pharmacy quantity differs from label',
        'Under investigation' => 'Under investigation',
    ];

    public static function signedQuantityFor(string $transactionType, float $quantity): float
    {
        return in_array($transactionType, self::STOCK_IN_TYPES, true) ? $quantity : -$quantity;
    }

    public static function stockKeyFor(string $drugName, ?string $strength, ?string $form, string $unit): string
    {
        return str(implode('|', [
            mb_strtolower(trim($drugName)),
            mb_strtolower(trim((string) $strength)),
            mb_strtolower(trim((string) $form)),
            mb_strtolower(trim($unit)),
        ]))->limit(191, '')->toString();
    }

    public static function activatesDiscrepancy(?string $reason): bool
    {
        return filled($reason) && in_array($reason, self::NEGATIVE_REASON_OPTIONS, true);
    }

    public function transactionTypeLabel(): string
    {
        return self::TRANSACTION_TYPES[$this->transaction_type] ?? str($this->transaction_type)->headline()->toString();
    }

    public function stockKey(): string
    {
        return self::stockKeyFor($this->drug_name, $this->strength, $this->form, $this->unit);
    }

    public function hasDiscrepancy(): bool
    {
        return (float) $this->discrepancy_amount !== 0.0;
    }

    public function home(): BelongsTo
    {
        return $this->belongsTo(Home::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function witness(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witness_user_id');
    }

    protected static function booted(): void
    {
        static::updating(function (self $entry): void {
            if ($entry->submitted_at !== null) {
                throw new LogicException('Submitted controlled drug register entries cannot be edited.');
            }
        });

        static::deleting(function (self $entry): void {
            if ($entry->submitted_at !== null) {
                throw new LogicException('Submitted controlled drug register entries cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'quantity' => 'decimal:2',
            'signed_quantity' => 'decimal:2',
            'expected_balance_before' => 'decimal:2',
            'expected_balance_after' => 'decimal:2',
            'actual_balance_after' => 'decimal:2',
            'discrepancy_amount' => 'decimal:2',
            'expiry_date' => 'date',
            'witness_required' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }
}
