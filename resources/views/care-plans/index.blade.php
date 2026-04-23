@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Care Plans'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Care Plans" description="Create and manage client care goals, support needs, risks, and review schedules.">
        <x-slot:action>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createCarePlanModal"><i class="fa-solid fa-plus me-1"></i>New care plan</button>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Care Plans">
                <thead class="table-light">
                    <tr>
                        <th>Care plan</th>
                        <th>Client</th>
                        <th>Home</th>
                        <th>Level</th>
                        <th>Risk</th>
                        <th>Visit frequency</th>
                        <th>Review</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($carePlans as $plan)
                        <tr>
                            <td>
                                <p class="fw-semibold mb-0">{{ $plan->title }}</p>
                                <p class="text-secondary mb-0">{{ $plan->plan_type }} plan from {{ $plan->start_date?->format('Y-m-d') }}</p>
                            </td>
                            <td>{{ $plan->client->fullName() }}</td>
                            <td>{{ $plan->home->name }}</td>
                            <td>{{ $plan->care_level ?: 'Not set' }}</td>
                            <td>{{ $plan->risk_level ?: 'Not set' }}</td>
                            <td>{{ $plan->visit_frequency ?: 'Not set' }}</td>
                            <td>
                                <p class="mb-0">{{ $plan->review_date?->format('Y-m-d') ?: 'Not scheduled' }}</p>
                                <p class="text-secondary mb-0">{{ $plan->review_frequency ?: '' }}</p>
                            </td>
                            <td><span class="badge text-bg-{{ $plan->status === 'active' ? 'success' : ($plan->status === 'draft' ? 'warning' : 'secondary') }}">{{ ucfirst($plan->status) }}</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editCarePlanModal{{ $plan->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                    <form method="POST" action="{{ route('care-plans.destroy', $plan) }}" data-confirm data-confirm-title="{{ $plan->status === 'inactive' ? 'Activate care plan?' : 'Disable care plan?' }}" data-confirm-text="{{ $plan->status === 'inactive' ? 'This care plan will become active again.' : 'Disabled care plans should not be used for active care delivery.' }}" data-confirm-button="{{ $plan->status === 'inactive' ? 'Yes, activate' : 'Yes, disable' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-action {{ $plan->status === 'inactive' ? 'btn-action-primary' : 'btn-action-danger' }}" type="submit"><i class="fa-solid {{ $plan->status === 'inactive' ? 'fa-check' : 'fa-ban' }}"></i>{{ $plan->status === 'inactive' ? 'Activate' : 'Disable' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="createCarePlanModal" tabindex="-1" aria-labelledby="createCarePlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('care-plans.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createCarePlanModalLabel">New care plan</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('care-plans.partials.form', ['carePlan' => $carePlan, 'clients' => $clients, 'submitLabel' => 'Create care plan', 'formId' => 'create'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($carePlans as $editPlan)
        <div class="modal fade" id="editCarePlanModal{{ $editPlan->id }}" tabindex="-1" aria-labelledby="editCarePlanModalLabel{{ $editPlan->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('care-plans.update', $editPlan) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editCarePlanModalLabel{{ $editPlan->id }}">Edit {{ $editPlan->title }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('care-plans.partials.form', ['carePlan' => $editPlan, 'clients' => $clients, 'submitLabel' => 'Update care plan', 'formId' => 'edit_'.$editPlan->id])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
