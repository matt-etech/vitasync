@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <p class="brand-kicker mb-1">Family access</p>
                <h1 class="h3 fw-bold mb-2">Welcome, {{ $familyMember->name }}</h1>
                <p class="text-secondary mb-0">You are viewing the information shared for {{ $familyMember->client->fullName() }}.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Log out</button>
            </form>
        </div>

        <div class="alert alert-info">
            Family access is permission-based. Only information approved by the care team is shown here.
        </div>

        @php
            $canViewVisitTab = $familyMember->canAccess('can_view_appointments') || $familyMember->canAccess('can_view_visits');
            $canViewMessagesTab = $familyMember->canAccess('can_view_staff_messages');
            $canViewDocumentsTab = $familyMember->canAccess('can_upload_documents') || $familyMember->canAccess('can_view_shared_documents') || $familyMember->canAccess('can_view_sensitive_documents');
            $upcomingFamilyVisits = $sharedVisits
                ->when(! $familyMember->canAccess('can_view_appointments'), fn ($visits) => collect())
                ->filter(fn ($visit) => $visit->scheduled_start_at !== null && $visit->scheduled_start_at->greaterThanOrEqualTo(now()))
                ->sortBy('scheduled_start_at')
                ->values();
            $pastFamilyVisits = $sharedVisits
                ->when(! $familyMember->canAccess('can_view_visits'), fn ($visits) => collect())
                ->filter(fn ($visit) => $visit->scheduled_start_at !== null && $visit->scheduled_start_at->lessThan(now()))
                ->sortByDesc('scheduled_start_at')
                ->values();
            $calendarAnchor = ($upcomingFamilyVisits->first()?->scheduled_start_at ?? $pastFamilyVisits->first()?->scheduled_start_at ?? now())->copy();
            $calendarMonth = $calendarAnchor->copy()->startOfMonth();
            $calendarStart = $calendarMonth->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
            $calendarDays = collect(range(0, 41))->map(fn (int $offset) => $calendarStart->copy()->addDays($offset));
            $visitsByDate = $upcomingFamilyVisits->merge($pastFamilyVisits)->groupBy(fn ($visit) => $visit->scheduled_start_at?->toDateString());
        @endphp

        <div class="row g-4 mb-4">
            <div class="col-lg-12">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-3">Client profile</h2>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Client</dt>
                            <dd class="col-sm-8">{{ $familyMember->client->fullName() }}</dd>
                            <dt class="col-sm-4">Home</dt>
                            <dd class="col-sm-8">{{ $familyMember->client->home?->name ?? 'Not assigned' }}</dd>
                            <dt class="col-sm-4">Relationship</dt>
                            <dd class="col-sm-8">{{ $familyMember->relationship ?: 'Not recorded' }}</dd>
                            <dt class="col-sm-4">Last login</dt>
                            <dd class="col-sm-8">{{ $familyMember->last_login_at?->format('d/m/Y H:i') ?? 'Not recorded' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="updates-tab" data-bs-toggle="tab" data-bs-target="#updates-pane" type="button" role="tab" aria-controls="updates-pane" aria-selected="true">
                    <i class="fa-solid fa-heart-pulse me-1"></i>Updates
                </button>
            </li>
            @if ($canViewVisitTab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="visits-tab" data-bs-toggle="tab" data-bs-target="#visits-pane" type="button" role="tab" aria-controls="visits-pane" aria-selected="false">
                        <i class="fa-solid fa-calendar-days me-1"></i>Visits
                    </button>
                </li>
            @endif
            @if ($financeSummary)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance-pane" type="button" role="tab" aria-controls="finance-pane" aria-selected="false">
                        <i class="fa-solid fa-file-invoice-dollar me-1"></i>Finance
                    </button>
                </li>
            @endif
            @if ($canViewMessagesTab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="messages-tab" data-bs-toggle="tab" data-bs-target="#messages-pane" type="button" role="tab" aria-controls="messages-pane" aria-selected="false">
                        <i class="fa-solid fa-message me-1"></i>Messages
                    </button>
                </li>
            @endif
            @if ($canViewDocumentsTab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents-pane" type="button" role="tab" aria-controls="documents-pane" aria-selected="false">
                        <i class="fa-solid fa-folder-open me-1"></i>Documents
                    </button>
                </li>
            @endif
            @if ($safeguardingSummary)
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="safeguarding-tab" data-bs-toggle="tab" data-bs-target="#safeguarding-pane" type="button" role="tab" aria-controls="safeguarding-pane" aria-selected="false">
                        <i class="fa-solid fa-shield-halved me-1"></i>Safeguarding
                    </button>
                </li>
            @endif
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="updates-pane" role="tabpanel" aria-labelledby="updates-tab" tabindex="0">
                <div class="row g-4">
            @if ($carePlanSummary)
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Care plan summary</h2>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Plan</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['title'] }}</dd>
                                <dt class="col-sm-4">Care level</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['care_level'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Visit frequency</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['visit_frequency'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Review date</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['review_date']?->format('d/m/Y') ?? 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Risk level</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['risk_level'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Goals</dt>
                                <dd class="col-sm-8">{{ $carePlanSummary['care_goals'] ?: 'Not recorded' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            @endif

            @if ($medicationSummary)
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Medication summary</h2>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Support needed</dt>
                                <dd class="col-sm-8">{{ $medicationSummary['support_needed'] ? 'Yes' : 'No' }}</dd>
                                <dt class="col-sm-4">Support summary</dt>
                                <dd class="col-sm-8">{{ $medicationSummary['support_summary'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Allergies</dt>
                                <dd class="col-sm-8">{{ $medicationSummary['allergies'] ?: 'Not recorded' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            @endif

            @if ($visitNotes->isNotEmpty())
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Visit notes shared by staff</h2>
                            <div class="vstack gap-3">
                                @foreach ($visitNotes as $visit)
                                    <div class="border rounded p-3">
                                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                            <p class="fw-semibold mb-0">{{ $visit->title }}</p>
                                            <span class="text-secondary small">{{ $visit->durationLabel() }}</span>
                                        </div>
                                        <p class="mb-0">{{ $visit->notes }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($incidentNotifications->isNotEmpty())
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Incident notifications</h2>
                            <div class="vstack gap-3">
                                @foreach ($incidentNotifications as $incident)
                                    <div class="border rounded p-3">
                                        <p class="fw-semibold mb-1">{{ data_get($incident->metadata, 'category', 'Incident update') }}</p>
                                        <p class="text-secondary mb-0">A manager-approved incident notification is available. Reported {{ $incident->created_at->format('d/m/Y H:i') }}.</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (! $carePlanSummary && ! $medicationSummary && $visitNotes->isEmpty() && $incidentNotifications->isEmpty())
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-2">No shared updates yet</h2>
                            <p class="text-secondary mb-0">The care team has not shared any care updates, visits, medication summaries, or incident notifications with this family account yet.</p>
                        </div>
                    </div>
                </div>
            @endif

                </div>
            </div>

            @if ($canViewVisitTab)
                <div class="tab-pane fade" id="visits-pane" role="tabpanel" aria-labelledby="visits-tab" tabindex="0">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        <h2 class="h5 fw-bold mb-0">Visit calendar</h2>
                                        <span class="text-secondary">{{ $calendarMonth->format('F Y') }}</span>
                                    </div>
                                    <div class="d-grid gap-1 text-center small fw-semibold text-secondary mb-2" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
                                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                                            <div>{{ $dayName }}</div>
                                        @endforeach
                                    </div>
                                    <div class="d-grid gap-1" style="grid-template-columns: repeat(7, minmax(0, 1fr));">
                                        @foreach ($calendarDays as $day)
                                            @php
                                                $dayVisits = $visitsByDate->get($day->toDateString(), collect());
                                                $isCurrentMonth = $day->month === $calendarMonth->month;
                                            @endphp
                                            <div class="border rounded p-2 {{ $isCurrentMonth ? 'bg-white' : 'bg-light text-secondary' }}" style="min-height: 6rem;">
                                                <div class="fw-semibold mb-1">{{ $day->day }}</div>
                                                <div class="vstack gap-1">
                                                    @foreach ($dayVisits->take(3) as $visit)
                                                        @php
                                                            $visitBadgeClass = match ($visit->status) {
                                                                'completed' => 'text-bg-success',
                                                                'in_progress' => 'text-bg-warning',
                                                                'missed', 'cancelled' => 'text-bg-danger',
                                                                default => 'text-bg-light border',
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $visitBadgeClass }} text-truncate" title="{{ $visit->title }} - {{ $visit->durationLabel() }}">{{ $visit->scheduled_start_at->format('H:i') }} {{ str($visit->title)->limit(18) }}</span>
                                                    @endforeach
                                                    @if ($dayVisits->count() > 3)
                                                        <span class="text-secondary small">+{{ $dayVisits->count() - 3 }} more</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($familyMember->canAccess('can_view_appointments'))
                            <div class="col-lg-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h2 class="h5 fw-bold mb-3">Upcoming visits</h2>
                                        @if ($upcomingFamilyVisits->isNotEmpty())
                                            <div class="vstack gap-3">
                                                @foreach ($upcomingFamilyVisits as $visit)
                                                    <div class="border rounded p-3">
                                                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                                            <p class="fw-semibold mb-0">{{ $visit->title }}</p>
                                                            <span class="badge text-bg-light border">{{ str($visit->status)->replace('_', ' ')->title() }}</span>
                                                        </div>
                                                        <p class="text-secondary mb-1">{{ $visit->durationLabel() }}</p>
                                                        <p class="mb-0">{{ $visit->carePlan?->title ?? 'Care plan not linked' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-secondary mb-0">No upcoming visits are currently shared.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($familyMember->canAccess('can_view_visits'))
                            <div class="col-lg-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h2 class="h5 fw-bold mb-3">Past visits</h2>
                                        @if ($pastFamilyVisits->isNotEmpty())
                                            <div class="vstack gap-3">
                                                @foreach ($pastFamilyVisits as $visit)
                                                    <div class="border rounded p-3">
                                                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                                            <p class="fw-semibold mb-0">{{ $visit->title }}</p>
                                                            <span class="badge text-bg-light border">{{ str($visit->status)->replace('_', ' ')->title() }}</span>
                                                        </div>
                                                        <p class="text-secondary mb-1">{{ $visit->durationLabel() }}</p>
                                                        @if ($visit->check_in_at || $visit->check_out_at)
                                                            <p class="mb-1">Attendance: {{ $visit->check_in_at ? 'arrived '.$visit->check_in_at->format('d/m/Y H:i') : 'arrival not recorded' }}{{ $visit->check_out_at ? ', left '.$visit->check_out_at->format('H:i') : '' }}</p>
                                                        @endif
                                                        @if (filled($visit->notes))
                                                            <p class="mb-0">{{ $visit->notes }}</p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-secondary mb-0">No past visits are currently shared.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($financeSummary)
                <div class="tab-pane fade" id="finance-pane" role="tabpanel" aria-labelledby="finance-tab" tabindex="0">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <p class="text-secondary mb-1">Outstanding balance</p>
                                    <p class="display-6 fw-bold mb-0">{{ $financeSummary['currency'] ? $financeSummary['currency'].' ' : '' }}{{ number_format($financeSummary['outstanding_balance'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <p class="text-secondary mb-1">Pending contract charges</p>
                                    <p class="display-6 fw-bold mb-0">{{ $financeSummary['currency'] ? $financeSummary['currency'].' ' : '' }}{{ number_format($financeSummary['pending_contract_total'], 2) }}</p>
                                    @if ($financeSummary['deposit_applied'] > 0)
                                        <p class="text-secondary small mb-0">Deposit applied: {{ $financeSummary['currency'] ? $financeSummary['currency'].' ' : '' }}{{ number_format($financeSummary['deposit_applied'], 2) }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <p class="text-secondary mb-1">Overdue balance</p>
                                    <p class="display-6 fw-bold mb-0">{{ $financeSummary['currency'] ? $financeSummary['currency'].' ' : '' }}{{ number_format($financeSummary['overdue_balance'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <p class="text-secondary mb-1">Open invoices</p>
                                    <p class="display-6 fw-bold mb-0">{{ $financeSummary['open_invoices']->count() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h2 class="h5 fw-bold mb-3">Invoices owed</h2>
                                    @if ($financeSummary['open_invoices']->isNotEmpty())
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Invoice</th>
                                                        <th>Period</th>
                                                        <th>Due date</th>
                                                        <th>Status</th>
                                                        <th class="text-end">Total</th>
                                                        <th class="text-end">Paid</th>
                                                        <th class="text-end">Owed</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($financeSummary['open_invoices'] as $invoice)
                                                        <tr>
                                                            <td>{{ $invoice->invoice_number }}</td>
                                                            <td>{{ $invoice->period_start?->format('d/m/Y') ?? 'Not set' }} - {{ $invoice->period_end?->format('d/m/Y') ?? 'Not set' }}</td>
                                                            <td>{{ $invoice->due_date?->format('d/m/Y') ?? 'Not set' }}</td>
                                                            <td><span class="badge text-bg-light border">{{ str($invoice->status)->replace('_', ' ')->title() }}</span></td>
                                                            <td class="text-end">{{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}</td>
                                                            <td class="text-end">{{ $invoice->currency }} {{ number_format((float) $invoice->paid_amount, 2) }}</td>
                                                            <td class="text-end fw-semibold">{{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-secondary mb-0">There are no open invoices for this client.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($financeSummary['recent_payments']->isNotEmpty())
                            <div class="col-lg-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h2 class="h5 fw-bold mb-3">Recent payments</h2>
                                        <div class="vstack gap-3">
                                            @foreach ($financeSummary['recent_payments'] as $payment)
                                                <div class="border rounded p-3">
                                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                                        <p class="fw-semibold mb-0">{{ $payment->payment_number }}</p>
                                                        <span>{{ $financeSummary['currency'] ? $financeSummary['currency'].' ' : '' }}{{ number_format((float) $payment->amount, 2) }}</span>
                                                    </div>
                                                    <p class="text-secondary mb-0">{{ $payment->payment_date?->format('d/m/Y') ?? 'Date not set' }} - {{ str($payment->method)->replace('_', ' ')->title() }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($financeSummary['statement_entries']->isNotEmpty())
                            <div class="col-lg-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h2 class="h5 fw-bold mb-3">Statement activity</h2>
                                        <div class="vstack gap-3">
                                            @foreach ($financeSummary['statement_entries'] as $entry)
                                                <div class="border rounded p-3">
                                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                                        <p class="fw-semibold mb-0">{{ $entry->description }}</p>
                                                        <span>{{ $financeSummary['currency'] ? $financeSummary['currency'].' ' : '' }}{{ number_format((float) $entry->running_balance, 2) }}</span>
                                                    </div>
                                                    <p class="text-secondary mb-0">{{ $entry->entry_date?->format('d/m/Y') ?? 'Date not set' }} - {{ str($entry->entry_type)->title() }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($canViewMessagesTab)
                <div class="tab-pane fade" id="messages-pane" role="tabpanel" aria-labelledby="messages-tab" tabindex="0">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Messages from staff</h2>
                            @if ($staffMessages->isNotEmpty())
                                <div class="vstack gap-3">
                                    @foreach ($staffMessages as $message)
                                        <div class="border rounded p-3">
                                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                                <p class="fw-semibold mb-0">{{ $message->subject }}</p>
                                                <span class="text-secondary small">{{ $message->sent_at?->format('d/m/Y H:i') ?? 'Date not recorded' }}</span>
                                            </div>
                                            <p class="mb-1">{{ $message->message }}</p>
                                            @if ($message->sender)
                                                <p class="text-secondary small mb-0">Sent by {{ $message->sender->name }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-secondary mb-0">No staff messages are currently shared.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($canViewDocumentsTab)
                <div class="tab-pane fade" id="documents-pane" role="tabpanel" aria-labelledby="documents-tab" tabindex="0">
                    <div class="row g-4">
                        @if ($familyMember->canAccess('can_upload_documents'))
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h2 class="h5 fw-bold mb-3">Upload documents</h2>
                                        <form method="POST" action="{{ route('family-portal.documents.store') }}" enctype="multipart/form-data" class="row g-3">
                                            @csrf
                                            <input type="hidden" name="client_id" value="{{ $familyMember->client_id }}">
                                            <div class="col-md-4">
                                                <label class="form-label" for="display_name">Document name</label>
                                                <input class="form-control" id="display_name" name="display_name" maxlength="255">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="category">Category</label>
                                                <input class="form-control" id="category" name="category" maxlength="100" placeholder="Family upload">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="document">File</label>
                                                <input class="form-control" id="document" name="document" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.txt" required>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload me-1"></i>Upload document</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($familyMember->canAccess('can_view_shared_documents'))
                            <div class="col-lg-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h2 class="h5 fw-bold mb-3">Shared documents</h2>
                                        @if ($sharedDocuments->isNotEmpty())
                                            <div class="vstack gap-3">
                                                @foreach ($sharedDocuments as $document)
                                                    <div class="border rounded p-3">
                                                        <p class="fw-semibold mb-1">{{ $document->display_name }}</p>
                                                        <p class="text-secondary mb-0">{{ $document->category ?: 'Document' }} - {{ $document->uploaded_at?->format('d/m/Y H:i') ?? 'Date not recorded' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-secondary mb-0">No shared documents are currently available.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($familyMember->canAccess('can_view_sensitive_documents'))
                            <div class="col-lg-6">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body">
                                        <h2 class="h5 fw-bold mb-3">Sensitive documents</h2>
                                        @if ($sensitiveDocuments->isNotEmpty())
                                            <div class="vstack gap-3">
                                                @foreach ($sensitiveDocuments as $document)
                                                    <div class="border rounded p-3">
                                                        <p class="fw-semibold mb-1">{{ $document->display_name }}</p>
                                                        <p class="text-secondary mb-0">{{ $document->category ?: 'Sensitive document' }} - {{ $document->uploaded_at?->format('d/m/Y H:i') ?? 'Date not recorded' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-secondary mb-0">No sensitive documents are currently shared.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($safeguardingSummary)
                <div class="tab-pane fade" id="safeguarding-pane" role="tabpanel" aria-labelledby="safeguarding-tab" tabindex="0">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 fw-bold mb-3">Safeguarding summary</h2>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Safeguarding risk</dt>
                                <dd class="col-sm-8">{{ $safeguardingSummary['safeguarding_risk'] }}</dd>
                                <dt class="col-sm-4">Control measures</dt>
                                <dd class="col-sm-8">{{ $safeguardingSummary['control_measures'] ?: 'Not recorded' }}</dd>
                                <dt class="col-sm-4">Notes</dt>
                                <dd class="col-sm-8">{{ $safeguardingSummary['notes'] ?: 'Not recorded' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
