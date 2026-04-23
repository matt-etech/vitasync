@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Clients'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Clients" description="Create client records and keep onboarding details current.">
        <x-slot:action>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createClientModal"><i class="fa-solid fa-plus me-1"></i>New client</button>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Clients">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Home</th>
                        <th>Date of birth</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th>Emergency contact</th>
                        <th>Account</th>
                        <th>Onboarding</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $client)
                        <tr>
                            <td>
                                <p class="fw-semibold mb-0">{{ $client->fullName() }}</p>
                                <p class="text-secondary mb-0">{{ $client->address ?: 'No address recorded' }}</p>
                            </td>
                            <td>{{ $client->home->name }}</td>
                            <td>{{ $client->date_of_birth?->format('Y-m-d') ?: 'Not recorded' }}</td>
                            <td>{{ $client->gender ?: 'Not recorded' }}</td>
                            <td>
                                <p class="mb-0">{{ $client->phone ?: 'No phone' }}</p>
                                <p class="text-secondary mb-0">{{ $client->email ?: 'No email' }}</p>
                            </td>
                            <td>
                                <p class="mb-0">{{ $client->emergency_contact_name ?: 'Not recorded' }}</p>
                                <p class="text-secondary mb-0">{{ $client->emergency_contact_phone ?: '' }}</p>
                            </td>
                            <td><span class="badge text-bg-{{ $client->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($client->status) }}</span></td>
                            <td>
                                @php
                                    $onboardingStatus = $client->onboarding_status ?: 'onboarding';
                                    $onboardingBadge = [
                                        'onboarding' => 'text-bg-info',
                                        'pending' => 'text-bg-warning',
                                        'approved' => 'text-bg-success',
                                        'declined' => 'text-bg-danger',
                                    ][$onboardingStatus] ?? 'text-bg-secondary';
                                @endphp
                                <span class="badge {{ $onboardingBadge }}">{{ ucfirst($onboardingStatus) }}</span>
                                @if ($client->review_notes)
                                    <p class="text-secondary small mb-0 mt-1">{{ \Illuminate\Support\Str::limit($client->review_notes, 60) }}</p>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-action" href="{{ route('clients.show', $client) }}"><i class="fa-solid fa-eye"></i>View</a>
                                    <a class="btn btn-sm btn-action btn-action-primary" href="{{ route('clients.assessments.edit', $client) }}"><i class="fa-solid fa-list-check"></i>{{ $client->has_onboarding_assessment ? 'Resume assessment' : 'Assessments' }}</a>
                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editClientModal{{ $client->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}" data-confirm data-confirm-title="{{ $client->status === 'active' ? 'Disable client?' : 'Activate client?' }}" data-confirm-text="{{ $client->status === 'active' ? 'Disabled clients will not appear in operational workflows.' : 'This client will become active again.' }}" data-confirm-button="{{ $client->status === 'active' ? 'Yes, disable' : 'Yes, activate' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-action {{ $client->status === 'active' ? 'btn-action-danger' : 'btn-action-primary' }}" type="submit"><i class="fa-solid {{ $client->status === 'active' ? 'fa-ban' : 'fa-check' }}"></i>{{ $client->status === 'active' ? 'Disable' : 'Activate' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="createClientModal" tabindex="-1" aria-labelledby="createClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('clients.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createClientModalLabel">New client</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('clients.partials.form', ['client' => $newClient, 'homes' => $homes, 'submitLabel' => 'Create client'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($clients as $editClient)
        <div class="modal fade" id="editClientModal{{ $editClient->id }}" tabindex="-1" aria-labelledby="editClientModalLabel{{ $editClient->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('clients.update', $editClient) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editClientModalLabel{{ $editClient->id }}">Edit {{ $editClient->fullName() }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('clients.partials.form', ['client' => $editClient, 'homes' => $homes, 'submitLabel' => 'Update client'])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
