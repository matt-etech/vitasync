@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Roles', 'url' => route('roles.index')],
        ['label' => $role->name],
    ]" />
@endsection

@section('content')
    <x-page-header title="Edit Role" description="Update role details and permission assignments." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')
        @include('roles.partials.form', ['submitLabel' => 'Update role'])
    </form>
@endsection
