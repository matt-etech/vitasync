<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreControlledDrugRegisterEntryRequest;
use App\Models\Client;
use App\Models\ControlledDrugRegisterEntry;
use App\Models\Home;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ControlledDrugRegisterController extends Controller
{
    public function index(): View
    {
        $entries = ControlledDrugRegisterEntry::query()
            ->with(['home', 'client', 'recorder', 'witness'])
            ->latest('occurred_at')
            ->latest()
            ->get();

        return view('controlled-drugs.index', [
            'entries' => $entries,
            'stockBalances' => $this->stockBalances($entries),
            'discrepancies' => $entries
                ->filter(fn (ControlledDrugRegisterEntry $entry): bool => $entry->hasDiscrepancy())
                ->values(),
            'homes' => Home::query()->where('status', 'active')->orderBy('name')->get(),
            'clients' => Client::query()->with('home')->orderBy('first_name')->orderBy('last_name')->get(),
            'witnesses' => User::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreControlledDrugRegisterEntryRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validated();
        $transactionType = (string) $validated['transaction_type'];
        $drugName = $this->selectedValue($validated, 'drug_name');
        $form = $this->selectedValue($validated, 'form');
        $unit = $this->selectedValue($validated, 'unit');
        $reason = $this->selectedValue($validated, 'reason');
        $sourceOrDestination = $this->selectedValue($validated, 'source_or_destination');
        $discrepancyReason = $this->selectedValue($validated, 'discrepancy_reason');
        $quantity = round((float) $validated['quantity'], 2);
        $signedQuantity = ControlledDrugRegisterEntry::signedQuantityFor($transactionType, $quantity);
        $stockKey = ControlledDrugRegisterEntry::stockKeyFor(
            $drugName,
            $validated['strength'] ?? null,
            $form,
            $unit,
        );
        $expectedBalanceBefore = $this->currentBalanceFor(
            (int) $validated['home_id'],
            $stockKey,
        );
        $expectedBalanceAfter = round($expectedBalanceBefore + $signedQuantity, 2);
        $actualBalanceAfter = round((float) $validated['actual_balance_after'], 2);
        $discrepancyAmount = round($actualBalanceAfter - $expectedBalanceAfter, 2);
        $discrepancyActive = ControlledDrugRegisterEntry::activatesDiscrepancy($reason);
        $witnessRequired = in_array($transactionType, ControlledDrugRegisterEntry::WITNESS_REQUIRED_TYPES, true);

        if ($discrepancyAmount !== 0.0 && $discrepancyActive && blank($discrepancyReason)) {
            return back()
                ->withInput()
                ->withErrors(['discrepancy_reason' => 'Record a reason for the controlled drug stock discrepancy.']);
        }

        $entry = ControlledDrugRegisterEntry::create([
            'home_id' => $validated['home_id'],
            'client_id' => $validated['client_id'] ?? null,
            'recorded_by' => auth()->id(),
            'witness_user_id' => $validated['witness_user_id'] ?? null,
            'transaction_type' => $transactionType,
            'occurred_at' => $validated['occurred_at'],
            'drug_name' => $drugName,
            'form' => $form,
            'strength' => $validated['strength'] ?? null,
            'unit' => $unit,
            'stock_key' => $stockKey,
            'quantity' => $quantity,
            'signed_quantity' => $signedQuantity,
            'expected_balance_before' => $expectedBalanceBefore,
            'expected_balance_after' => $expectedBalanceAfter,
            'actual_balance_after' => $actualBalanceAfter,
            'discrepancy_amount' => $discrepancyAmount,
            'discrepancy_reason' => $discrepancyActive ? $discrepancyReason : null,
            'reason' => $reason,
            'source_or_destination' => $sourceOrDestination,
            'batch_number' => $validated['batch_number'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'witness_required' => $witnessRequired,
            'witness_name' => $validated['witness_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'submitted_at' => now(),
        ]);

        $entry->load(['home', 'client', 'recorder', 'witness']);

        $auditLogger->log('controlled_drug_register.submitted', [
            'auditable' => $entry,
            'event' => 'Controlled Drug Register',
            'friendly_action' => 'submitted controlled drug register entry for',
            'friendly_subject' => $entry->drug_name,
            'friendly_summary' => (auth()->user()?->name ?? 'System')." submitted {$entry->transactionTypeLabel()} for {$entry->drug_name}.",
            'new_values' => [
                'transaction_type' => $entry->transaction_type,
                'drug_name' => $entry->drug_name,
                'quantity' => $entry->quantity,
                'expected_balance_after' => $entry->expected_balance_after,
                'actual_balance_after' => $entry->actual_balance_after,
                'discrepancy_amount' => $entry->discrepancy_amount,
            ],
            'metadata' => [
                'Home' => $entry->home?->name,
                'Client' => $entry->client?->fullName(),
                'Witness' => $entry->witness?->name ?? $entry->witness_name,
            ],
        ]);

        return redirect()
            ->route('controlled-drugs.index')
            ->with('status', $discrepancyAmount === 0.0
                ? 'Controlled drug register entry submitted.'
                : 'Controlled drug register entry submitted with a stock discrepancy.');
    }

    private function currentBalanceFor(int $homeId, string $stockKey): float
    {
        $entry = ControlledDrugRegisterEntry::query()
            ->where('home_id', $homeId)
            ->where('stock_key', $stockKey)
            ->latest('occurred_at')
            ->latest()
            ->first();

        return $entry ? (float) $entry->actual_balance_after : 0.0;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function selectedValue(array $validated, string $field): ?string
    {
        $value = $validated[$field] ?? null;

        if ($value === ControlledDrugRegisterEntry::OTHER_VALUE) {
            $value = $validated[$field.'_other'] ?? null;
        }

        return filled($value) ? (string) $value : null;
    }

    /**
     * @param  Collection<int, ControlledDrugRegisterEntry>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    private function stockBalances(Collection $entries): Collection
    {
        return $entries
            ->sortBy([
                ['occurred_at', 'asc'],
                ['id', 'asc'],
            ])
            ->groupBy(fn (ControlledDrugRegisterEntry $entry): string => implode('|', [
                $entry->home_id,
                $entry->stock_key,
            ]))
            ->map(function (Collection $stockEntries): array {
                /** @var ControlledDrugRegisterEntry $latest */
                $latest = $stockEntries->last();

                return [
                    'home' => $latest->home?->name ?? 'No home',
                    'drug' => $latest->drug_name,
                    'strength' => $latest->strength,
                    'form' => $latest->form,
                    'unit' => $latest->unit,
                    'balance' => $latest->actual_balance_after,
                    'last_movement_at' => $latest->occurred_at,
                    'last_entry' => $latest,
                ];
            })
            ->sortBy('drug')
            ->values();
    }
}
