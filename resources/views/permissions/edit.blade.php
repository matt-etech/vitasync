@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Permissions', 'url' => route('permissions.index')],
        ['label' => $permission->name],
    ]" />
@endsection

@section('content')
    <x-page-header title="Edit Permission" description="Update the permission name or description." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('permissions.update', $permission) }}">
        @csrf
        @method('PUT')
        @include('permissions.partials.form', ['submitLabel' => 'Update permission'])
    </form>
@endsection
