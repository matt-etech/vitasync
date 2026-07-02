@extends('layouts.app')

@php
    $latestMedicationAdministration = static fn ($visit) => $visit->medicationAdministrations->first();
@endphp

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'MAR'],
    ]" />
@endsection

@section('content')
    <x-page-header title="MAR" description="Review medication instructions and actual medication administrations." />

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Medication Visits</p>
                    <p class="display-6 fw-bold mb-0">{{ $visits->count() }}</p>
                    <p class="text-secondary mb-0">Visits with medication support in the care plan.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Administrations</p>
                    <p class="display-6 fw-bold mb-0">{{ $administrations->count() }}</p>
                    <p class="text-secondary mb-0">Medication actually administered or marked refused/missed.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="section-kicker mb-2">Attention Needed</p>
                    <p class="display-6 fw-bold mb-0">{{ $administrations->whereIn('outcome', ['missed', 'refused'])->count() }}</p>
                    <p class="text-secondary mb-0">Refused or missed medication administrations.</p>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="marTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="medicationSupportVisitsTabButton" data-bs-toggle="tab" data-bs-target="#medicationSupportVisitsTab" type="button" role="tab" aria-controls="medicationSupportVisitsTab" aria-selected="true">
                Medication support visits
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="medicationAdministrationsTabButton" data-bs-toggle="tab" data-bs-target="#medicationAdministrationsTab" type="button" role="tab" aria-controls="medicationAdministrationsTab" aria-selected="false">
                Medication administration
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="medicationSupportVisitsTab" role="tabpanel" aria-labelledby="medicationSupportVisitsTabButton" tabindex="0">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">Current MAR</p>
                            <h2 class="h4 fw-bold mb-0">Medication support visits</h2>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="MAR visits">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Visit</th>
                                    <th>Carer</th>
                                    <th>Medication instruction</th>
                                    <th>Latest administration</th>
                                    <th class="no-export">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($visits as $visit)
                                    @php($latestAdministration = $latestMedicationAdministration($visit))
                                    <tr>
                                        <td>
                                            <p class="fw-semibold mb-0">{{ $visit->client->fullName() }}</p>
                                            <p class="text-secondary mb-0">{{ $visit->client->home?->name ?: 'No home recorded' }}</p>
                                        </td>
                                        <td>
                                            <p class="fw-semibold mb-0">{{ $visit->title }}</p>
                                            <p class="text-secondary mb-0">{{ $visit->durationLabel() }}</p>
                                        </td>
                                        <td>{{ $visit->assignedWorker?->name ?: 'Unassigned' }}</td>
                                        <td>
                                            <p class="mb-1"><strong>{{ $visit->carePlan?->medication_support_level ?: 'Medication support' }}</strong></p>
                                            <p class="text-secondary mb-0">{{ $visit->carePlan?->medication_support ?: 'Follow the current care plan and MAR instructions.' }}</p>
                                        </td>
                                        <td>
                                            @if ($latestAdministration)
                                                <span class="badge text-bg-{{ $latestAdministration->outcome === 'administered' ? 'success' : ($latestAdministration->outcome === 'refused' ? 'warning' : 'danger') }}">{{ str($latestAdministration->outcome)->headline() }}</span>
                                                <p class="text-secondary mb-0 mt-1">{{ $latestAdministration->administered_at->format('d/m/Y H:i') }}</p>
                                                @if ($latestAdministration->notes)
                                                    <p class="small mb-0">{{ $latestAdministration->notes }}</p>
                                                @endif
                                            @else
                                                <span class="badge text-bg-light border">Not administered yet</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addAdministrationModal{{ $visit->id }}">
                                                <i class="fa-solid fa-plus me-1"></i>Add administration
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="medicationAdministrationsTab" role="tabpanel" aria-labelledby="medicationAdministrationsTabButton" tabindex="0">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">History</p>
                            <h2 class="h4 fw-bold mb-0">Medication administrations</h2>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" data-vitasync-datatable data-export-title="MAR history">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Client</th>
                                    <th>Carer</th>
                                    <th>Administration</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($administrations as $administration)
                                    <tr>
                                        <td>{{ $administration->administered_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $administration->client?->fullName() ?: 'Client unavailable' }}</td>
                                        <td>{{ $administration->carer?->name ?: 'Carer unavailable' }}</td>
                                        <td>
                                            <span class="badge text-bg-{{ $administration->outcome === 'administered' ? 'success' : ($administration->outcome === 'refused' ? 'warning' : 'danger') }}">{{ str($administration->outcome)->headline() }}</span>
                                            <p class="text-secondary mb-0">{{ $administration->medication_name }}</p>
                                        </td>
                                        <td>{{ $administration->notes ?: 'No note recorded' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($visits as $visit)
        <div class="modal fade" id="addAdministrationModal{{ $visit->id }}" tabindex="-1" aria-labelledby="addAdministrationModalLabel{{ $visit->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('mar.medication-administrations.store') }}">
                    @csrf
                    <input type="hidden" name="visit_id" value="{{ $visit->id }}">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="addAdministrationModalLabel{{ $visit->id }}">Add administration</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border">
                            <p class="fw-semibold mb-1">{{ $visit->client->fullName() }} - {{ $visit->title }}</p>
                            <p class="text-secondary mb-0">{{ $visit->durationLabel() }} · {{ $visit->assignedWorker?->name ?: 'Unassigned carer' }}</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="medication_name_{{ $visit->id }}">Medication</label>
                                <input class="form-control" id="medication_name_{{ $visit->id }}" name="medication_name" value="{{ $visit->carePlan?->medication_support_level ?: 'Medication support' }}" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="outcome_{{ $visit->id }}">Outcome</label>
                                <select class="form-select" id="outcome_{{ $visit->id }}" name="outcome" required>
                                    <option value="administered">Administered</option>
                                    <option value="refused">Refused</option>
                                    <option value="missed">Missed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="dose_{{ $visit->id }}">Dose</label>
                                <input class="form-control" id="dose_{{ $visit->id }}" name="dose" maxlength="120">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="route_{{ $visit->id }}">Route</label>
                                <input class="form-control" id="route_{{ $visit->id }}" name="route" maxlength="120" placeholder="Oral, topical, other">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="notes_{{ $visit->id }}">Administration note</label>
                                <textarea class="form-control" id="notes_{{ $visit->id }}" name="notes" rows="4" placeholder="Dose given, reason refused, or why missed"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-1"></i>Add administration</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
