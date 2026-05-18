<?php

namespace App\Services\Billing;

use App\Models\BillingCharge;
use App\Models\BillingContract;
use App\Models\BillingInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceGenerationService
{
    public function __construct(
        private readonly BillingNumberGenerator $numberGenerator,
        private readonly BillingStatementService $statementService,
    ) {
    }

    public function generate(BillingContract $contract, Carbon $periodStart, Carbon $periodEnd): BillingInvoice
    {
        return DB::transaction(function () use ($contract, $periodStart, $periodEnd): BillingInvoice {
            $contract->loadMissing(['profile.client', 'ratePlan']);
            $profile = $contract->profile;
            $ratePlan = $contract->ratePlan;

            $charges = $profile->charges()
                ->where(function ($query) use ($contract): void {
                    $query->whereNull('billing_contract_id')
                        ->orWhere('billing_contract_id', $contract->id);
                })
                ->whereNull('billing_invoice_id')
                ->where('approval_status', 'approved')
                ->whereBetween('charge_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->orderBy('charge_date')
                ->lockForUpdate()
                ->get();

            $items = collect();

            if ((float) $ratePlan->room_fee > 0) {
                $items->push([
                    'type' => 'recurring',
                    'description' => 'Room fee',
                    'amount' => (float) $ratePlan->room_fee,
                    'charge' => null,
                    'is_credit' => false,
                ]);
            }

            if ((float) $ratePlan->care_fee > 0) {
                $items->push([
                    'type' => 'recurring',
                    'description' => 'Care fee',
                    'amount' => (float) $ratePlan->care_fee,
                    'charge' => null,
                    'is_credit' => false,
                ]);
            }

            foreach ($charges as $charge) {
                $items->push([
                    'type' => $charge->charge_type,
                    'description' => $charge->description,
                    'amount' => (float) $charge->amount,
                    'charge' => $charge,
                    'is_credit' => (bool) $charge->is_credit || in_array($charge->charge_type, ['discount', 'credit'], true),
                ]);
            }

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'billing_contract_id' => 'No approved charges or recurring rate plan items are available for this invoice period.',
                ]);
            }

            $subtotal = round((float) $items->where('is_credit', false)->sum('amount'), 2);
            $creditTotal = round((float) $items->where('is_credit', true)->sum('amount'), 2);
            $discountTotal = $this->discountFor($contract, $subtotal);
            $taxableAmount = max(0, $subtotal - $discountTotal - $creditTotal);
            $taxTotal = $profile->tax_exempt ? 0.0 : round($taxableAmount * ((float) $profile->tax_rate / 100), 2);
            $total = round($taxableAmount + $taxTotal, 2);
            $issueDate = now()->toDateString();
            $dueDate = Carbon::parse($issueDate)->day(min((int) $contract->due_day, 28));

            if ($dueDate->isPast()) {
                $dueDate->addMonthNoOverflow();
            }

            $invoice = BillingInvoice::create([
                'billing_profile_id' => $profile->id,
                'billing_contract_id' => $contract->id,
                'invoice_number' => $this->numberGenerator->invoiceNumber(),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'issue_date' => $issueDate,
                'due_date' => $dueDate->toDateString(),
                'currency' => $profile->currency,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'credit_total' => $creditTotal,
                'tax_total' => $taxTotal,
                'total_amount' => $total,
                'paid_amount' => 0,
                'balance_due' => $total,
                'status' => 'sent',
                'sent_at' => now(),
                'locked_at' => now(),
            ]);

            $sortOrder = 1;
            foreach ($items as $item) {
                $signedAmount = $item['is_credit'] ? -1 * (float) $item['amount'] : (float) $item['amount'];
                $invoice->items()->create([
                    'billing_charge_id' => $item['charge']?->id,
                    'item_type' => $item['type'],
                    'description' => $item['description'],
                    'quantity' => 1,
                    'unit_amount' => $signedAmount,
                    'line_subtotal' => $signedAmount,
                    'tax_amount' => 0,
                    'line_total' => $signedAmount,
                    'sort_order' => $sortOrder++,
                ]);
            }

            if ($discountTotal > 0) {
                $invoice->items()->create([
                    'item_type' => 'discount',
                    'description' => 'Contract discount',
                    'quantity' => 1,
                    'unit_amount' => -1 * $discountTotal,
                    'line_subtotal' => -1 * $discountTotal,
                    'tax_amount' => 0,
                    'line_total' => -1 * $discountTotal,
                    'sort_order' => $sortOrder++,
                ]);
            }

            if ($taxTotal > 0) {
                $invoice->items()->create([
                    'item_type' => 'tax',
                    'description' => 'Tax/VAT',
                    'quantity' => 1,
                    'unit_amount' => $taxTotal,
                    'line_subtotal' => $taxTotal,
                    'tax_amount' => $taxTotal,
                    'line_total' => $taxTotal,
                    'sort_order' => $sortOrder,
                ]);
            }

            $charges->each->update(['billing_invoice_id' => $invoice->id]);
            $this->statementService->debit($profile, 'Invoice '.$invoice->invoice_number, $total, $invoice);

            return $invoice->load(['items', 'profile.client']);
        });
    }

    private function discountFor(BillingContract $contract, float $subtotal): float
    {
        if ((float) $contract->discount_amount <= 0 || blank($contract->discount_type)) {
            return 0.0;
        }

        if ($contract->discount_type === 'percentage') {
            return round($subtotal * ((float) $contract->discount_amount / 100), 2);
        }

        return min(round((float) $contract->discount_amount, 2), $subtotal);
    }
}
