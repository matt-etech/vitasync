@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Users'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Users" description="Create accounts and assign operational roles.">
        <x-slot:action>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="fa-solid fa-plus me-1"></i>New user</button>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Users">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    <th>Home</th>
                    <th>Roles</th>
                    <th>Direct permissions</th>
                    <th>Status</th>
                    <th class="no-export">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <p class="fw-semibold mb-0">{{ $user->name }}</p>
                            <p class="text-secondary mb-0">{{ $user->email }}</p>
                            @if ($user->job_title)
                                <p class="small text-secondary mb-0">{{ $user->job_title }}</p>
                            @endif
                        </td>
                        <td>
                            @if ($user->home)
                                <a href="{{ route('homes.users.index', $user->home) }}">{{ $user->home->name }}</a>
                            @else
                                <span class="text-secondary">Platform-wide</span>
                            @endif
                        </td>
                        <td>
                            @forelse ($user->roles as $role)
                                <span class="badge text-bg-light border me-1">{{ $role->name }}</span>
                            @empty
                                <span class="text-secondary">No roles assigned</span>
                            @endforelse
                        </td>
                        <td>{{ $user->permissions->count() }}</td>
                        <td><span class="badge text-bg-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm data-confirm-title="{{ $user->is_active ? 'Disable user?' : 'Activate user?' }}" data-confirm-text="{{ $user->is_active ? 'Disabled users cannot be used for active operations.' : 'This user will become active again.' }}" data-confirm-button="{{ $user->is_active ? 'Yes, disable' : 'Yes, activate' }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-action {{ $user->is_active ? 'btn-action-danger' : 'btn-action-primary' }}" type="submit"><i class="fa-solid {{ $user->is_active ? 'fa-ban' : 'fa-check' }}"></i>{{ $user->is_active ? 'Disable' : 'Activate' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createUserModalLabel">New user</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('users.partials.form', ['user' => $newUser, 'homes' => $homes, 'roles' => $roles, 'permissions' => $permissions, 'selectedRoles' => [], 'selectedPermissions' => [], 'passwordRequired' => true, 'submitLabel' => 'Create user'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($users as $editUser)
        <div class="modal fade" id="editUserModal{{ $editUser->id }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $editUser->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('users.update', $editUser) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editUserModalLabel{{ $editUser->id }}">Edit {{ $editUser->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('users.partials.form', ['user' => $editUser, 'homes' => $homes, 'roles' => $roles, 'permissions' => $permissions, 'selectedRoles' => $editUser->roles->pluck('id')->all(), 'selectedPermissions' => $editUser->permissions->pluck('id')->all(), 'passwordRequired' => false, 'submitLabel' => 'Update user'])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
