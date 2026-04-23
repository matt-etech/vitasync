@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Roles'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Roles" description="Create responsibilities and connect them to permissions.">
        <x-slot:action>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createRoleModal"><i class="fa-solid fa-plus me-1"></i>New role</button>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Roles">
            <thead class="table-light">
                <tr>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th>Status</th>
                    <th class="no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td>
                            <p class="fw-semibold mb-0">{{ $role->name }}</p>
                            <p class="text-secondary mb-0">{{ $role->description ?: 'No description' }}</p>
                        </td>
                        <td>{{ $role->permissions_count }}</td>
                        <td>{{ $role->users_count }}</td>
                        <td><span class="badge text-bg-{{ $role->is_active ? 'success' : 'secondary' }}">{{ $role->is_active ? 'Active' : 'Disabled' }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editRoleModal{{ $role->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" data-confirm data-confirm-title="{{ $role->is_active ? 'Disable role?' : 'Activate role?' }}" data-confirm-text="{{ $role->is_active ? 'Disabled roles will not appear in role assignment lists.' : 'This role will become available for assignment again.' }}" data-confirm-button="{{ $role->is_active ? 'Yes, disable' : 'Yes, activate' }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-action {{ $role->is_active ? 'btn-action-danger' : 'btn-action-primary' }}" type="submit"><i class="fa-solid {{ $role->is_active ? 'fa-ban' : 'fa-check' }}"></i>{{ $role->is_active ? 'Disable' : 'Activate' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('roles.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createRoleModalLabel">New role</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('roles.partials.form', ['role' => new \App\Models\Role(['is_active' => true]), 'selectedPermissions' => [], 'submitLabel' => 'Create role'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($roles as $editRole)
        <div class="modal fade" id="editRoleModal{{ $editRole->id }}" tabindex="-1" aria-labelledby="editRoleModalLabel{{ $editRole->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('roles.update', $editRole) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editRoleModalLabel{{ $editRole->id }}">Edit {{ $editRole->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('roles.partials.form', ['role' => $editRole, 'selectedPermissions' => $editRole->permissions->pluck('id')->all(), 'submitLabel' => 'Update role'])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
