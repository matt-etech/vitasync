<?php

namespace App\Services\Billing;

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\BillingReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentAllocationService
{
    public function __construct(
        private readonly BillingNumberGenerator $numberGenerator,
        private readonly BillingStatementService $statementService,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array{payment: BillingPayment, receipt: BillingReceipt}
     */
    public function record(BillingInvoice $invoice, array $attributes): array
    {
        return DB::transaction(function () use ($invoice, $attributes): array {
            $invoice->refresh();

            if (in_array($invoice->status, ['paid', 'void'], true)) {
                throw ValidationException::withMessages([
                    'billing_invoice_id' => 'Payments can only be recorded against open invoices.',
                ]);
            }

            $amount = round((float) $attributes['amount'], 2);

            if ($amount <= 0 || $amount > (float) $invoice->balance_due) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment must be greater than zero and cannot exceed the invoice balance.',
                ]);
            }

            $payment = BillingPayment::create([
                'billing_profile_id' => $invoice->billing_profile_id,
                'billing_invoice_id' => $invoice->id,
                'received_by_user_id' => $attributes['received_by_user_id'] ?? null,
                'payment_number' => $this->numberGenerator->paymentNumber(),
                'payment_date' => $attributes['payment_date'],
                'amount' => $amount,
                'method' => $attributes['method'],
                'reference' => $attributes['reference'] ?? null,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $paidAmount = round((float) $invoice->paid_amount + $amount, 2);
            $balanceDue = round((float) $invoice->total_amount - $paidAmount, 2);
            $invoice->update([
                'paid_amount' => $paidAmount,
                'balance_due' => max(0, $balanceDue),
                'status' => $balanceDue <= 0 ? 'paid' : 'partially_paid',
                'locked_at' => $invoice->locked_at ?: now(),
            ]);

            $receipt = BillingReceipt::create([
                'billing_payment_id' => $payment->id,
                'receipt_number' => $this->numberGenerator->receiptNumber(),
                'issued_at' => now(),
                'amount' => $amount,
                'currency' => $invoice->currency,
                'payer_name' => $attributes['payer_name'] ?? null,
            ]);

            $profile = $invoice->profile()->firstOrFail();
            $this->statementService->credit($profile, 'Payment '.$payment->payment_number, $amount, $payment);
            $this->statementService->credit($profile, 'Receipt '.$receipt->receipt_number, 0.0, $receipt, 'receipt');

            return ['payment' => $payment->load('receipt'), 'receipt' => $receipt];
        });
    }
}
