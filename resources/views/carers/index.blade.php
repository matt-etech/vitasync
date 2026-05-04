@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Carers'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Carers" description="Manage dedicated carer accounts and review visit allocation readiness.">
        <x-slot:action>
            <a class="btn btn-primary" href="{{ route('carers.create') }}"><i class="fa-solid fa-plus me-1"></i>New carer</a>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Carers">
                <thead class="table-light">
                    <tr>
                        <th>Carer</th>
                        <th>Home</th>
                        <th>Identity</th>
                        <th>Onboarding</th>
                        <th>Login</th>
                        <th>Visits</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($carers as $carer)
                        <tr>
                            <td>
                                <p class="fw-semibold mb-0">{{ $carer->name }}</p>
                                <p class="text-secondary mb-0">{{ $carer->email }}</p>
                                @if ($carer->job_title)
                                    <p class="small text-secondary mb-0">{{ $carer->job_title }}</p>
                                @endif
                            </td>
                            <td>{{ $carer->home?->name ?: 'Unassigned' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $carer->carerProfile ? 'success' : 'warning' }}">
                                    {{ $carer->carerProfile?->legal_name ? 'Started' : 'Missing' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $onboardingStatus = $carer->carerProfile?->status ?: 'onboarding';
                                    $onboardingBadge = [
                                        'onboarding' => 'text-bg-info',
                                        'pending' => 'text-bg-warning',
                                        'approved' => 'text-bg-success',
                                        'declined' => 'text-bg-danger',
                                    ][$onboardingStatus] ?? 'text-bg-secondary';
                                    $criticalFailures = $carer->carerProfile?->criticalValidationFailures() ?? ['Assessment required'];
                                @endphp
                                <span class="badge {{ $onboardingBadge }}">{{ ucfirst($onboardingStatus) }}</span>
                                <span class="badge text-bg-{{ $criticalFailures === [] ? 'success' : 'danger' }}">{{ $criticalFailures === [] ? 'Ready' : 'Blocked' }}</span>
                                @if ($carer->carerProfile?->review_notes)
                                    <p class="text-secondary small mb-0 mt-1">{{ \Illuminate\Support\Str::limit($carer->carerProfile->review_notes, 60) }}</p>
                                @elseif ($criticalFailures !== [])
                                    <p class="text-secondary small mb-0 mt-1">{{ \Illuminate\Support\Str::limit($criticalFailures[0], 60) }}</p>
                                @endif
                            </td>
                            <td>
                                <p class="mb-0">{{ $carer->email }}</p>
                            </td>
                            <td>{{ $carer->assigned_visits_count }}</td>
                            <td><span class="badge text-bg-{{ $carer->is_active ? 'success' : 'secondary' }}">{{ $carer->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-action" href="{{ route('carers.show', $carer) }}"><i class="fa-solid fa-eye"></i>Open</a>
                                    <a class="btn btn-sm btn-action btn-action-primary" href="{{ route('carers.assessments.edit', $carer) }}"><i class="fa-solid fa-list-check"></i>Resume assessment</a>
                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editCarerModal{{ $carer->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                    <form method="POST" action="{{ route('carers.destroy', $carer) }}" data-confirm data-confirm-title="{{ $carer->is_active ? 'Disable carer?' : 'Activate carer?' }}" data-confirm-text="{{ $carer->is_active ? 'Disabled carers cannot be assigned to active visits.' : 'This carer account will become active again.' }}" data-confirm-button="{{ $carer->is_active ? 'Yes, disable' : 'Yes, activate' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-action {{ $carer->is_active ? 'btn-action-danger' : 'btn-action-primary' }}" type="submit"><i class="fa-solid {{ $carer->is_active ? 'fa-ban' : 'fa-check' }}"></i>{{ $carer->is_active ? 'Disable' : 'Activate' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($carers as $editCarer)
        <div class="modal fade" id="editCarerModal{{ $editCarer->id }}" tabindex="-1" aria-labelledby="editCarerModalLabel{{ $editCarer->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('carers.update', $editCarer) }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editCarerModalLabel{{ $editCarer->id }}">Edit {{ $editCarer->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && (int) old('editing_carer_id') === (int) $editCarer->id)
                            @include('components.form-errors')
                        @endif
                        <input type="hidden" name="editing_carer_id" value="{{ $editCarer->id }}">
                        @include('carers.partials.form', ['carer' => $editCarer, 'homes' => $homes, 'passwordRequired' => false, 'submitLabel' => 'Update carer'])
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalId = @json(old('editing_carer_id') ? 'editCarerModal'.old('editing_carer_id') : null);
                const modal = document.getElementById(modalId);

                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endif
@endsection
