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
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createCarerModal"><i class="fa-solid fa-plus me-1"></i>New carer</button>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Carers">
                <thead class="table-light">
                    <tr>
                        <th>Carer</th>
                        <th>Home</th>
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
                            <td>{{ $carer->home?->name ?: 'Platform-wide' }}</td>
                            <td>{{ $carer->assigned_visits_count }}</td>
                            <td><span class="badge text-bg-{{ $carer->is_active ? 'success' : 'secondary' }}">{{ $carer->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-action" href="{{ route('carers.show', $carer) }}"><i class="fa-solid fa-eye"></i>Open</a>
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

    <div class="modal fade" id="createCarerModal" tabindex="-1" aria-labelledby="createCarerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('carers.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createCarerModalLabel">New carer</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('carers.partials.form', ['carer' => $newCarer, 'homes' => $homes, 'passwordRequired' => true, 'submitLabel' => 'Create carer'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($carers as $editCarer)
        <div class="modal fade" id="editCarerModal{{ $editCarer->id }}" tabindex="-1" aria-labelledby="editCarerModalLabel{{ $editCarer->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('carers.update', $editCarer) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editCarerModalLabel{{ $editCarer->id }}">Edit {{ $editCarer->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('carers.partials.form', ['carer' => $editCarer, 'homes' => $homes, 'passwordRequired' => false, 'submitLabel' => 'Update carer'])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
