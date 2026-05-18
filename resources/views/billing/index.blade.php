@extends('layouts.app')

@section('content')
<div class="page-hero mb-4">
    <div class="d-flex flex-column flex-xl-row gap-3 justify-content-xl-between align-items-xl-start">
        <div>
            <p class="section-kicker mb-2">Finance</p>
            <h1 class="h3 fw-bold mb-2">Care Home Billing Workbench</h1>
            <p class="text-secondary mb-0">Resident profile to contract, charges, invoice, payment, receipt, and statement. Financial records are auditable and paid invoices are locked from direct edits.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="fa-solid fa-user-plus me-2"></i>Billing profile</button>
            <button class="btn btn-action btn-action-primary" data-bs-toggle="modal" data-bs-target="#ratePlanModal"><i class="fa-solid fa-tags me-2"></i>Rate plan</button>
            <button class="btn btn-action btn-action-primary" data-bs-toggle="modal" data-bs-target="#contractModal"><i class="fa-solid fa-file-signature me-2"></i>Contract</button>
            <button class="btn btn-action btn-action-primary" data-bs-toggle="modal" data-bs-target="#chargeModal"><i class="fa-solid fa-circle-plus me-2"></i>Charge</button>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Billing action not saved.</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<ul class="nav nav-tabs flex-nowrap overflow-auto mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profilesTab" type="button">Profiles</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#contractsTab" type="button">Contracts</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#chargesTab" type="button">Charges</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#invoicesTab" type="button">Invoices</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#paymentsTab" type="button">Payments & statements</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="profilesTab">
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Billing Profiles">
                    <thead><tr><th>Resident</th><th>Funding</th><th>Contact</th><th>Terms</th><th>Active contract</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($profiles as $profile)
                            <tr>
                                <td><strong>{{ $profile->client->fullName() }}</strong><span class="d-block small text-secondary">{{ $profile->room_bed ?: 'No room/bed recorded' }}</span></td>
                                <td>{{ $fundingSources[$profile->funding_source] ?? $profile->funding_source }}<span class="d-block small text-secondary">{{ $profile->currency }} @if($profile->tax_exempt) tax exempt @else tax {{ $profile->tax_rate }}% @endif</span></td>
                                <td>{{ $profile->billing_contact_name }}<span class="d-block small text-secondary">{{ $profile->billing_contact_email ?: $profile->billing_contact_phone }}</span></td>
                                <td>{{ $profile->payment_terms }}</td>
                                <td>{{ $profile->activeContract?->ratePlan?->name ?? 'Not assigned' }}</td>
                                <td><span class="badge text-bg-{{ $profile->status === 'active' ? 'success' : 'secondary' }}">{{ $profileStatuses[$profile->status] ?? $profile->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="contractsTab">
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Billing Contracts">
                    <thead><tr><th>Resident</th><th>Rate plan</th><th>Cycle</th><th>Deposit</th><th>Discount</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($contracts as $contract)
                            <tr>
                                <td><strong>{{ $contract->profile->client->fullName() }}</strong><span class="d-block small text-secondary">{{ $contract->start_date?->format('d M Y') }} - {{ $contract->end_date?->format('d M Y') ?? 'open' }}</span></td>
                                <td>{{ $contract->ratePlan->name }}</td>
                                <td>{{ $billingCycles[$contract->billing_cycle] ?? $contract->billing_cycle }}<span class="d-block small text-secondary">Due day {{ $contract->due_day }}</span></td>
                                <td>{{ $contract->ratePlan->currency }} {{ number_format((float) $contract->deposit_amount, 2) }}</td>
                                <td>{{ $contract->discount_type ?: 'None' }} {{ (float) $contract->discount_amount > 0 ? number_format((float) $contract->discount_amount, 2) : '' }}</td>
                                <td><span class="badge text-bg-{{ $contract->status === 'active' ? 'success' : 'secondary' }}">{{ $contractStatuses[$contract->status] ?? $contract->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header bg-white fw-bold">Rate plans</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Rate Plans">
                    <thead><tr><th>Name</th><th>Room</th><th>Care</th><th>Cycle</th><th>Late rule</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($ratePlans as $ratePlan)
                            <tr>
                                <td><strong>{{ $ratePlan->name }}</strong><span class="d-block small text-secondary">{{ $ratePlan->description }}</span></td>
                                <td>{{ $ratePlan->currency }} {{ number_format((float) $ratePlan->room_fee, 2) }}</td>
                                <td>{{ $ratePlan->currency }} {{ number_format((float) $ratePlan->care_fee, 2) }}</td>
                                <td>{{ $billingCycles[$ratePlan->billing_cycle] ?? $ratePlan->billing_cycle }}</td>
                                <td>{{ $lateFeeTypes[$ratePlan->late_fee_type] ?? $ratePlan->late_fee_type }} {{ number_format((float) $ratePlan->late_fee_amount, 2) }}</td>
                                <td><span class="badge text-bg-{{ $ratePlan->status === 'active' ? 'success' : 'secondary' }}">{{ $ratePlanStatuses[$ratePlan->status] ?? $ratePlan->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="chargesTab">
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Billing Charges">
                    <thead><tr><th>Date</th><th>Resident</th><th>Charge</th><th>Amount</th><th>Staff</th><th>Approval</th><th class="no-export">Action</th></tr></thead>
                    <tbody>
                        @foreach ($charges as $charge)
                            <tr>
                                <td>{{ $charge->charge_date?->format('d M Y') }}</td>
                                <td>{{ $charge->profile->client->fullName() }}</td>
                                <td><strong>{{ $charge->description }}</strong><span class="d-block small text-secondary">{{ $chargeTypes[$charge->charge_type] ?? $charge->charge_type }} - {{ $chargeCategories[$charge->category] ?? $charge->category }}</span></td>
                                <td>{{ $charge->profile->currency }} {{ $charge->is_credit ? '-' : '' }}{{ number_format((float) $charge->amount, 2) }}</td>
                                <td>{{ $charge->staff?->name ?? 'Not recorded' }}</td>
                                <td><span class="badge text-bg-{{ $charge->approval_status === 'approved' ? 'success' : ($charge->approval_status === 'pending' ? 'warning' : 'secondary') }}">{{ $approvalStatuses[$charge->approval_status] ?? $charge->approval_status }}</span></td>
                                <td class="no-export">
                                    @if ($charge->approval_status !== 'approved' && $charge->billing_invoice_id === null)
                                        <form method="POST" action="{{ route('billing.charges.approve', $charge) }}" data-confirm data-confirm-title="Approve charge?" data-confirm-text="This charge will become available for invoice generation.">
                                            @csrf
                                            <button class="btn btn-sm btn-action btn-action-primary"><i class="fa-solid fa-check me-1"></i>Approve</button>
                                        </form>
                                    @else
                                        <span class="text-secondary small">{{ $charge->invoice?->invoice_number ?? 'Ready' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="invoicesTab">
        <div class="card mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('billing.invoices.generate') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Active contract</label>
                        <select class="form-select" name="billing_contract_id" required>
                            <option value="">Select contract</option>
                            @foreach ($contracts->where('status', 'active') as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->profile->client->fullName() }} - {{ $contract->ratePlan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label fw-bold">Period start</label><input class="form-control" type="date" name="period_start" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
                    <div class="col-md-2"><label class="form-label fw-bold">Period end</label><input class="form-control" type="date" name="period_end" value="{{ now()->endOfMonth()->toDateString() }}" required></div>
                    <div class="col-md-3"><button class="btn btn-primary w-100"><i class="fa-solid fa-file-invoice me-2"></i>Generate invoice</button></div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Invoices">
                    <thead><tr><th>Invoice</th><th>Resident</th><th>Period</th><th>Due</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_number }}</strong><span class="d-block small text-secondary">{{ $invoice->issue_date?->format('d M Y') }}</span></td>
                                <td>{{ $invoice->profile->client->fullName() }}</td>
                                <td>{{ $invoice->period_start?->format('d M Y') }} - {{ $invoice->period_end?->format('d M Y') }}</td>
                                <td>{{ $invoice->due_date?->format('d M Y') }}</td>
                                <td>{{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                                <td>{{ $invoice->currency }} {{ number_format((float) $invoice->paid_amount, 2) }}</td>
                                <td>{{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}</td>
                                <td><span class="badge text-bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">{{ $invoice->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="paymentsTab">
        <div class="card mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('billing.payments.store') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Open invoice</label>
                        <select class="form-select" name="billing_invoice_id" required>
                            <option value="">Select invoice</option>
                            @foreach ($invoices->whereNotIn('status', ['paid', 'void']) as $invoice)
                                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - {{ $invoice->profile->client->fullName() }} - {{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label fw-bold">Date</label><input class="form-control" type="date" name="payment_date" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-2"><label class="form-label fw-bold">Amount</label><input class="form-control" type="number" min="0.01" step="0.01" name="amount" required></div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Method</label>
                        <select class="form-select" name="method" required>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-money-check-dollar me-2"></i>Record</button></div>
                    <div class="col-md-4"><label class="form-label fw-bold">Payer</label><input class="form-control" name="payer_name"></div>
                    <div class="col-md-4"><label class="form-label fw-bold">Reference</label><input class="form-control" name="reference"></div>
                    <div class="col-md-4"><label class="form-label fw-bold">Notes</label><input class="form-control" name="notes"></div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Payments and Receipts">
                    <thead><tr><th>Payment</th><th>Resident</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Receipt</th><th>Received by</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td><strong>{{ $payment->payment_number }}</strong><span class="d-block small text-secondary">{{ $payment->payment_date?->format('d M Y') }}</span></td>
                                <td>{{ $payment->profile->client->fullName() }}</td>
                                <td>{{ $payment->invoice->invoice_number }}</td>
                                <td>{{ $payment->invoice->currency }} {{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ $paymentMethods[$payment->method] ?? $payment->method }}</td>
                                <td>{{ $payment->receipt?->receipt_number ?? 'Pending' }}</td>
                                <td>{{ $payment->receiver?->name ?? 'Not recorded' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header bg-white fw-bold">Statement entries</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="Billing Statements">
                    <thead><tr><th>Date</th><th>Resident</th><th>Type</th><th>Description</th><th>Debit</th><th>Credit</th><th>Running balance</th></tr></thead>
                    <tbody>
                        @foreach ($statementEntries as $entry)
                            <tr>
                                <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                                <td>{{ $entry->profile->client->fullName() }}</td>
                                <td>{{ ucfirst($entry->entry_type) }}</td>
                                <td>{{ $entry->description }}</td>
                                <td>{{ $entry->profile->currency }} {{ number_format((float) $entry->debit, 2) }}</td>
                                <td>{{ $entry->profile->currency }} {{ number_format((float) $entry->credit, 2) }}</td>
                                <td><strong>{{ $entry->profile->currency }} {{ number_format((float) $entry->running_balance, 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="POST" action="{{ route('billing.profiles.store') }}">@csrf
        <div class="modal-header"><h2 class="modal-title h5">Create resident billing profile</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-6"><label class="form-label fw-bold">Resident</label><select class="form-select" name="client_id" required><option value="">Select resident</option>@foreach($clients->whereNull('billingProfile') as $client)<option value="{{ $client->id }}">{{ $client->fullName() }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label fw-bold">Admission</label><input class="form-control" type="date" name="admission_date"></div>
            <div class="col-md-3"><label class="form-label fw-bold">Room/bed</label><input class="form-control" name="room_bed"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Billing contact</label><input class="form-control" name="billing_contact_name" required></div>
            <div class="col-md-6"><label class="form-label fw-bold">Relationship</label><input class="form-control" name="billing_contact_relationship"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Email</label><input class="form-control" type="email" name="billing_contact_email"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Phone</label><input class="form-control" name="billing_contact_phone"></div>
            <div class="col-md-6"><label class="form-label fw-bold">Funding source</label><select class="form-select" name="funding_source" required>@foreach($fundingSources as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label fw-bold">Payment terms</label><input class="form-control" name="payment_terms" value="Due on receipt" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Currency</label><input class="form-control" name="currency" value="USD" maxlength="3" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Tax/VAT %</label><input class="form-control" type="number" min="0" max="100" step="0.01" name="tax_rate" value="0" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Status</label><select class="form-select" name="status">@foreach($profileStatuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-12"><label class="choice-card"><input class="form-check-input" type="checkbox" name="tax_exempt" value="1"><span><strong>Tax exempt</strong><span class="d-block text-secondary small">No tax/VAT will be added to generated invoices.</span></span></label></div>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-dark" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create profile</button></div>
    </form></div>
</div>

<div class="modal fade" id="ratePlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="POST" action="{{ route('billing.rate-plans.store') }}">@csrf
        <div class="modal-header"><h2 class="modal-title h5">Create rate plan</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-8"><label class="form-label fw-bold">Name</label><input class="form-control" name="name" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Currency</label><input class="form-control" name="currency" value="USD" maxlength="3" required></div>
            <div class="col-12"><label class="form-label fw-bold">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
            <div class="col-md-4"><label class="form-label fw-bold">Room fee</label><input class="form-control" type="number" min="0" step="0.01" name="room_fee" value="0" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Care fee</label><input class="form-control" type="number" min="0" step="0.01" name="care_fee" value="0" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Billing cycle</label><select class="form-select" name="billing_cycle">@foreach($billingCycles as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label fw-bold">Due day</label><input class="form-control" type="number" min="1" max="28" name="due_day" value="1" required></div>
            <div class="col-md-3"><label class="form-label fw-bold">Deposit</label><input class="form-control" type="number" min="0" step="0.01" name="deposit_amount" value="0" required></div>
            <div class="col-md-3"><label class="form-label fw-bold">Notice days</label><input class="form-control" type="number" min="0" name="notice_period_days" value="30" required></div>
            <div class="col-md-3"><label class="form-label fw-bold">Status</label><select class="form-select" name="status">@foreach($ratePlanStatuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Late fee type</label><select class="form-select" name="late_fee_type">@foreach($lateFeeTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Late fee amount</label><input class="form-control" type="number" min="0" step="0.01" name="late_fee_amount" value="0" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Discount type</label><select class="form-select" name="discount_type"><option value="">None</option>@foreach($discountTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Discount amount</label><input class="form-control" type="number" min="0" step="0.01" name="discount_amount" value="0" required></div>
            <div class="col-12"><label class="choice-card"><input class="form-check-input" type="checkbox" name="meals_included" value="1" checked><span><strong>Meals included</strong><span class="d-block text-secondary small">Included meals are not charged separately by this rate plan.</span></span></label></div>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-dark" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create rate plan</button></div>
    </form></div>
</div>

<div class="modal fade" id="contractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="POST" action="{{ route('billing.contracts.store') }}">@csrf
        <div class="modal-header"><h2 class="modal-title h5">Create billing contract</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-6"><label class="form-label fw-bold">Billing profile</label><select class="form-select" name="billing_profile_id" required><option value="">Select resident</option>@foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->client->fullName() }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label fw-bold">Rate plan</label><select class="form-select" name="billing_rate_plan_id" required><option value="">Select rate plan</option>@foreach($ratePlans->where('status', 'active') as $ratePlan)<option value="{{ $ratePlan->id }}">{{ $ratePlan->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Start</label><input class="form-control" type="date" name="start_date" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">End</label><input class="form-control" type="date" name="end_date"></div>
            <div class="col-md-4"><label class="form-label fw-bold">Status</label><select class="form-select" name="status">@foreach($contractStatuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Cycle</label><select class="form-select" name="billing_cycle">@foreach($billingCycles as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label fw-bold">Due day</label><input class="form-control" type="number" min="1" max="28" name="due_day" value="1" required></div>
            <div class="col-md-3"><label class="form-label fw-bold">Deposit</label><input class="form-control" type="number" min="0" step="0.01" name="deposit_amount" value="0" required></div>
            <div class="col-md-3"><label class="form-label fw-bold">Notice days</label><input class="form-control" type="number" min="0" name="notice_period_days" value="30" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Late fee type</label><select class="form-select" name="late_fee_type">@foreach($lateFeeTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Late fee amount</label><input class="form-control" type="number" min="0" step="0.01" name="late_fee_amount" value="0" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Discount type</label><select class="form-select" name="discount_type"><option value="">None</option>@foreach($discountTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Discount amount</label><input class="form-control" type="number" min="0" step="0.01" name="discount_amount" value="0" required></div>
            <div class="col-12"><label class="form-label fw-bold">Care level pricing JSON</label><textarea class="form-control" name="care_level_pricing" rows="2" placeholder='{"high_care": 450}'></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-dark" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create contract</button></div>
    </form></div>
</div>

<div class="modal fade" id="chargeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="POST" action="{{ route('billing.charges.store') }}">@csrf
        <div class="modal-header"><h2 class="modal-title h5">Add charge</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-6"><label class="form-label fw-bold">Resident</label><select class="form-select" name="billing_profile_id" required><option value="">Select resident</option>@foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->client->fullName() }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label fw-bold">Contract</label><select class="form-select" name="billing_contract_id"><option value="">Use active/default profile contract</option>@foreach($contracts as $contract)<option value="{{ $contract->id }}">{{ $contract->profile->client->fullName() }} - {{ $contract->ratePlan->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Type</label><select class="form-select" name="charge_type">@foreach($chargeTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Category</label><select class="form-select" name="category">@foreach($chargeCategories as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label fw-bold">Date</label><input class="form-control" type="date" name="charge_date" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-8"><label class="form-label fw-bold">Description</label><input class="form-control" name="description" required></div>
            <div class="col-md-4"><label class="form-label fw-bold">Amount</label><input class="form-control" type="number" min="0.01" step="0.01" name="amount" required></div>
            <div class="col-md-6"><label class="form-label fw-bold">Staff member</label><select class="form-select" name="staff_user_id"><option value="">Current user</option>@foreach($staff as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label fw-bold">Approval</label><select class="form-select" name="approval_status">@foreach($approvalStatuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-12"><label class="choice-card"><input class="form-check-input" type="checkbox" name="is_credit" value="1"><span><strong>Credit/discount</strong><span class="d-block text-secondary small">Treat this as money taken off the invoice total.</span></span></label></div>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-dark" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Add charge</button></div>
    </form></div>
</div>
@endsection
