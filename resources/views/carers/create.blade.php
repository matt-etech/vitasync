@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Carers', 'url' => route('carers.index')],
        ['label' => 'New Carer'],
    ]" />
@endsection

@section('content')
    <x-page-header title="New Carer" description="Create the carer's login and access record first. The onboarding assessment opens next." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('carers.store') }}">
        @csrf
        @include('carers.partials.form', ['carer' => $carer, 'homes' => $homes, 'passwordRequired' => true, 'submitLabel' => 'Create carer', 'cancelUrl' => route('carers.index')])
    </form>
@endsection
