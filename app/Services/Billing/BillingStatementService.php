<?php

namespace App\Services\Billing;

use App\Models\BillingProfile;
use App\Models\BillingStatementEntry;
use Illuminate\Database\Eloquent\Model;

class BillingStatementService
{
    public function debit(BillingProfile $profile, string $description, float $amount, Model $source): BillingStatementEntry
    {
        return $this->append($profile, 'invoice', $description, $amount, 0.0, $source);
    }

    public function credit(BillingProfile $profile, string $description, float $amount, Model $source, string $type = 'payment'): BillingStatementEntry
    {
        return $this->append($profile, $type, $description, 0.0, $amount, $source);
    }

    private function append(BillingProfile $profile, string $type, string $description, float $debit, float $credit, Model $source): BillingStatementEntry
    {
        $lastBalance = (float) ($profile->statementEntries()->latest('id')->value('running_balance') ?? 0);

        return $profile->statementEntries()->create([
            'entry_date' => now()->toDateString(),
            'entry_type' => $type,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => round($lastBalance + $debit - $credit, 2),
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
        ]);
    }
}
