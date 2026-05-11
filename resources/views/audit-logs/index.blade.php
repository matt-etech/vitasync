@extends('layouts.app')

@php
    $formatLabel = static fn (?string $value): string => $value ? str($value)->replace(['_', '-'], ' ')->title()->toString() : 'System';
@endphp

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Audit Trail'],
    ]" />
@endsection

@section('content')
    <x-page-header title="Audit Trail" description="Review who changed records, what changed, and when it happened." />

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET" action="{{ route('audit-logs.index') }}">
                <div class="col-md-4">
                    <label class="form-label" for="actor_id">User</label>
                    <select class="form-select" id="actor_id" name="actor_id">
                        <option value="">All users</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" @selected(($filters['actor_id'] ?? null) == $actor->id)>{{ $actor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="action">Action</label>
                    <select class="form-select" id="action" name="action">
                        <option value="">All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? null) === $action)>{{ $formatLabel($action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="subject">Subject</label>
                    <select class="form-select" id="subject" name="subject">
                        <option value="">All subjects</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject }}" @selected(($filters['subject'] ?? null) === $subject)>{{ $subject }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-1"></i>Apply filters</button>
                    <a class="btn btn-outline-secondary" href="{{ route('audit-logs.index') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Who</th>
                        <th>Action</th>
                        <th>Subject</th>
                        <th>Changes</th>
                        <th>Request</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $log)
                        <tr>
                            <td class="text-nowrap">
                                <p class="fw-semibold mb-0">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                                <p class="small text-secondary mb-0">{{ $log->created_at->diffForHumans() }}</p>
                            </td>
                            <td>
                                <p class="fw-semibold mb-0">{{ $log->actor?->name ?? 'System' }}</p>
                                @if ($log->actor?->email)
                                    <p class="small text-secondary mb-0">{{ $log->actor->email }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="badge text-bg-light border">{{ $formatLabel($log->action) }}</span>
                            </td>
                            <td>
                                <p class="fw-semibold mb-0">{{ $log->event ?? 'System' }}</p>
                                @if ($log->auditable_id)
                                    <p class="small text-secondary mb-0">Record #{{ $log->auditable_id }}</p>
                                @endif
                            </td>
                            <td style="min-width: 22rem;">
                                @if ($log->old_values)
                                    <p class="small fw-semibold mb-1 text-secondary">Before</p>
                                    <pre class="small bg-light border rounded p-2 mb-2">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @endif
                                @if ($log->new_values)
                                    <p class="small fw-semibold mb-1 text-secondary">After</p>
                                    <pre class="small bg-light border rounded p-2 mb-2">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @endif
                                @if ($log->metadata)
                                    <p class="small fw-semibold mb-1 text-secondary">Context</p>
                                    <pre class="small bg-light border rounded p-2 mb-0">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @endif
                            </td>
                            <td style="min-width: 16rem;">
                                <p class="fw-semibold mb-0">{{ $log->method ?? 'System' }}</p>
                                @if ($log->route_name)
                                    <p class="small text-secondary mb-1">{{ $log->route_name }}</p>
                                @endif
                                @if ($log->ip_address)
                                    <p class="small text-secondary mb-1">IP: {{ $log->ip_address }}</p>
                                @endif
                                @if ($log->url)
                                    <p class="small text-secondary mb-0 text-break">{{ $log->url }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">No audit entries match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($auditLogs->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                {{ $auditLogs->links() }}
            </div>
        @endif
    </div>
@endsection
