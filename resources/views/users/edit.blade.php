@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Users', 'url' => route('users.index')],
        ['label' => $user->name],
    ]" />
@endsection

@section('content')
    <x-page-header title="Edit User" description="Update account details and role assignments." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users.partials.form', ['submitLabel' => 'Update user', 'passwordRequired' => false])
    </form>
@endsection
