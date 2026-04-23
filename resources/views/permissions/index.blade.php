@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Permissions'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Permissions" description="Define discrete capabilities for role-based access.">
        <x-slot:action>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createPermissionModal"><i class="fa-solid fa-plus me-1"></i>New permission</button>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Permissions">
            <thead class="table-light">
                <tr>
                    <th>Permission</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th class="no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                    <tr>
                        <td>
                            <p class="fw-semibold mb-0">{{ $permission->name }}</p>
                            <p class="text-secondary mb-0">{{ $permission->description ?: 'No description' }}</p>
                        </td>
                        <td>{{ $permission->roles_count }}</td>
                        <td><span class="badge text-bg-{{ $permission->is_active ? 'success' : 'secondary' }}">{{ $permission->is_active ? 'Active' : 'Disabled' }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editPermissionModal{{ $permission->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                <form method="POST" action="{{ route('permissions.destroy', $permission) }}" data-confirm data-confirm-title="{{ $permission->is_active ? 'Disable permission?' : 'Activate permission?' }}" data-confirm-text="{{ $permission->is_active ? 'Disabled permissions will not appear in assignment lists.' : 'This permission will become available for assignment again.' }}" data-confirm-button="{{ $permission->is_active ? 'Yes, disable' : 'Yes, activate' }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-action {{ $permission->is_active ? 'btn-action-danger' : 'btn-action-primary' }}" type="submit"><i class="fa-solid {{ $permission->is_active ? 'fa-ban' : 'fa-check' }}"></i>{{ $permission->is_active ? 'Disable' : 'Activate' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div class="modal fade" id="createPermissionModal" tabindex="-1" aria-labelledby="createPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('permissions.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createPermissionModalLabel">New permission</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('permissions.partials.form', ['permission' => new \App\Models\Permission(['is_active' => true]), 'submitLabel' => 'Create permission'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($permissions as $editPermission)
        <div class="modal fade" id="editPermissionModal{{ $editPermission->id }}" tabindex="-1" aria-labelledby="editPermissionModalLabel{{ $editPermission->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('permissions.update', $editPermission) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editPermissionModalLabel{{ $editPermission->id }}">Edit {{ $editPermission->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('permissions.partials.form', ['permission' => $editPermission, 'submitLabel' => 'Update permission'])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
