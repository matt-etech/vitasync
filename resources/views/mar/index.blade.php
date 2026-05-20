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

    <div class="card shadow-sm mb-4">
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
@endsection
