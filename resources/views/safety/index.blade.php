@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Safety & Compliance'],
    ]" />
@endsection

@section('content')
    @php
        $clientOptions = $clients->map(fn ($client) => ['id' => $client->id, 'label' => $client->fullName().' - '.$client->home->name]);
        $input = fn (string $name, string $label, string $type = 'text', mixed $value = null): string => '<div class="col-md-6"><label class="form-label" for="'.$name.'">'.$label.'</label><input class="form-control focus-ring-brand" id="'.$name.'" name="'.$name.'" type="'.$type.'" value="'.e($value ?? old($name)).'"></div>';
        $textarea = fn (string $name, string $label): string => '<div class="col-12"><label class="form-label" for="'.$name.'">'.$label.'</label><textarea class="form-control focus-ring-brand" id="'.$name.'" name="'.$name.'" rows="3">'.e(old($name)).'</textarea></div>';
        $select = function (string $name, string $label, iterable $options, mixed $selected = null): string {
            $html = '<div class="col-md-6"><label class="form-label" for="'.$name.'">'.$label.'</label><select class="form-select focus-ring-brand" id="'.$name.'" name="'.$name.'"><option value="">Select '.$label.'</option>';
            foreach ($options as $key => $value) {
                $optionValue = is_array($value) ? $value['id'] : $value;
                $optionLabel = is_array($value) ? $value['label'] : $value;
                $html .= '<option value="'.e($optionValue).'" '.(old($name, $selected) == $optionValue ? 'selected' : '').'>'.e($optionLabel).'</option>';
            }
            return $html.'</select></div>';
        };
        $statusBadge = fn (?string $status): string => [
            'open' => 'text-bg-warning',
            'investigating' => 'text-bg-info',
            'managed' => 'text-bg-success',
            'closed' => 'text-bg-secondary',
            'active' => 'text-bg-success',
            'paused' => 'text-bg-warning',
            'stopped' => 'text-bg-secondary',
            'referred' => 'text-bg-info',
            'monitoring' => 'text-bg-primary',
        ][$status] ?? 'text-bg-light';
        $riskBadge = fn (?string $risk): string => [
            'Critical' => 'text-bg-danger',
            'High' => 'text-bg-warning',
            'Medium' => 'text-bg-info',
            'Low' => 'text-bg-success',
        ][$risk] ?? 'text-bg-secondary';
    @endphp

    <x-page-header title="Safety & Compliance" description="Operate ongoing risk, consent/capacity, eMAR, incident, and safeguarding workflows with audit evidence.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#incidentModal"><i class="fa-solid fa-triangle-exclamation me-1"></i>Record incident</button>
                <button class="btn btn-action btn-action-primary" type="button" data-bs-toggle="modal" data-bs-target="#medAdminModal"><i class="fa-solid fa-pills"></i>Record MAR</button>
            </div>
        </x-slot:action>
    </x-page-header>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><p class="section-kicker mb-2">Open incidents</p><h2 class="h4 mb-0">{{ $incidents->where('status', '!=', 'closed')->count() }}</h2></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><p class="section-kicker mb-2">Safeguarding</p><h2 class="h4 mb-0">{{ $safeguardingCases->where('status', '!=', 'closed')->count() }}</h2></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><p class="section-kicker mb-2">Active meds</p><h2 class="h4 mb-0">{{ $medications->where('status', 'active')->count() }}</h2></div></div></div>
        <div class="col-md-3"><div class="card h-100"><div class="card-body"><p class="section-kicker mb-2">Critical risks</p><h2 class="h4 mb-0">{{ $riskReviews->where('risk_level', 'Critical')->where('status', '!=', 'closed')->count() }}</h2></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <ul class="nav nav-tabs" role="tablist">
                @foreach (['risk' => 'Risk', 'capacity' => 'Consent / Capacity', 'medication' => 'Medication', 'incidents' => 'Incidents', 'safeguarding' => 'Safeguarding'] as $key => $label)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $key }}-pane" type="button" role="tab">{{ $label }}</button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content pt-4">
                <div class="tab-pane fade show active" id="risk-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div><p class="section-kicker mb-2">Risk</p><h2 class="h4 fw-bold mb-0">Ongoing risk reviews</h2></div>
                        <button class="btn btn-action btn-action-primary" type="button" data-bs-toggle="modal" data-bs-target="#riskModal"><i class="fa-solid fa-plus"></i>Record risk</button>
                    </div>
                    @include('safety.partials.simple-table', ['rows' => $riskReviews, 'columns' => ['client' => 'Client', 'risk_domain' => 'Domain', 'risk_level' => 'Level', 'status' => 'Status', 'review_date' => 'Review']])
                </div>

                <div class="tab-pane fade" id="capacity-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
                        <div><p class="section-kicker mb-2">Consent and MCA</p><h2 class="h4 fw-bold mb-0">Decision-specific evidence</h2></div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-action btn-action-primary" type="button" data-bs-toggle="modal" data-bs-target="#capacityModal"><i class="fa-solid fa-brain"></i>Capacity review</button>
                            <button class="btn btn-action btn-action-primary" type="button" data-bs-toggle="modal" data-bs-target="#consentModal"><i class="fa-solid fa-file-signature"></i>Consent record</button>
                        </div>
                    </div>
                    @include('safety.partials.simple-table', ['rows' => $capacityReviews, 'columns' => ['client' => 'Client', 'decision_type' => 'Decision', 'capacity_outcome' => 'Outcome', 'best_interest_status' => 'Best interest', 'review_date' => 'Review']])
                    <hr>
                    @include('safety.partials.simple-table', ['rows' => $consentRecords, 'columns' => ['client' => 'Client', 'consent_type' => 'Consent', 'decision' => 'Decision', 'given_by' => 'Given by', 'recorded_at' => 'Recorded']])
                </div>

                <div class="tab-pane fade" id="medication-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start mb-3">
                        <div><p class="section-kicker mb-2">eMAR</p><h2 class="h4 fw-bold mb-0">Medication and administration</h2></div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-action btn-action-primary" type="button" data-bs-toggle="modal" data-bs-target="#medicationModal"><i class="fa-solid fa-plus"></i>Add medication</button>
                            <button class="btn btn-action btn-action-primary" type="button" data-bs-toggle="modal" data-bs-target="#medAdminModal"><i class="fa-solid fa-check"></i>Record administration</button>
                        </div>
                    </div>
                    @include('safety.partials.simple-table', ['rows' => $medications, 'columns' => ['client' => 'Client', 'name' => 'Medication', 'dose' => 'Dose', 'support_level' => 'Support', 'status' => 'Status']])
                    <hr>
                    @include('safety.partials.simple-table', ['rows' => $medicationAdministrations, 'columns' => ['client' => 'Client', 'medication' => 'Medication', 'outcome' => 'Outcome', 'administered_at' => 'Time', 'notes' => 'Notes']])
                </div>

                <div class="tab-pane fade" id="incidents-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div><p class="section-kicker mb-2">Incidents</p><h2 class="h4 fw-bold mb-0">Incident log and escalation</h2></div>
                        <button class="btn btn-action btn-action-primary" type="button" data-bs-toggle="modal" data-bs-target="#incidentModal"><i class="fa-solid fa-plus"></i>Record incident</button>
                    </div>
                    @include('safety.partials.simple-table', ['rows' => $incidents, 'columns' => ['client' => 'Client', 'category' => 'Category', 'severity' => 'Severity', 'status' => 'Status', 'occurred_at' => 'Occurred']])
                </div>

                <div class="tab-pane fade" id="safeguarding-pane" role="tabpanel" tabindex="0">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div><p class="section-kicker mb-2">Safeguarding</p><h2 class="h4 fw-bold mb-0">Safeguarding cases</h2></div>
                        <button class="btn btn-action btn-action-primary" type="button" data-bs-toggle="modal" data-bs-target="#safeguardingModal"><i class="fa-solid fa-shield-heart"></i>Open case</button>
                    </div>
                    @include('safety.partials.simple-table', ['rows' => $safeguardingCases, 'columns' => ['client' => 'Client', 'concern_type' => 'Concern', 'risk_level' => 'Risk', 'status' => 'Status', 'opened_at' => 'Opened']])
                </div>
            </div>
        </div>
    </div>

    @include('safety.partials.modals')
@endsection
