<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'VitaSync') }}</title>
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/css/buttons.bootstrap5.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --vitasync-ink: #101828;
            --vitasync-muted: #667085;
            --vitasync-line: #e4e7ec;
            --vitasync-soft-line: #f2f4f7;
            --vitasync-teal: #0f766e;
            --vitasync-teal-dark: #134e4a;
            --vitasync-teal-soft: #ccfbf1;
            --vitasync-surface: #f6f8fb;
            --vitasync-panel: #ffffff;
        }

        body {
            background: var(--vitasync-surface);
            color: var(--vitasync-ink);
            font-size: 15px;
        }

        .btn-primary {
            --bs-btn-bg: var(--vitasync-teal);
            --bs-btn-border-color: var(--vitasync-teal);
            --bs-btn-hover-bg: var(--vitasync-teal-dark);
            --bs-btn-hover-border-color: var(--vitasync-teal-dark);
        }

        .btn,
        .form-control,
        .form-select,
        .dropdown-item {
            border-radius: .5rem;
        }

        .btn {
            font-weight: 700;
            letter-spacing: -.01em;
        }

        .btn-primary {
            box-shadow: 0 8px 18px rgba(15, 118, 110, .18);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            box-shadow: 0 10px 22px rgba(19, 78, 74, .22);
        }

        .btn-action {
            align-items: center;
            background: #fff;
            border: 1px solid #d0d5dd;
            color: #344054;
            display: inline-flex;
            gap: .45rem;
            line-height: 1.1;
            padding: .48rem .72rem;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease, box-shadow .15s ease;
        }

        .btn-action:hover,
        .btn-action:focus {
            background: #f8fafc;
            border-color: #98a2b3;
            color: #101828;
            box-shadow: 0 6px 16px rgba(16, 24, 40, .08);
        }

        .btn-action-primary {
            background: #ecfdf9;
            border-color: #99f6e4;
            color: #0f766e;
        }

        .btn-action-primary:hover,
        .btn-action-primary:focus {
            background: #ccfbf1;
            border-color: #5eead4;
            color: #134e4a;
        }

        .btn-action-danger {
            background: #fff5f5;
            border-color: #fecaca;
            color: #b42318;
        }

        .btn-action-danger:hover,
        .btn-action-danger:focus {
            background: #fee4e2;
            border-color: #fda29b;
            color: #7a271a;
        }

        .btn-action .fa-solid,
        .btn-action .fa-regular {
            font-size: .95em;
        }

        .text-brand {
            color: var(--vitasync-teal-dark);
        }

        .app-shell {
            padding: 1.25rem;
        }

        .workspace-header {
            background: rgba(255, 255, 255, .96);
            border: 1px solid var(--vitasync-soft-line);
            border-radius: 1.5rem;
            box-shadow: 0 18px 45px rgba(16, 24, 40, .08);
            overflow: visible;
        }

        .brand-mark {
            width: 3rem;
            height: 3rem;
            border-radius: .5rem;
            object-fit: contain;
        }

        .brand-title {
            font-size: 1.25rem;
            letter-spacing: -.02em;
        }

        .brand-kicker,
        .section-kicker {
            color: var(--vitasync-muted);
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .account-panel {
            background: #f8fafc;
            border: 1px solid var(--vitasync-soft-line);
            border-radius: 1rem;
            padding: .5rem;
        }

        .avatar-initial {
            width: 2.6rem;
            height: 2.6rem;
            border-radius: .8rem;
            background: #eef2f7;
            color: var(--vitasync-ink);
            font-weight: 800;
        }

        .workspace-nav {
            border-top: 1px solid var(--vitasync-soft-line);
            padding: .85rem 0;
        }

        .workspace-nav .nav-link,
        .workspace-nav .dropdown-toggle {
            border: 1px solid transparent;
            border-radius: .5rem;
            color: #344054;
            font-weight: 700;
            padding: .68rem 1rem;
        }

        .workspace-nav .nav-link:hover,
        .workspace-nav .dropdown-toggle:hover {
            background: #f8fafc;
            border-color: var(--vitasync-line);
            color: var(--vitasync-teal-dark);
        }

        .workspace-nav .nav-link.active,
        .workspace-nav .dropdown-toggle.active {
            background: var(--vitasync-teal-dark);
            color: #fff;
        }

        .mega-menu {
            border: 1px solid var(--vitasync-line);
            border-radius: 1rem;
            box-shadow: 0 26px 60px rgba(16, 24, 40, .14);
            min-width: 21rem;
            padding: .75rem;
        }

        .mega-menu .dropdown-item {
            border-radius: .5rem;
            padding: .9rem;
            white-space: normal;
        }

        .mega-menu .dropdown-item:hover {
            background: #f8fafc;
        }

        .menu-icon {
            width: 1.7rem;
            color: var(--vitasync-muted);
        }

        .content-panel,
        .card {
            border-color: var(--vitasync-line);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(16, 24, 40, .06);
        }

        .page-hero {
            background: rgba(255, 255, 255, .94);
            border: 1px solid var(--vitasync-soft-line);
            border-radius: 1.25rem;
            box-shadow: 0 12px 32px rgba(16, 24, 40, .06);
            padding: 2rem;
        }

        .login-stage {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 4rem);
            overflow: hidden;
            padding: 2rem 0;
            position: relative;
        }

        .login-stage::before,
        .login-stage::after {
            content: "";
            left: 50%;
            pointer-events: none;
            position: absolute;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
        }

        .login-stage::before {
            background:
                linear-gradient(115deg, transparent 0 16%, rgba(15, 118, 110, .1) 16.2% 17.5%, transparent 17.7% 34%, rgba(244, 162, 48, .11) 34.2% 35.5%, transparent 35.7% 52%, rgba(19, 78, 74, .09) 52.2% 53.5%, transparent 53.7% 70%, rgba(15, 118, 110, .08) 70.2% 71.5%, transparent 71.7%),
                repeating-linear-gradient(115deg, rgba(19, 78, 74, .12) 0 1px, transparent 1px 7px),
                repeating-linear-gradient(245deg, rgba(244, 162, 48, .08) 0 1px, transparent 1px 9px);
            clip-path: none;
            height: 100%;
            left: 0;
            opacity: .34;
            top: 0;
            transform: none;
            width: 100%;
        }

        .login-stage::after {
            background:
                linear-gradient(55deg, transparent 0 20%, rgba(15, 118, 110, .1) 20.2% 22.2%, transparent 22.4% 45%, rgba(244, 162, 48, .11) 45.2% 47.2%, transparent 47.4% 72%, rgba(19, 78, 74, .1) 72.2% 74.2%, transparent 74.4%),
                repeating-linear-gradient(55deg, rgba(15, 118, 110, .08) 0 1px, transparent 1px 8px);
            clip-path: none;
            height: 100%;
            left: 0;
            opacity: .26;
            top: 0;
            transform: none;
            width: 100%;
        }

        .login-panel {
            max-width: 31rem;
            position: relative;
            width: 100%;
            z-index: 1;
        }

        .login-logo {
            height: 4.75rem;
            object-fit: contain;
            width: 4.75rem;
        }

        .login-card {
            border-radius: 1rem;
        }

        .form-workspace {
            background: #fff;
            border: 1px solid var(--vitasync-line);
            border-radius: 1rem;
            display: block;
            box-shadow: 0 12px 32px rgba(16, 24, 40, .06);
            max-width: none !important;
            overflow: hidden;
            width: 100%;
        }

        .form-section {
            padding: 1.5rem;
        }

        .form-section + .form-section {
            border-top: 1px solid var(--vitasync-soft-line);
        }

        .form-section-header {
            margin-bottom: 1rem;
            max-width: 44rem;
        }

        .form-section-title {
            color: var(--vitasync-ink);
            font-size: 1rem;
            font-weight: 800;
            margin-bottom: .25rem;
        }

        .form-section-description {
            color: var(--vitasync-muted);
            margin-bottom: 0;
        }

        .form-actions {
            align-items: center;
            background: #f8fafc;
            border-top: 1px solid var(--vitasync-line);
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            justify-content: flex-end;
            padding: 1rem 1.35rem;
        }

        @media (min-width: 992px) {
            .form-section {
                padding: 1.75rem 2rem;
            }

            .form-actions {
                padding: 1.1rem 2rem;
            }
        }

        .choice-card {
            align-items: flex-start;
            background: #fff;
            border: 1px solid var(--vitasync-line);
            border-radius: .75rem;
            display: flex;
            gap: .8rem;
            height: 100%;
            padding: .95rem;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .choice-card:hover,
        .choice-card:focus-within {
            background: #f8fafc;
            border-color: #99f6e4;
            box-shadow: 0 8px 22px rgba(16, 24, 40, .06);
        }

        .choice-card .form-check-input {
            margin-top: .2rem;
        }

        .assessment-layout {
            align-items: flex-start;
            display: grid;
            gap: 1rem;
        }

        .assessment-steps {
            background: #fff;
            border: 1px solid var(--vitasync-line);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(16, 24, 40, .05);
            padding: 1rem;
        }

        .assessment-steps a {
            border: 1px solid transparent;
            border-radius: .5rem;
            color: #344054;
            display: block;
            font-weight: 700;
            padding: .65rem .75rem;
            text-decoration: none;
        }

        .assessment-steps button {
            background: transparent;
            border: 1px solid transparent;
            border-radius: .5rem;
            color: #344054;
            display: block;
            font-weight: 700;
            padding: .65rem .75rem;
            text-align: left;
            width: 100%;
        }

        .assessment-steps a:hover,
        .assessment-steps a:focus,
        .assessment-steps button:hover,
        .assessment-steps button:focus,
        .assessment-steps button.active {
            background: #ecfdf9;
            border-color: #99f6e4;
            color: var(--vitasync-teal-dark);
        }

        .assessment-step-panel {
            display: none;
        }

        .assessment-step-panel.active {
            display: block;
        }

        .assessment-progress-shell {
            background: #f8fafc;
            border-bottom: 1px solid var(--vitasync-line);
            padding: 1.25rem 1.5rem;
        }

        .assessment-progress-meta {
            color: #344054;
            display: flex;
            font-weight: 800;
            justify-content: space-between;
            margin-bottom: .65rem;
        }

        .assessment-progress-shell .progress {
            background: #e4e7ec;
            border-radius: .5rem;
            height: .75rem;
        }

        .assessment-progress-shell .progress-bar {
            background: var(--vitasync-teal-dark);
        }

        @media (min-width: 1200px) {
            .assessment-layout {
                grid-template-columns: 18rem minmax(0, 1fr);
            }

            .assessment-steps {
                position: sticky;
                top: 1rem;
            }
        }

        .breadcrumb-shell {
            color: #475467;
            font-size: .9rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .breadcrumb-shell .breadcrumb {
            margin-bottom: 0;
        }

        .breadcrumb-shell .breadcrumb-item a {
            color: var(--vitasync-teal-dark);
            text-decoration: none;
        }

        .breadcrumb-shell .breadcrumb-item a:hover,
        .breadcrumb-shell .breadcrumb-item a:focus {
            text-decoration: underline;
        }

        .breadcrumb-shell .breadcrumb-item.active {
            color: #344054;
        }

        .focus-ring-brand:focus {
            border-color: var(--vitasync-teal);
            box-shadow: 0 0 0 .25rem rgba(17, 94, 89, .15);
        }

        div.dataTables_wrapper div.dataTables_filter input,
        div.dataTables_wrapper div.dataTables_length select {
            border-radius: .5rem;
        }

        .dataTables_wrapper {
            padding: 1.25rem;
        }

        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_info {
            color: #344054;
            font-size: .9rem;
            font-weight: 600;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--vitasync-line);
            color: var(--vitasync-ink);
            min-height: 2.35rem;
        }

        .dataTables_wrapper .dataTables_filter input::placeholder {
            color: #667085;
        }

        .dt-buttons .btn {
            background: #fff;
            border: 1px solid #98a2b3;
            color: #1d2939;
            font-weight: 700;
            margin-right: .35rem;
            min-width: 5rem;
        }

        .dataTables_wrapper > .row:first-child {
            margin-bottom: 1rem !important;
        }

        .dataTables_wrapper > .row:last-child {
            border-top: 1px solid var(--vitasync-soft-line);
            margin-top: 1.25rem !important;
            padding-top: 1rem;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: .35rem !important;
        }

        .dataTables_wrapper .dataTables_paginate .pagination {
            margin-bottom: 0;
        }

        .dt-buttons .btn:hover,
        .dt-buttons .btn:focus {
            background: var(--vitasync-teal-dark);
            border-color: var(--vitasync-teal-dark);
            color: #fff;
        }

        table.dataTable {
            margin-top: .5rem !important;
        }

        table.dataTable thead th {
            background: #eef4f3;
            border-bottom: 1px solid var(--vitasync-line) !important;
            color: #101828;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: none;
        }

        table.dataTable tbody td {
            border-color: var(--vitasync-soft-line);
            color: var(--vitasync-ink);
            vertical-align: middle;
        }

        .page-item .page-link {
            border-color: #98a2b3;
            color: #1d2939;
            font-weight: 700;
        }

        .page-item.active .page-link {
            background: var(--vitasync-teal-dark);
            border-color: var(--vitasync-teal-dark);
            color: #fff;
        }

        .nav-tabs {
            border-bottom: 1px solid var(--vitasync-line);
            gap: .35rem;
        }

        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-radius: .5rem .5rem 0 0;
            color: #475467;
            font-weight: 800;
            padding: .75rem 1rem;
        }

        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link:focus {
            background: #ecfdf9;
            border-color: #99f6e4;
            color: var(--vitasync-teal-dark);
        }

        .nav-tabs .nav-link.active {
            background: #fff;
            border-color: var(--vitasync-line) var(--vitasync-line) #fff;
            color: var(--vitasync-teal-dark);
            box-shadow: inset 0 3px 0 var(--vitasync-teal);
        }

        .nav-tabs.flex-nowrap {
            scrollbar-width: thin;
        }

        .nav-tabs.flex-nowrap .nav-link {
            white-space: nowrap;
        }

        .modal-content {
            border: 1px solid var(--vitasync-line);
            border-radius: 1rem;
            box-shadow: 0 28px 70px rgba(16, 24, 40, .22);
        }

        .modal-header,
        .modal-footer {
            background: #f8fafc;
            border-color: var(--vitasync-line);
        }

        .impersonation-banner {
            align-items: center;
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: .75rem;
            color: #78350f;
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: .85rem 1rem;
        }
    </style>
