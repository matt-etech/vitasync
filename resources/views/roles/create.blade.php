@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Roles', 'url' => route('roles.index')],
        ['label' => 'New Role'],
    ]" />
@endsection

@section('content')
    <x-page-header title="New Role" description="Define a role and assign existing permissions." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('roles.store') }}">
        @csrf
        @include('roles.partials.form', ['submitLabel' => 'Create role'])
    </form>
@endsection
