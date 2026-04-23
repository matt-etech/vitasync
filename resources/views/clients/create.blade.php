@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Clients', 'url' => route('clients.index')],
        ['label' => 'New Client'],
    ]" />
@endsection

@section('content')
    <x-page-header title="New Client" description="Onboard a client with identity, contact, emergency contact, and home details." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('clients.store') }}">
        @csrf
        @include('clients.partials.form', ['submitLabel' => 'Create client'])
    </form>
@endsection
