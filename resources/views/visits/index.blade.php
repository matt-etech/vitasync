@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Visits'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Visits" description="Book, review, and update client visits before EVV execution begins.">
        <x-slot:action>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createVisitModal"><i class="fa-solid fa-plus me-1"></i>Book visit</button>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Visits">
                <thead class="table-light">
                    <tr>
                        <th>Visit</th>
                        <th>Client</th>
                        <th>Home</th>
                        <th>Schedule</th>
                        <th>Worker</th>
                        <th>Care plan</th>
                        <th>Status</th>
                        <th>EVV</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($visits as $scheduledVisit)
                        <tr>
                            <td>
                                <p class="fw-semibold mb-0">{{ $scheduledVisit->title }}</p>
                                <p class="text-secondary mb-0">{{ $scheduledVisit->notes ?: 'No notes recorded' }}</p>
                            </td>
                            <td>{{ $scheduledVisit->client->fullName() }}</td>
                            <td>{{ $scheduledVisit->home->name }}</td>
                            <td>{{ $scheduledVisit->durationLabel() }}</td>
                            <td>{{ $scheduledVisit->assignedWorker?->name ?: 'Unassigned' }}</td>
                            <td>{{ $scheduledVisit->carePlan?->title ?: 'Not linked' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $scheduledVisit->status === 'completed' ? 'success' : ($scheduledVisit->status === 'in_progress' ? 'warning' : ($scheduledVisit->status === 'cancelled' ? 'secondary' : 'info')) }}">
                                    {{ str($scheduledVisit->status)->replace('_', ' ')->headline() }}
                                </span>
                            </td>
                            <td>
                                <p class="mb-0">{{ $scheduledVisit->check_in_at?->format('Y-m-d H:i') ?: 'No check-in' }}</p>
                                <p class="text-secondary mb-0">{{ $scheduledVisit->check_out_at?->format('Y-m-d H:i') ?: 'No check-out' }}</p>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-action" href="{{ route('clients.show', $scheduledVisit->client) }}"><i class="fa-solid fa-eye"></i>Client</a>
                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editVisitModal{{ $scheduledVisit->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                    <form method="POST" action="{{ route('visits.destroy', $scheduledVisit) }}" data-confirm data-confirm-title="{{ $scheduledVisit->status === 'cancelled' ? 'Restore visit?' : 'Cancel visit?' }}" data-confirm-text="{{ $scheduledVisit->status === 'cancelled' ? 'This visit will return to scheduled status.' : 'Cancelled visits remain visible for audit but should not be delivered.' }}" data-confirm-button="{{ $scheduledVisit->status === 'cancelled' ? 'Restore visit' : 'Cancel visit' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-action {{ $scheduledVisit->status === 'cancelled' ? 'btn-action-primary' : 'btn-action-danger' }}" type="submit"><i class="fa-solid {{ $scheduledVisit->status === 'cancelled' ? 'fa-check' : 'fa-ban' }}"></i>{{ $scheduledVisit->status === 'cancelled' ? 'Restore' : 'Cancel' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="createVisitModal" tabindex="-1" aria-labelledby="createVisitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('visits.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createVisitModalLabel">Book visit</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('visits.partials.form', ['visit' => $visit, 'clients' => $clients, 'workers' => $workers, 'submitLabel' => 'Book visit', 'formId' => 'create'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($visits as $editVisit)
        <div class="modal fade" id="editVisitModal{{ $editVisit->id }}" tabindex="-1" aria-labelledby="editVisitModalLabel{{ $editVisit->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('visits.update', $editVisit) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editVisitModalLabel{{ $editVisit->id }}">Edit {{ $editVisit->title }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('visits.partials.form', ['visit' => $editVisit, 'clients' => $clients, 'workers' => $workers, 'submitLabel' => 'Update visit', 'formId' => 'edit_'.$editVisit->id])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
