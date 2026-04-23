@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Homes', 'url' => route('homes.index')],
        ['label' => $home->name, 'url' => route('homes.users.index', $home)],
        ['label' => $user->name],
    ]" />
@endsection

@section('content')
    <x-page-header title="Edit {{ $home->name }} User" description="Update home user details, roles, and direct permissions." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('homes.users.update', [$home, $user]) }}">
        @csrf
        @method('PUT')
        @include('homes.users.partials.form', ['submitLabel' => 'Update home user', 'passwordRequired' => false])
    </form>
@endsection
