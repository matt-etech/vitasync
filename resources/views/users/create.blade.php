@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'User Management'],
        ['label' => 'Users', 'url' => route('users.index')],
        ['label' => 'New User'],
    ]" />
@endsection

@section('content')
    <x-page-header title="New User" description="Create a login account and assign roles." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users.partials.form', ['submitLabel' => 'Create user', 'passwordRequired' => true])
    </form>
@endsection
