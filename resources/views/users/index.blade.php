@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Users'],
    ]" />
@endsection

@section('content')
    @php($activeUserForm = old('user_form'))

    <x-page-header title="Users" description="Create accounts and assign operational roles.">
        <x-slot:action>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('users.login-history.destroy') }}" data-confirm data-confirm-title="Reset login history?" data-confirm-text="This will permanently delete all recorded login and logout history." data-confirm-button="Yes, reset">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-rotate-left me-1"></i>Reset history</button>
                </form>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="fa-solid fa-plus me-1"></i>New user</button>
            </div>
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
                    <th>Last login</th>
                    <th>Last logout</th>
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
                        <td>
                            @if ($user->latestLogin)
                                <button class="btn btn-sm btn-link p-0 text-start" type="button" data-bs-toggle="modal" data-bs-target="#loginHistoryModal{{ $user->id }}">
                                    <span data-local-datetime="{{ $user->latestLogin->logged_in_at->toIso8601String() }}" data-device-datetime="{{ $user->latestLogin->logged_in_device_at }}" data-time-zone="{{ $user->latestLogin->login_timezone }}">{{ $user->latestLogin->logged_in_at->format('d M Y H:i:s') }}</span>
                                </button>
                            @else
                                <span class="text-secondary">Never</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->latestLogin?->logged_out_at)
                                <button class="btn btn-sm btn-link p-0 text-start" type="button" data-bs-toggle="modal" data-bs-target="#loginHistoryModal{{ $user->id }}">
                                    <span data-local-datetime="{{ $user->latestLogin->logged_out_at->toIso8601String() }}" data-device-datetime="{{ $user->latestLogin->logged_out_device_at }}" data-time-zone="{{ $user->latestLogin->logout_timezone ?? $user->latestLogin->login_timezone }}">{{ $user->latestLogin->logged_out_at->format('d M Y H:i:s') }}</span>
                                </button>
                            @elseif ($user->latestLogin)
                                <span class="text-secondary">No logout recorded</span>
                            @else
                                <span class="text-secondary">Never</span>
                            @endif
                        </td>
                        <td><span class="badge text-bg-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#loginHistoryModal{{ $user->id }}"><i class="fa-solid fa-clock-rotate-left"></i>Logins</button>
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

    {{-- Family access accounts are managed from the dedicated Family Access page, not the Users page.
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="h5 mb-1">Family members</h2>
                <p class="text-secondary mb-0">Client-linked family access accounts shown in the user panel.</p>
            </div>
            @can('family_members.manage')
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createFamilyMemberFromUsersModal"><i class="fa-solid fa-plus me-1"></i>New family member</button>
                    <a class="btn btn-action" href="{{ route('family-members.index') }}"><i class="fa-solid fa-users-gear"></i>Family access page</a>
                </div>
            @endcan
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Family members">
                <thead class="table-light">
                    <tr>
                        <th>Family member</th>
                        <th>Client</th>
                        <th>Home</th>
                        <th>Access permissions</th>
                        <th>Last login</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($familyMembers as $familyMember)
                        <tr>
                            <td>
                                <p class="fw-semibold mb-0">{{ $familyMember->name }}</p>
                                <p class="text-secondary mb-0">{{ $familyMember->email }}</p>
                                @if ($familyMember->relationship)
                                    <p class="small text-secondary mb-0">{{ $familyMember->relationship }}</p>
                                @endif
                            </td>
                            <td>
                                @if ($familyMember->client)
                                    <a href="{{ route('clients.show', $familyMember->client) }}">{{ $familyMember->client->fullName() }}</a>
                                @else
                                    <span class="text-secondary">No client linked</span>
                                @endif
                            </td>
                            <td>{{ $familyMember->home?->name ?? $familyMember->client?->home?->name ?? 'Not assigned' }}</td>
                            <td>
                                @php($enabledPermissions = collect($familyMember->accessSummary())->filter())
                                @if ($enabledPermissions->isNotEmpty())
                                    <span class="badge text-bg-light border">{{ $enabledPermissions->count() }} allowed</span>
                                @else
                                    <span class="text-secondary">No access granted</span>
                                @endif
                            </td>
                            <td>
                                @if ($familyMember->last_login_at)
                                    <span data-local-datetime="{{ $familyMember->last_login_at->toIso8601String() }}">{{ $familyMember->last_login_at->format('d M Y H:i:s') }}</span>
                                @else
                                    <span class="text-secondary">Never</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $familyMember->is_active ? 'success' : 'secondary' }}">{{ $familyMember->is_active ? 'Active' : 'Inactive' }}</span>
                                @if ($familyMember->login_created_at)
                                    <p class="text-secondary small mb-0 mt-1">Login created {{ $familyMember->login_created_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </td>
                            <td>
                                @can('family_members.manage')
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editFamilyMemberFromUsersModal{{ $familyMember->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                        <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#auditFamilyMemberFromUsersModal{{ $familyMember->id }}"><i class="fa-solid fa-clock-rotate-left"></i>Audit</button>
                                        <form method="POST" action="{{ route('family-members.destroy', $familyMember) }}" data-confirm data-confirm-title="{{ $familyMember->is_active ? 'Disable family access?' : 'Activate family access?' }}" data-confirm-text="{{ $familyMember->is_active ? 'This family member will no longer be able to log in.' : 'This family member will regain their configured access.' }}" data-confirm-button="{{ $familyMember->is_active ? 'Yes, disable' : 'Yes, activate' }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-action {{ $familyMember->is_active ? 'btn-action-danger' : 'btn-action-primary' }}" type="submit"><i class="fa-solid {{ $familyMember->is_active ? 'fa-ban' : 'fa-check' }}"></i>{{ $familyMember->is_active ? 'Disable' : 'Activate' }}</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-secondary">No access</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-secondary">No family members created.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('family_members.manage')
        <div class="modal fade" id="createFamilyMemberFromUsersModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('family-members.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title h5">New family member</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('family-members.partials.form', ['member' => $newFamilyMember, 'clients' => $clients, 'accessLabels' => $accessLabels, 'requirePassword' => true])
                    </div>
                </form>
            </div>
        </div>

        @foreach ($familyMembers as $editFamilyMember)
            <div class="modal fade" id="editFamilyMemberFromUsersModal{{ $editFamilyMember->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <form class="modal-content" method="POST" action="{{ route('family-members.update', $editFamilyMember) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h2 class="modal-title h5">Edit {{ $editFamilyMember->name }}</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('family-members.partials.form', ['member' => $editFamilyMember, 'clients' => $clients, 'accessLabels' => $accessLabels, 'requirePassword' => false])
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="auditFamilyMemberFromUsersModal{{ $editFamilyMember->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title h5">Audit history for {{ $editFamilyMember->name }}</h2>
                                <p class="text-secondary mb-0 small">{{ $editFamilyMember->email }}</p>
                            </div>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if ($editFamilyMember->auditLogs->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>When</th>
                                                <th>Who</th>
                                                <th>Action</th>
                                                <th>Evidence</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($editFamilyMember->auditLogs as $auditLog)
                                                <tr>
                                                    <td class="text-nowrap">
                                                        <p class="fw-semibold mb-0">{{ $auditLog->created_at->format('d/m/Y H:i:s') }}</p>
                                                        <p class="small text-secondary mb-0">{{ $auditLog->created_at->diffForHumans() }}</p>
                                                    </td>
                                                    <td>
                                                        <p class="fw-semibold mb-0">{{ $auditLog->actor?->name ?? 'System' }}</p>
                                                        @if ($auditLog->actor?->email)
                                                            <p class="small text-secondary mb-0">{{ $auditLog->actor->email }}</p>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge text-bg-light border">{{ $formatAuditLabel($auditLog->action) }}</span>
                                                        @if ($auditLog->event)
                                                            <p class="small text-secondary mb-0 mt-1">{{ $auditLog->event }}</p>
                                                        @endif
                                                    </td>
                                                    <td style="min-width: 22rem;">
                                                        @if ($auditLog->old_values)
                                                            <p class="small fw-semibold mb-1 text-secondary">Before</p>
                                                            <pre class="small bg-light border rounded p-2 mb-2">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        @endif
                                                        @if ($auditLog->new_values)
                                                            <p class="small fw-semibold mb-1 text-secondary">After</p>
                                                            <pre class="small bg-light border rounded p-2 mb-2">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        @endif
                                                        @if ($auditLog->metadata)
                                                            <p class="small fw-semibold mb-1 text-secondary">Context</p>
                                                            <pre class="small bg-light border rounded p-2 mb-0">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-secondary mb-0">No audit history recorded for this family member.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endcan
    --}}

    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <form class="modal-content" method="POST" action="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="user_form" value="create">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="createUserModalLabel">New user</h2>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && $activeUserForm === 'create')
                        <x-form-errors />
                    @endif
                    @include('users.partials.form', ['user' => $newUser, 'homes' => $homes, 'roles' => $roles, 'permissions' => $permissions, 'selectedRoles' => [], 'selectedPermissions' => [], 'passwordRequired' => true, 'submitLabel' => 'Create user', 'useOldInput' => $activeUserForm === 'create'])
                </div>
            </form>
        </div>
    </div>

    @foreach ($users as $editUser)
        <div class="modal fade" id="loginHistoryModal{{ $editUser->id }}" tabindex="-1" aria-labelledby="loginHistoryModalLabel{{ $editUser->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="loginHistoryModalLabel{{ $editUser->id }}">Login history for {{ $editUser->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($editUser->loginHistories->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Logged in</th>
                                            <th>Logged out</th>
                                            <th>IP address</th>
                                            <th>Browser / device</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($editUser->loginHistories as $loginHistory)
                                            <tr>
                                                <td><span data-local-datetime="{{ $loginHistory->logged_in_at->toIso8601String() }}" data-device-datetime="{{ $loginHistory->logged_in_device_at }}" data-time-zone="{{ $loginHistory->login_timezone }}">{{ $loginHistory->logged_in_at->format('d M Y H:i:s') }}</span></td>
                                                <td>
                                                    @if ($loginHistory->logged_out_at)
                                                        <span data-local-datetime="{{ $loginHistory->logged_out_at->toIso8601String() }}" data-device-datetime="{{ $loginHistory->logged_out_device_at }}" data-time-zone="{{ $loginHistory->logout_timezone ?? $loginHistory->login_timezone }}">{{ $loginHistory->logged_out_at->format('d M Y H:i:s') }}</span>
                                                    @else
                                                        <span class="text-secondary">No logout recorded</span>
                                                    @endif
                                                </td>
                                                <td>{{ $loginHistory->ip_address ?? 'Unknown' }}</td>
                                                <td class="small text-secondary">{{ $loginHistory->user_agent ?? 'Unknown' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-secondary mb-0">No login history recorded.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editUserModal{{ $editUser->id }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $editUser->id }}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('users.update', $editUser) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="user_form" value="edit-{{ $editUser->id }}">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editUserModalLabel{{ $editUser->id }}">Edit {{ $editUser->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && $activeUserForm === 'edit-'.$editUser->id)
                            <x-form-errors />
                        @endif
                        @include('users.partials.form', ['user' => $editUser, 'homes' => $homes, 'roles' => $roles, 'permissions' => $permissions, 'selectedRoles' => $editUser->roles->pluck('id')->all(), 'selectedPermissions' => $editUser->permissions->pluck('id')->all(), 'passwordRequired' => false, 'submitLabel' => 'Update user', 'useOldInput' => $activeUserForm === 'edit-'.$editUser->id])
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    @if ($errors->any() && $activeUserForm)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modalId = @json($activeUserForm === 'create' ? 'createUserModal' : 'editUserModal'.str_replace('edit-', '', $activeUserForm));
                const modal = document.getElementById(modalId);

                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endif
@endsection
