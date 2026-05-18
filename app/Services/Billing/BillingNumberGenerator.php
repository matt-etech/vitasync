<?php

namespace App\Services\Billing;

use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\BillingReceipt;

class BillingNumberGenerator
{
    public function invoiceNumber(): string
    {
        return $this->next('INV', BillingInvoice::class, 'invoice_number');
    }

    public function paymentNumber(): string
    {
        return $this->next('PAY', BillingPayment::class, 'payment_number');
    }

    public function receiptNumber(): string
    {
        return $this->next('RCT', BillingReceipt::class, 'receipt_number');
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     */
    private function next(string $prefix, string $modelClass, string $column): string
    {
        $date = now()->format('Ymd');
        $count = $modelClass::query()
            ->where($column, 'like', $prefix.'-'.$date.'-%')
            ->count() + 1;

        return $prefix.'-'.$date.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
