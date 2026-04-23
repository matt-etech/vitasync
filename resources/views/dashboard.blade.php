@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace'],
    ]" />
@endsection

@section('content')
    <div class="mb-4">
        <div>
            <h1 class="h2 fw-semibold">Access Management</h1>
            <p class="text-secondary">Manage platform users, assign roles, and connect permissions.</p>
        </div>
    </div>

    <div class="row g-3">
        @if (auth()->user()->hasPermission('homes.manage'))
        <a class="col-md-3 text-decoration-none" href="{{ route('homes.index') }}">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
            <p class="small fw-medium text-secondary">Homes</p>
            <p class="display-6 fw-semibold text-dark">{{ $homeCount }}</p>
            <p class="mb-0 text-secondary">Register homes and assign managers.</p>
                </div>
            </div>
        </a>
        @endif
        @if (auth()->user()->hasPermission('users.manage'))
        <a class="col-md-3 text-decoration-none" href="{{ route('users.index') }}">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
            <p class="small fw-medium text-secondary">Users</p>
            <p class="display-6 fw-semibold text-dark">{{ $userCount }}</p>
            <p class="mb-0 text-secondary">Create accounts and assign roles.</p>
                </div>
            </div>
        </a>
        @endif
        @if (auth()->user()->hasPermission('roles.manage'))
        <a class="col-md-3 text-decoration-none" href="{{ route('roles.index') }}">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
            <p class="small fw-medium text-secondary">Roles</p>
            <p class="display-6 fw-semibold text-dark">{{ $roleCount }}</p>
            <p class="mb-0 text-secondary">Group permissions by responsibility.</p>
                </div>
            </div>
        </a>
        @endif
        @if (auth()->user()->hasPermission('permissions.manage'))
        <a class="col-md-3 text-decoration-none" href="{{ route('permissions.index') }}">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
            <p class="small fw-medium text-secondary">Permissions</p>
            <p class="display-6 fw-semibold text-dark">{{ $permissionCount }}</p>
            <p class="mb-0 text-secondary">Define access capabilities.</p>
                </div>
            </div>
        </a>
        @endif
        @if (auth()->user()->hasPermission('clients.manage'))
        <a class="col-md-3 text-decoration-none" href="{{ route('clients.index') }}">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
            <p class="small fw-medium text-secondary">Clients</p>
            <p class="display-6 fw-semibold text-dark">{{ $clientCount }}</p>
            <p class="mb-0 text-secondary">Onboard and manage care clients.</p>
                </div>
            </div>
        </a>
        @endif
    </div>
@endsection
