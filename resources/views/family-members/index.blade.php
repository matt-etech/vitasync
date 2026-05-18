@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Family Access'],
    ]" />
@endsection

@section('content')
    @php
        $formatAuditLabel = static fn (?string $value): string => $value ? str($value)->replace(['_', '-', '.'], ' ')->title()->toString() : 'System';
    @endphp

    <x-page-header title="Family Access" description="Create client-linked family accounts with explicit consent controls and audit evidence.">
        <x-slot:action>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createFamilyMemberModal"><i class="fa-solid fa-plus me-1"></i>New family member</button>
        </x-slot:action>
    </x-page-header>

    <div class="alert alert-info">
        Family access is permission-based. Family members never receive internal staff notes, other clients' information, staff records, full audit logs, or sensitive medical/legal documents unless an authorised manager grants the relevant control.
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-vitasync-datatable data-export-title="Family Access">
                <thead class="table-light">
                    <tr>
                        <th>Family member</th>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Core permissions</th>
                        <th>Restricted permissions</th>
                        <th>Status</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($familyMembers as $member)
                        <tr>
                            <td>
                                <p class="fw-semibold mb-0">{{ $member->name }}</p>
                                <p class="text-secondary mb-0">{{ $member->relationship ?: 'Relationship not set' }}</p>
                            </td>
                            <td>
                                <p class="mb-0">{{ $member->client->fullName() }}</p>
                                <p class="text-secondary mb-0">{{ $member->client->home->name }} default</p>
                                @if ($member->clients->count() > 1)
                                    <p class="text-secondary small mb-0">{{ $member->clients->count() }} assigned clients</p>
                                @endif
                            </td>
                            <td>
                                <p class="mb-0">{{ $member->email }}</p>
                                <p class="text-secondary mb-0">{{ $member->phone ?: 'No phone' }}</p>
                            </td>
                            <td>
                                @foreach (['can_view_care_updates', 'can_view_medication', 'can_view_invoices', 'can_receive_incident_alerts', 'can_view_appointments', 'can_view_visits', 'can_upload_documents', 'can_view_staff_messages', 'can_view_shared_documents'] as $field)
                                    <span class="badge {{ $member->{$field} ? 'text-bg-success' : 'text-bg-secondary' }} mb-1">{{ $accessLabels[$field]['label'] }}: {{ $member->{$field} ? 'Yes' : 'No' }}</span>
                                @endforeach
                            </td>
                            <td>
                                @foreach (['can_view_sensitive_documents', 'can_view_safeguarding'] as $field)
                                    <span class="badge {{ $member->{$field} ? 'text-bg-warning' : 'text-bg-secondary' }} mb-1">{{ $accessLabels[$field]['label'] }}: {{ $member->{$field} ? 'Yes' : 'No' }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $member->is_active ? 'success' : 'secondary' }}">{{ $member->is_active ? 'Active' : 'Disabled' }}</span>
                                @if ($member->login_created_at)
                                    <p class="text-secondary small mb-0 mt-1">Login created {{ $member->login_created_at->format('d/m/Y H:i') }}</p>
                                    <p class="text-secondary small mb-0">By {{ $member->loginCreator?->name ?? 'System' }}</p>
                                @endif
                                @if ($member->last_login_at)
                                    <p class="text-secondary small mb-0 mt-1">Last login {{ $member->last_login_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#editFamilyMemberModal{{ $member->id }}"><i class="fa-solid fa-pen"></i>Edit</button>
                                    <button class="btn btn-sm btn-action" type="button" data-bs-toggle="modal" data-bs-target="#auditFamilyMemberModal{{ $member->id }}"><i class="fa-solid fa-clock-rotate-left"></i>Audit</button>
                                    <form method="POST" action="{{ route('family-members.destroy', $member) }}" data-confirm data-confirm-title="{{ $member->is_active ? 'Disable family access?' : 'Activate family access?' }}" data-confirm-text="{{ $member->is_active ? 'This family member will no longer be able to log in.' : 'This family member will regain their configured access.' }}" data-confirm-button="{{ $member->is_active ? 'Yes, disable' : 'Yes, activate' }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-action {{ $member->is_active ? 'btn-action-danger' : 'btn-action-primary' }}" type="submit"><i class="fa-solid {{ $member->is_active ? 'fa-ban' : 'fa-check' }}"></i>{{ $member->is_active ? 'Disable' : 'Activate' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="createFamilyMemberModal" tabindex="-1" aria-hidden="true">
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

    @foreach ($familyMembers as $editMember)
        <div class="modal fade" id="editFamilyMemberModal{{ $editMember->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <form class="modal-content" method="POST" action="{{ route('family-members.update', $editMember) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title h5">Edit {{ $editMember->name }}</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('family-members.partials.form', ['member' => $editMember, 'clients' => $clients, 'accessLabels' => $accessLabels, 'requirePassword' => false])
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="auditFamilyMemberModal{{ $editMember->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title h5">Audit history for {{ $editMember->name }}</h2>
                            <p class="text-secondary mb-0 small">{{ $editMember->email }}</p>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($editMember->auditLogs->isNotEmpty())
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
                                        @foreach ($editMember->auditLogs as $auditLog)
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
@endsection
