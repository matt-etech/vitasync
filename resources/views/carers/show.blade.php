@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Carers', 'url' => route('carers.index')],
        ['label' => $carer->name],
    ]" />
@endsection

@section('content')
    <x-page-header title="{{ $carer->name }}" description="Review carer profile details and the visits currently assigned to this worker.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('carers.index') }}"><i class="fa-solid fa-arrow-left me-1"></i>Carers</a>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#editCarerModal"><i class="fa-solid fa-pen me-1"></i>Edit carer</button>
            </div>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <ul class="nav nav-tabs" id="carerShowTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="carer-profile-tab" data-bs-toggle="tab" data-bs-target="#carer-profile-pane" type="button" role="tab" aria-controls="carer-profile-pane" aria-selected="true">Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="carer-visits-tab" data-bs-toggle="tab" data-bs-target="#carer-visits-pane" type="button" role="tab" aria-controls="carer-visits-pane" aria-selected="false">Visits</button>
                </li>
            </ul>

            <div class="tab-content pt-4" id="carerShowTabsContent">
                <div class="tab-pane fade show active" id="carer-profile-pane" role="tabpanel" aria-labelledby="carer-profile-tab" tabindex="0">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="border rounded p-4 h-100">
                                <p class="section-kicker mb-2">Carer</p>
                                <h2 class="h4 fw-bold mb-3">{{ $carer->name }}</h2>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge text-bg-{{ $carer->is_active ? 'success' : 'secondary' }}">{{ $carer->is_active ? 'Active' : 'Inactive' }}</span>
                                    <span class="badge text-bg-light border">Carer role</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="border rounded p-4 h-100">
                                <dl class="row mb-0 g-3">
                                    <dt class="col-md-4 text-secondary">Email</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->email }}</dd>
                                    <dt class="col-md-4 text-secondary">Home</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->home?->name ?: 'Platform-wide' }}</dd>
                                    <dt class="col-md-4 text-secondary">Job title</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->job_title ?: 'Carer' }}</dd>
                                    <dt class="col-md-4 text-secondary">Phone</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->phone ?: 'Not recorded' }}</dd>
                                    <dt class="col-md-4 text-secondary">Assigned visits</dt>
                                    <dd class="col-md-8 mb-0">{{ $carer->assignedVisits->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="carer-visits-pane" role="tabpanel" aria-labelledby="carer-visits-tab" tabindex="0">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-between mb-3">
                        <div>
                            <p class="section-kicker mb-2">Visit Allocation</p>
                            <h2 class="h4 fw-bold mb-0">Assigned visits</h2>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            <span class="text-secondary fw-semibold">{{ $carer->assignedVisits->count() }} total visits</span>
                            <a class="btn btn-sm btn-action btn-action-primary" href="{{ route('visits.index') }}"><i class="fa-solid fa-calendar-check"></i>Manage visits</a>
                        </div>
                    </div>

                    @if ($carer->assignedVisits->isEmpty())
                        <div class="alert alert-info mb-0">No visits are assigned to this carer yet.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Visit</th>
                                        <th>Client</th>
                                        <th>Home</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th>EVV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($carer->assignedVisits as $visit)
                                        <tr>
                                            <td>
                                                <p class="fw-bold mb-0">{{ $visit->title }}</p>
                                                <p class="text-secondary mb-0">{{ $visit->carePlan?->title ?: 'No care plan linked' }}</p>
                                            </td>
                                            <td>
                                                <a href="{{ route('clients.show', $visit->client) }}">{{ $visit->client->fullName() }}</a>
                                            </td>
                                            <td>{{ $visit->home->name }}</td>
                                            <td>{{ $visit->durationLabel() }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $visit->status === 'completed' ? 'success' : ($visit->status === 'in_progress' ? 'warning' : ($visit->status === 'cancelled' ? 'secondary' : 'info')) }}">
                                                    {{ str($visit->status)->replace('_', ' ')->headline() }}
                                                </span>
                                            </td>
                                            <td>
                                                <p class="mb-0">{{ $visit->check_in_at?->format('Y-m-d H:i') ?: 'No check-in' }}</p>
                                                <p class="text-secondary mb-0">{{ $visit->check_out_at?->format('Y-m-d H:i') ?: 'No check-out' }}</p>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCarerModal" tabindex="-1" aria-labelledby="editCarerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('carers.update', $carer) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h2 class="modal-title h5" id="editCarerModalLabel">Edit {{ $carer->name }}</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('carers.partials.form', ['carer' => $carer, 'homes' => $homes, 'passwordRequired' => false, 'submitLabel' => 'Update carer'])
                </div>
            </form>
        </div>
    </div>
@endsection
