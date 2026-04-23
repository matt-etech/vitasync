@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Homes', 'url' => route('homes.index')],
        ['label' => $home->name, 'url' => route('homes.users.index', $home)],
        ['label' => 'New User'],
    ]" />
@endsection

@section('content')
    <x-page-header title="New {{ $home->name }} User" description="Create a user directly under this home and assign access." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('homes.users.store', $home) }}">
        @csrf
        @include('homes.users.partials.form', ['submitLabel' => 'Create home user', 'passwordRequired' => true])
    </form>
@endsection
