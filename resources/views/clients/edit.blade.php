@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Clients', 'url' => route('clients.index')],
        ['label' => $client->fullName()],
    ]" />
@endsection

@section('content')
    <x-page-header title="Edit {{ $client->fullName() }}" description="Update client onboarding and contact details." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')
        @include('clients.partials.form', ['submitLabel' => 'Update client'])
    </form>
@endsection
