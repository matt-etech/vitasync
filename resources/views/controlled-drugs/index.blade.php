@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Controlled Drugs'],
    ]" />
@endsection

@section('content')
    @php
        $otherValue = \App\Models\ControlledDrugRegisterEntry::OTHER_VALUE;
        $negativeReasons = \App\Models\ControlledDrugRegisterEntry::NEGATIVE_REASON_OPTIONS;
    @endphp

    <x-page-header title="Controlled Drugs Register" description="Record controlled medication stock movement with witnesses, balances, discrepancies, and immutable audit history." />

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Stock Lines</p>
                    <p class="display-6 fw-bold mb-0">{{ $stockBalances->count() }}</p>
                    <p class="text-secondary mb-0">Current controlled drug balances.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Register Entries</p>
                    <p class="display-6 fw-bold mb-0">{{ $entries->count() }}</p>
                    <p class="text-secondary mb-0">Submitted lifecycle records.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Discrepancies</p>
                    <p class="display-6 fw-bold mb-0">{{ $discrepancies->count() }}</p>
                    <p class="text-secondary mb-0">Actual balances that differ from expected.</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Witnessed</p>
                    <p class="display-6 fw-bold mb-0">{{ $entries->where('witness_required', true)->count() }}</p>
                    <p class="text-secondary mb-0">Entries requiring a second person.</p>
                </div>
            </div>
        </div>
    </div>

    <form class="form-workspace mb-4" method="POST" action="{{ route('controlled-drugs.store') }}">
        @csrf
        <x-form-errors />

        <section class="form-section">
            <div class="form-section-header">
                <span class="section-kicker">New submitted entry</span>
                <h2 class="form-section-title mt-2">Controlled drug movement</h2>
                <p class="form-section-description">Entries are submitted immediately and cannot be edited or deleted after saving.</p>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="home_id">Home</label>
                    <select class="form-select @error('home_id') is-invalid @enderror" id="home_id" name="home_id" required>
                        <option value="">Select home</option>
                        @foreach ($homes as $home)
                            <option value="{{ $home->id }}" @selected((int) old('home_id') === $home->id)>{{ $home->name }}</option>
                        @endforeach
                    </select>
                    @error('home_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="transaction_type">Entry type</label>
                    <select class="form-select @error('transaction_type') is-invalid @enderror" id="transaction_type" name="transaction_type" required>
                        <option value="">Select entry type</option>
                        @foreach (\App\Models\ControlledDrugRegisterEntry::TRANSACTION_TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('transaction_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('transaction_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="occurred_at">Date and time</label>
                    <input class="form-control @error('occurred_at') is-invalid @enderror" id="occurred_at" name="occurred_at" type="datetime-local" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('occurred_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="drug_name">Controlled drug</label>
                    <select class="form-select @error('drug_name') is-invalid @enderror" id="drug_name" name="drug_name" required data-other-select data-other-target="drug_name_other_wrap">
                        <option value="">Select controlled drug</option>
                        @foreach (\App\Models\ControlledDrugRegisterEntry::DRUG_OPTIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('drug_name') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="{{ $otherValue }}" @selected(old('drug_name') === $otherValue)>Other controlled drug</option>
                    </select>
                    @error('drug_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="mt-2 {{ old('drug_name') === $otherValue ? '' : 'd-none' }}" id="drug_name_other_wrap" data-other-field>
                        <input class="form-control @error('drug_name_other') is-invalid @enderror" name="drug_name_other" value="{{ old('drug_name_other') }}" maxlength="255" placeholder="Enter controlled drug name">
                        @error('drug_name_other')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="strength">Strength</label>
                    <input class="form-control @error('strength') is-invalid @enderror" id="strength" name="strength" value="{{ old('strength') }}" maxlength="120" placeholder="10mg/5ml">
                    @error('strength')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="form">Form</label>
                    <select class="form-select @error('form') is-invalid @enderror" id="form" name="form" data-other-select data-other-target="form_other_wrap">
                        <option value="">Select form</option>
                        @foreach (\App\Models\ControlledDrugRegisterEntry::FORM_OPTIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('form') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="{{ $otherValue }}" @selected(old('form') === $otherValue)>Other form</option>
                    </select>
                    @error('form')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="mt-2 {{ old('form') === $otherValue ? '' : 'd-none' }}" id="form_other_wrap" data-other-field>
                        <input class="form-control @error('form_other') is-invalid @enderror" name="form_other" value="{{ old('form_other') }}" maxlength="120" placeholder="Enter form">
                        @error('form_other')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="unit">Unit</label>
                    <select class="form-select @error('unit') is-invalid @enderror" id="unit" name="unit" required data-other-select data-other-target="unit_other_wrap">
                        <option value="">Select unit</option>
                        @foreach (\App\Models\ControlledDrugRegisterEntry::UNIT_OPTIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('unit', 'units') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="{{ $otherValue }}" @selected(old('unit') === $otherValue)>Other unit</option>
                    </select>
                    @error('unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="mt-2 {{ old('unit') === $otherValue ? '' : 'd-none' }}" id="unit_other_wrap" data-other-field>
                        <input class="form-control @error('unit_other') is-invalid @enderror" name="unit_other" value="{{ old('unit_other') }}" maxlength="40" placeholder="Enter unit">
                        @error('unit_other')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="quantity">Quantity moved</label>
                    <input class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" type="number" step="0.01" min="0.01" value="{{ old('quantity') }}" required>
                    @error('quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="actual_balance_after">Actual stock balance after</label>
                    <input class="form-control @error('actual_balance_after') is-invalid @enderror" id="actual_balance_after" name="actual_balance_after" type="number" step="0.01" min="0" value="{{ old('actual_balance_after') }}" required>
                    @error('actual_balance_after')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="source_or_destination">Source / destination</label>
                    <select class="form-select @error('source_or_destination') is-invalid @enderror" id="source_or_destination" name="source_or_destination" data-other-select data-other-target="source_or_destination_other_wrap">
                        <option value="">Select source / destination</option>
                        @foreach (\App\Models\ControlledDrugRegisterEntry::SOURCE_DESTINATION_OPTIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('source_or_destination') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="{{ $otherValue }}" @selected(old('source_or_destination') === $otherValue)>Other source / destination</option>
                    </select>
                    @error('source_or_destination')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="mt-2 {{ old('source_or_destination') === $otherValue ? '' : 'd-none' }}" id="source_or_destination_other_wrap" data-other-field>
                        <input class="form-control @error('source_or_destination_other') is-invalid @enderror" name="source_or_destination_other" value="{{ old('source_or_destination_other') }}" maxlength="255" placeholder="Enter source or destination">
                        @error('source_or_destination_other')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="client_id">Service user</label>
                    <select class="form-select @error('client_id') is-invalid @enderror" id="client_id" name="client_id">
                        <option value="">Not applicable</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((int) old('client_id') === $client->id)>{{ $client->fullName() }} - {{ $client->home?->name ?: 'No home' }}</option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="batch_number">Batch number</label>
                    <input class="form-control @error('batch_number') is-invalid @enderror" id="batch_number" name="batch_number" value="{{ old('batch_number') }}" maxlength="120">
                    @error('batch_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="expiry_date">Expiry date</label>
                    <input class="form-control @error('expiry_date') is-invalid @enderror" id="expiry_date" name="expiry_date" type="date" value="{{ old('expiry_date') }}">
                    @error('expiry_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="witness_user_id">Witness user</label>
                    <select class="form-select @error('witness_user_id') is-invalid @enderror" id="witness_user_id" name="witness_user_id">
                        <option value="">Manual witness or not required</option>
                        @foreach ($witnesses as $witness)
                            <option value="{{ $witness->id }}" @selected((int) old('witness_user_id') === $witness->id)>{{ $witness->name }}</option>
                        @endforeach
                    </select>
                    @error('witness_user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="witness_name">Manual witness name</label>
                    <input class="form-control @error('witness_name') is-invalid @enderror" id="witness_name" name="witness_name" value="{{ old('witness_name') }}" maxlength="255">
                    @error('witness_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="reason">Reason for administration / movement</label>
                    <select class="form-select @error('reason') is-invalid @enderror" id="reason" name="reason" data-other-select data-other-target="reason_other_wrap">
                        <option value="">Select reason</option>
                        @foreach (\App\Models\ControlledDrugRegisterEntry::REASON_OPTIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="{{ $otherValue }}" @selected(old('reason') === $otherValue)>Other reason</option>
                    </select>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="mt-2 {{ old('reason') === $otherValue ? '' : 'd-none' }}" id="reason_other_wrap" data-other-field>
                        <textarea class="form-control @error('reason_other') is-invalid @enderror" name="reason_other" rows="2" placeholder="Enter reason">{{ old('reason_other') }}</textarea>
                        @error('reason_other')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="discrepancy_reason">Discrepancy reason</label>
                    <select class="form-select @error('discrepancy_reason') is-invalid @enderror" id="discrepancy_reason" name="discrepancy_reason" data-other-select data-other-target="discrepancy_reason_other_wrap" disabled>
                        <option value="">Select discrepancy reason</option>
                        @foreach (\App\Models\ControlledDrugRegisterEntry::DISCREPANCY_REASON_OPTIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('discrepancy_reason') === $value)>{{ $label }}</option>
                        @endforeach
                        <option value="{{ $otherValue }}" @selected(old('discrepancy_reason') === $otherValue)>Other discrepancy reason</option>
                    </select>
                    @error('discrepancy_reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <p class="form-text">Enabled only when a negative dose or movement reason is selected.</p>
                    <div class="mt-2 {{ old('discrepancy_reason') === $otherValue ? '' : 'd-none' }}" id="discrepancy_reason_other_wrap" data-other-field>
                        <textarea class="form-control @error('discrepancy_reason_other') is-invalid @enderror" name="discrepancy_reason_other" rows="2" placeholder="Enter discrepancy reason" disabled>{{ old('discrepancy_reason_other') }}</textarea>
                        @error('discrepancy_reason_other')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Additional notes</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <div class="form-actions">
            <span class="text-secondary small me-auto">Submission records the entry, balance, witness, and audit event permanently.</span>
            <button class="btn btn-primary fw-semibold" type="submit"><i class="fa-solid fa-lock me-1"></i>Submit register entry</button>
        </div>
    </form>

    <ul class="nav nav-tabs mb-3" id="controlledDrugTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="stockTabButton" data-bs-toggle="tab" data-bs-target="#stockTab" type="button" role="tab" aria-controls="stockTab" aria-selected="true">Current stock</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="registerTabButton" data-bs-toggle="tab" data-bs-target="#registerTab" type="button" role="tab" aria-controls="registerTab" aria-selected="false">Register history</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="discrepancyTabButton" data-bs-toggle="tab" data-bs-target="#discrepancyTab" type="button" role="tab" aria-controls="discrepancyTab" aria-selected="false">Discrepancy report</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="stockTab" role="tabpanel" aria-labelledby="stockTabButton" tabindex="0">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Controlled drugs current stock">
                            <thead>
                                <tr>
                                    <th>Home</th>
                                    <th>Controlled drug</th>
                                    <th>Strength / form</th>
                                    <th>Current balance</th>
                                    <th>Last movement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockBalances as $stock)
                                    <tr>
                                        <td>{{ $stock['home'] }}</td>
                                        <td class="fw-semibold">{{ $stock['drug'] }}</td>
                                        <td>{{ collect([$stock['strength'], $stock['form']])->filter()->implode(' / ') ?: 'Not recorded' }}</td>
                                        <td><span class="badge text-bg-info">{{ $stock['balance'] }} {{ $stock['unit'] }}</span></td>
                                        <td>{{ $stock['last_movement_at']?->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="registerTab" role="tabpanel" aria-labelledby="registerTabButton" tabindex="0">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Controlled drugs register">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Home</th>
                                    <th>Drug</th>
                                    <th>Entry</th>
                                    <th>Client / reason</th>
                                    <th>Witness</th>
                                    <th>Balances</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    <tr>
                                        <td>
                                            <p class="fw-semibold mb-0">{{ $entry->occurred_at->format('d/m/Y H:i') }}</p>
                                            <p class="text-secondary mb-0">By {{ $entry->recorder?->name ?: 'Unknown' }}</p>
                                        </td>
                                        <td>{{ $entry->home?->name ?: 'Home unavailable' }}</td>
                                        <td>
                                            <p class="fw-semibold mb-0">{{ $entry->drug_name }}</p>
                                            <p class="text-secondary mb-0">{{ collect([$entry->strength, $entry->form, $entry->unit])->filter()->implode(' / ') }}</p>
                                            @if ($entry->batch_number || $entry->expiry_date)
                                                <p class="small mb-0">Batch {{ $entry->batch_number ?: 'not recorded' }}{{ $entry->expiry_date ? ' - Exp '.$entry->expiry_date->format('d/m/Y') : '' }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge text-bg-{{ $entry->transaction_type === 'received' ? 'success' : 'secondary' }}">{{ $entry->transactionTypeLabel() }}</span>
                                            <p class="text-secondary mb-0">{{ $entry->signed_quantity }} {{ $entry->unit }}</p>
                                            @if ($entry->source_or_destination)
                                                <p class="small mb-0">{{ $entry->source_or_destination }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            <p class="mb-1">{{ $entry->client?->fullName() ?: 'Not linked to a service user' }}</p>
                                            <p class="text-secondary mb-0">{{ $entry->reason ?: 'No reason recorded' }}</p>
                                        </td>
                                        <td>
                                            @if ($entry->witness_required)
                                                <span class="badge text-bg-light border">Required</span>
                                                <p class="mb-0 mt-1">{{ $entry->witness?->name ?? $entry->witness_name ?? 'Missing witness' }}</p>
                                            @else
                                                <span class="badge text-bg-secondary">Not required</span>
                                            @endif
                                        </td>
                                        <td>
                                            <p class="mb-0">Before: {{ $entry->expected_balance_before }}</p>
                                            <p class="mb-0">Expected: {{ $entry->expected_balance_after }}</p>
                                            <p class="mb-0">Actual: {{ $entry->actual_balance_after }}</p>
                                        </td>
                                        <td>
                                            @if ($entry->hasDiscrepancy())
                                                <span class="badge text-bg-danger">Discrepancy {{ $entry->discrepancy_amount }}</span>
                                                <p class="small mb-0 mt-1">{{ $entry->discrepancy_reason ?: 'No reason recorded' }}</p>
                                            @else
                                                <span class="badge text-bg-success">Balanced</span>
                                            @endif
                                            <p class="small text-secondary mb-0 mt-1">Submitted {{ $entry->submitted_at->format('d/m/Y H:i') }}</p>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="discrepancyTab" role="tabpanel" aria-labelledby="discrepancyTabButton" tabindex="0">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Controlled drug discrepancies">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Home</th>
                                    <th>Drug</th>
                                    <th>Expected</th>
                                    <th>Actual</th>
                                    <th>Difference</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($discrepancies as $entry)
                                    <tr>
                                        <td>{{ $entry->occurred_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $entry->home?->name ?: 'Home unavailable' }}</td>
                                        <td>{{ $entry->drug_name }}</td>
                                        <td>{{ $entry->expected_balance_after }} {{ $entry->unit }}</td>
                                        <td>{{ $entry->actual_balance_after }} {{ $entry->unit }}</td>
                                        <td><span class="badge text-bg-danger">{{ $entry->discrepancy_amount }} {{ $entry->unit }}</span></td>
                                        <td>{{ $entry->discrepancy_reason ?: 'No reason recorded' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const otherValue = @json($otherValue);
            const negativeReasons = @json($negativeReasons);
            const reasonSelect = document.getElementById('reason');
            const discrepancySelect = document.getElementById('discrepancy_reason');
            const discrepancyOtherWrap = document.getElementById('discrepancy_reason_other_wrap');
            const discrepancyOtherField = discrepancyOtherWrap?.querySelector('textarea, input, select');

            function syncDiscrepancyActivation() {
                if (!reasonSelect || !discrepancySelect) {
                    return;
                }

                const active = negativeReasons.includes(reasonSelect.value);

                discrepancySelect.disabled = !active;

                if (!active) {
                    discrepancySelect.value = '';
                    discrepancyOtherWrap?.classList.add('d-none');

                    if (discrepancyOtherField) {
                        discrepancyOtherField.value = '';
                        discrepancyOtherField.disabled = true;
                    }
                } else if (discrepancyOtherField) {
                    discrepancyOtherField.disabled = discrepancySelect.value !== otherValue;
                }
            }

            document.querySelectorAll('[data-other-select]').forEach(function (select) {
                const target = document.getElementById(select.dataset.otherTarget);
                const targetField = target?.querySelector('textarea, input, select');

                function syncOtherField() {
                    if (!target) {
                        return;
                    }

                    const show = select.value === otherValue && !select.disabled;

                    target.classList.toggle('d-none', !show);

                    if (targetField) {
                        targetField.disabled = !show;
                    }
                }

                select.addEventListener('change', syncOtherField);
                select.addEventListener('change', syncDiscrepancyActivation);
                syncOtherField();
            });

            reasonSelect?.addEventListener('change', syncDiscrepancyActivation);
            syncDiscrepancyActivation();
        });
    </script>
@endsection
