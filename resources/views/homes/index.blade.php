@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Homes'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Homes" description="Create care homes, assign managers, and manage home users.">
        <x-slot:action>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createHomeModal"><i class="fa-solid fa-plus me-1"></i>New home</button>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Homes">
                <thead class="table-light">
                    <tr>
                        <th>Home</th>
                        <th>Manager</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($homes as $home)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($home->logoUrl())
                                        <img src="{{ $home->logoUrl() }}" alt="{{ $home->name }} logo" class="rounded border" width="48" height="48">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light" style="width:48px;height:48px;">
                                            <i class="fa-solid fa-house-medical text-secondary"></i>
                                        </span>
                                    @endif
                                    <div>
                                        <p class="fw-semibold mb-0">{{ $home->name }}</p>
                                        <p class="text-secondary mb-0">{{ $home->city }}, {{ $home->postcode }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $home->manager?->name ?: 'Not assigned' }}</td>
                            <td>{{ $home->users_count }}</td>
                            <td><span class="badge text-bg-{{ $home->status === 'active' ? 'success' : ($home->status === 'onboarding' ? 'warning' : 'secondary') }}">{{ ucfirst($home->status) }}</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-action btn-action-primary" href="{{ route('homes.users.index', $home) }}"><i class="fa-solid fa-users"></i>Users</a>
                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editHomeModal{{ $home->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                    <form method="POST" action="{{ route('homes.destroy', $home) }}" data-confirm data-confirm-title="{{ $home->status === 'active' ? 'Disable home?' : 'Activate home?' }}" data-confirm-text="{{ $home->status === 'active' ? 'Disabled homes will not appear in assignment lists.' : 'This home will become available for assignment again.' }}" data-confirm-button="{{ $home->status === 'active' ? 'Yes, disable' : 'Yes, activate' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-action {{ $home->status === 'active' ? 'btn-action-danger' : 'btn-action-primary' }}" type="submit"><i class="fa-solid {{ $home->status === 'active' ? 'fa-ban' : 'fa-check' }}"></i>{{ $home->status === 'active' ? 'Disable' : 'Activate' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="createHomeModal" tabindex="-1" aria-labelledby="createHomeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('homes.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createHomeModalLabel">New home</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('homes.partials.form', ['home' => $newHome, 'managers' => $managers, 'submitLabel' => 'Create home'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($homes as $editHome)
        <div class="modal fade" id="editHomeModal{{ $editHome->id }}" tabindex="-1" aria-labelledby="editHomeModalLabel{{ $editHome->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('homes.update', $editHome) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editHomeModalLabel{{ $editHome->id }}">Edit {{ $editHome->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('homes.partials.form', ['home' => $editHome, 'managers' => $managers, 'submitLabel' => 'Update home'])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
