@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Homes', 'url' => route('homes.index')],
        ['label' => $home->name],
        ['label' => 'Users'],
    ]" />
@endsection

@section('content')
    <x-page-header title="{{ $home->name }} Users" description="Manage users who belong to this home and assign their access.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('homes.index') }}"><i class="fa-solid fa-arrow-left me-1"></i>Homes</a>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createHomeUserModal"><i class="fa-solid fa-plus me-1"></i>New home user</button>
            </div>
        </x-slot:action>
    </x-page-header>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="{{ $home->name }} Users">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Role</th>
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
                                    @if (auth()->user()->hasPermission('users.impersonate') && $user->is_active && (int) auth()->id() !== (int) $user->id)
                                        <form method="POST" action="{{ route('homes.users.impersonate', [$home, $user]) }}" data-confirm data-confirm-title="Impersonate home user?" data-confirm-text="You will temporarily act as {{ $user->name }} and see the system with their access." data-confirm-button="Yes, impersonate">
                                            @csrf
                                            <button class="btn btn-sm btn-action btn-action-primary" type="submit"><i class="fa-solid fa-user-shield"></i>Impersonate</button>
                                        </form>
                                    @endif
                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editHomeUserModal{{ $user->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                    <form method="POST" action="{{ route('homes.users.destroy', [$home, $user]) }}" data-confirm data-confirm-title="{{ $user->is_active ? 'Disable home user?' : 'Activate home user?' }}" data-confirm-text="{{ $user->is_active ? 'Disabled users cannot be used for active operations.' : 'This user will become active again.' }}" data-confirm-button="{{ $user->is_active ? 'Yes, disable' : 'Yes, activate' }}">
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

    <div class="modal fade" id="createHomeUserModal" tabindex="-1" aria-labelledby="createHomeUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('homes.users.store', $home) }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createHomeUserModalLabel">New home user</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @include('homes.users.partials.form', ['home' => $home, 'user' => $newUser, 'roles' => $roles, 'permissions' => $permissions, 'selectedRoles' => [], 'selectedPermissions' => [], 'passwordRequired' => true, 'submitLabel' => 'Create home user'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($users as $editUser)
        <div class="modal fade" id="editHomeUserModal{{ $editUser->id }}" tabindex="-1" aria-labelledby="editHomeUserModalLabel{{ $editUser->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('homes.users.update', [$home, $editUser]) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editHomeUserModalLabel{{ $editUser->id }}">Edit {{ $editUser->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('homes.users.partials.form', ['home' => $home, 'user' => $editUser, 'roles' => $roles, 'permissions' => $permissions, 'selectedRoles' => $editUser->roles->pluck('id')->all(), 'selectedPermissions' => $editUser->permissions->pluck('id')->all(), 'passwordRequired' => false, 'submitLabel' => 'Update home user'])
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