</head>
<body>
    <div class="min-vh-100 app-shell">
        @auth
            <header class="workspace-header">
                <div>
                    <div class="container-fluid px-4 py-3">
                        <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-md-between">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ asset('logo.png') }}" alt="VitaSync logo" class="brand-mark">
                                <div>
                                    <a href="{{ route('dashboard') }}" class="brand-title fw-bold text-dark text-decoration-none">VitaSync</a>
                                    <p class="brand-kicker mb-0">Default Home</p>
                                </div>
                            </div>
                            <div class="account-panel d-flex flex-wrap align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2 px-2">
                                    <span class="avatar-initial d-inline-flex align-items-center justify-content-center">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                    <span>
                                        <span class="d-block fw-bold">{{ auth()->user()->name }}</span>
                                        <span class="d-block small text-secondary">{{ session('impersonator_id') ? 'Impersonating' : 'Admin' }}{{ auth()->user()->home ? ' - '.auth()->user()->home->name : '' }}</span>
                                    </span>
                                </div>
                                @if (auth()->user()->home && auth()->user()->hasPermission('home_users.manage'))
                                    <a class="btn btn-outline-dark" href="{{ route('homes.users.index', auth()->user()->home) }}">
                                        <i class="fa-solid fa-house me-2"></i>{{ auth()->user()->home->name }}
                                    </a>
                                @elseif (auth()->user()->home)
                                    <span class="btn btn-outline-dark disabled" aria-disabled="true">
                                        <i class="fa-solid fa-house me-2"></i>{{ auth()->user()->home->name }}
                                    </span>
                                @elseif (auth()->user()->hasPermission('homes.manage'))
                                    <a class="btn btn-outline-dark" href="{{ route('homes.index') }}">
                                        <i class="fa-solid fa-house me-2"></i>Homes
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="btn btn-outline-dark" type="submit">
                                        <i class="fa-solid fa-right-from-bracket me-1"></i>Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <nav class="navbar navbar-expand-lg workspace-nav">
                    <div class="container-fluid px-4">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primaryNavigation" aria-controls="primaryNavigation" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="primaryNavigation">
                            <ul class="navbar-nav gap-lg-2 align-items-lg-center">
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="fa-solid fa-people-roof me-2"></i>Workspace</a>
                                </li>
                                @if (auth()->user()->hasPermission('homes.manage'))
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('homes.*') && ! request()->routeIs('homes.users.*') ? 'active' : '' }}" href="{{ route('homes.index') }}"><i class="fa-solid fa-house-medical me-2"></i>Homes</a>
                                </li>
                                @endif
                                @if (auth()->user()->hasPermission('users.manage') || auth()->user()->hasPermission('roles.manage') || auth()->user()->hasPermission('permissions.manage'))
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('homes.users.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-users-gear me-2"></i>User Management
                                    </a>
                                    <ul class="dropdown-menu mega-menu">
                                        <li><span class="dropdown-header section-kicker">Access Operations</span></li>
                                        @if (auth()->user()->hasPermission('users.manage'))
                                        <li>
                                            <a class="dropdown-item d-flex gap-2" href="{{ route('users.index') }}">
                                                <span class="menu-icon"><i class="fa-solid fa-users"></i></span>
                                                <span><span class="d-block fw-bold">Users</span><span class="d-block text-secondary small">Manage global and home-linked accounts.</span></span>
                                            </a>
                                        </li>
                                        @endif
                                        @if (auth()->user()->hasPermission('roles.manage'))
                                        <li>
                                            <a class="dropdown-item d-flex gap-2" href="{{ route('roles.index') }}">
                                                <span class="menu-icon"><i class="fa-solid fa-user-shield"></i></span>
                                                <span><span class="d-block fw-bold">Roles</span><span class="d-block text-secondary small">Group access by responsibility.</span></span>
                                            </a>
                                        </li>
                                        @endif
                                        @if (auth()->user()->hasPermission('permissions.manage'))
                                        <li>
                                            <a class="dropdown-item d-flex gap-2" href="{{ route('permissions.index') }}">
                                                <span class="menu-icon"><i class="fa-solid fa-key"></i></span>
                                                <span><span class="d-block fw-bold">Permissions</span><span class="d-block text-secondary small">Control protected actions.</span></span>
                                            </a>
                                        </li>
                                        @endif
                                    </ul>
                                </li>
                                @endif
                                @if (auth()->user()->hasPermission('clients.manage') || auth()->user()->hasPermission('care_plans.manage'))
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ request()->routeIs('clients.*') || request()->routeIs('care-plans.*') || request()->routeIs('visits.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-briefcase-medical me-2"></i>Care
                                    </a>
                                    <ul class="dropdown-menu mega-menu">
                                        <li><span class="dropdown-header section-kicker">Care Operations</span></li>
                                        @if (auth()->user()->hasPermission('clients.manage'))
                                        <li>
                                            <a class="dropdown-item d-flex gap-2" href="{{ route('clients.index') }}">
                                                <span class="menu-icon"><i class="fa-solid fa-user-group"></i></span>
                                                <span><span class="d-block fw-bold">Clients</span><span class="d-block text-secondary small">Onboard and manage client records.</span></span>
                                            </a>
                                        </li>
                                        @endif
                                        @if (auth()->user()->hasPermission('care_plans.manage'))
                                        <li>
                                            <a class="dropdown-item d-flex gap-2" href="{{ route('care-plans.index') }}">
                                                <span class="menu-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                                                <span><span class="d-block fw-bold">Care Plans</span><span class="d-block text-secondary small">Plan goals, support needs, risks, and reviews.</span></span>
                                            </a>
                                        </li>
                                        @endif
                                        @if (auth()->user()->hasPermission('clients.manage'))
                                        <li>
                                            <a class="dropdown-item d-flex gap-2" href="{{ route('visits.index') }}">
                                                <span class="menu-icon"><i class="fa-solid fa-calendar-check"></i></span>
                                                <span><span class="d-block fw-bold">Visits</span><span class="d-block text-secondary small">Book visits and prepare for EVV execution.</span></span>
                                            </a>
                                        </li>
                                        @endif
                                    </ul>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </nav>
            </header>
        @endauth

        <main class="container-fluid py-4">
            @auth
                @if (session('impersonator_id'))
                    <div class="impersonation-banner">
                        <div>
                            <strong>Impersonating {{ session('impersonated_user_name', auth()->user()->name) }}</strong>
                            <span class="d-block small">Started by {{ session('impersonator_name', 'administrator') }}. Return before making administrator-level changes.</span>
                        </div>
                        <form method="POST" action="{{ route('impersonation.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-dark" type="submit"><i class="fa-solid fa-user-shield me-1"></i>Return to admin</button>
                        </form>
                    </div>
                @endif
            @endauth

            @yield('breadcrumbs')

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    <script src="{{ asset('vendor/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form[data-confirm]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.confirmed === 'true') {
                        return;
                    }

                    event.preventDefault();

                    Swal.fire({
                        title: form.dataset.confirmTitle || 'Are you sure?',
                        text: form.dataset.confirmText || 'Please confirm this action.',
                        icon: form.dataset.confirmIcon || 'warning',
                        showCancelButton: true,
                        confirmButtonText: form.dataset.confirmButton || 'Yes, continue',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#134e4a',
                        cancelButtonColor: '#667085',
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.dataset.confirmed = 'true';
                            form.submit();
                        }
                    });
                });
            });

            document.querySelectorAll('[data-vitasync-datatable]').forEach(function (table) {
                const exportTitle = table.getAttribute('data-export-title') || document.title;
                table.querySelectorAll('tbody tr').forEach(function (row) {
                    if (row.children.length === 1 && row.querySelector('td[colspan]')) {
                        row.remove();
                    }
                });

                $(table).DataTable({
                    pageLength: 10,
                    order: [],
                    responsive: true,
                    dom: "<'row align-items-center g-3 mb-3'<'col-lg-6'B><'col-lg-3'l><'col-lg-3'f>>" +
                        "<'row'<'col-12'tr>>" +
                        "<'row align-items-center g-3 mt-3'<'col-md-5'i><'col-md-7'p>>",
                    buttons: [
                        { extend: 'copyHtml5', text: '<i class="fa-regular fa-copy me-1"></i>Copy', className: 'btn btn-sm', exportOptions: { columns: ':not(.no-export)' } },
                        { extend: 'csvHtml5', text: '<i class="fa-solid fa-file-csv me-1"></i>CSV', className: 'btn btn-sm', title: exportTitle, exportOptions: { columns: ':not(.no-export)' } },
                        { extend: 'excelHtml5', text: '<i class="fa-regular fa-file-excel me-1"></i>Excel', className: 'btn btn-sm', title: exportTitle, exportOptions: { columns: ':not(.no-export)' } },
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="fa-regular fa-file-pdf me-1"></i>PDF',
                            className: 'btn btn-sm',
                            title: exportTitle,
                            orientation: 'landscape',
                            pageSize: 'A4',
                            exportOptions: { columns: ':not(.no-export)' },
                            customize: function (doc) {
                                const generatedAt = new Date().toLocaleString();
                                const tableNode = doc.content.find(function (node) {
                                    return node.table;
                                });

                                doc.pageMargins = [32, 54, 32, 42];
                                doc.defaultStyle = {
                                    fontSize: 9,
                                    color: '#101828',
                                };

                                doc.styles.title = {
                                    fontSize: 16,
                                    bold: true,
                                    color: '#134e4a',
                                    alignment: 'left',
                                    margin: [0, 0, 0, 10],
                                };

                                doc.styles.tableHeader = {
                                    bold: true,
                                    fontSize: 9,
                                    color: '#ffffff',
                                    fillColor: '#134e4a',
                                    margin: [0, 4, 0, 4],
                                };

                                doc.content.splice(1, 0, {
                                    text: 'Generated ' + generatedAt,
                                    color: '#475467',
                                    fontSize: 8,
                                    margin: [0, 0, 0, 14],
                                });

                                if (tableNode) {
                                    const columnCount = tableNode.table.body[0].length;
                                    tableNode.table.widths = Array(columnCount).fill('*');
                                    tableNode.layout = {
                                        hLineColor: function () { return '#d0d5dd'; },
                                        vLineColor: function () { return '#eaecf0'; },
                                        hLineWidth: function (i) { return i === 0 || i === 1 ? 1 : 0.5; },
                                        vLineWidth: function () { return 0.5; },
                                        paddingLeft: function () { return 7; },
                                        paddingRight: function () { return 7; },
                                        paddingTop: function () { return 6; },
                                        paddingBottom: function () { return 6; },
                                        fillColor: function (rowIndex) {
                                            if (rowIndex === 0) {
                                                return '#134e4a';
                                            }

                                            return rowIndex % 2 === 0 ? '#f8fafc' : null;
                                        },
                                    };
                                }

                                doc.footer = function (currentPage, pageCount) {
                                    return {
                                        columns: [
                                            { text: 'VitaSync', alignment: 'left', color: '#475467', fontSize: 8 },
                                            { text: 'Page ' + currentPage + ' of ' + pageCount, alignment: 'right', color: '#475467', fontSize: 8 },
                                        ],
                                        margin: [32, 0],
                                    };
                                };
                            }
                        }
                    ],
                    language: {
                        search: '',
                        searchPlaceholder: 'Search records',
                        lengthMenu: 'Show _MENU_',
                        emptyTable: 'No records found',
                    }
                });
            });

            document.querySelectorAll('[data-assessment-stepper]').forEach(function (form) {
                const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
                const controls = Array.from(document.querySelectorAll('[data-step-target]'));
                const progress = form.querySelector('[data-step-progress]');
                const currentLabel = form.querySelector('[data-step-current]');
                const totalLabel = form.querySelector('[data-step-total]');
                const previousButton = form.querySelector('[data-step-previous]');
                const nextButton = form.querySelector('[data-step-next]');
                let currentStep = 0;

                if (totalLabel) {
                    totalLabel.textContent = panels.length;
                }

                function showStep(index) {
                    currentStep = Math.max(0, Math.min(index, panels.length - 1));

                    panels.forEach(function (panel, panelIndex) {
                        panel.classList.toggle('active', panelIndex === currentStep);
                    });

                    controls.forEach(function (control) {
                        control.classList.toggle('active', Number(control.dataset.stepTarget) === currentStep);
                    });

                    if (progress) {
                        progress.style.width = (((currentStep + 1) / panels.length) * 100) + '%';
                    }

                    if (currentLabel) {
                        currentLabel.textContent = currentStep + 1;
                    }

                    if (previousButton) {
                        previousButton.disabled = currentStep === 0;
                    }

                    if (nextButton) {
                        nextButton.classList.toggle('d-none', currentStep === panels.length - 1);
                    }
                }

                controls.forEach(function (control) {
                    control.addEventListener('click', function () {
                        showStep(Number(control.dataset.stepTarget));
                    });
                });

                previousButton?.addEventListener('click', function () {
                    showStep(currentStep - 1);
                });

                nextButton?.addEventListener('click', function () {
                    showStep(currentStep + 1);
                });

                showStep(0);
            });

            document.querySelectorAll('[data-assessment-history]').forEach(function (history) {
                const buttons = Array.from(history.querySelectorAll('[data-assessment-version-target]'));
                const panels = Array.from(history.querySelectorAll('.assessment-version-panel'));

                buttons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const target = button.dataset.assessmentVersionTarget;

                        panels.forEach(function (panel) {
                            panel.classList.toggle('d-none', panel.id !== target);
                        });

                        buttons.forEach(function (item) {
                            item.classList.toggle('btn-action-primary', item === button);
                        });
                    });
                });
            });
        });
    </script>
</body>
</html>
