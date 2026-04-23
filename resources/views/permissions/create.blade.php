@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Permissions', 'url' => route('permissions.index')],
        ['label' => 'New Permission'],
    ]" />
@endsection

@section('content')
    <x-page-header title="New Permission" description="Create a capability that can be assigned to roles." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('permissions.store') }}">
        @csrf
        @include('permissions.partials.form', ['submitLabel' => 'Create permission'])
    </form>
@endsection
