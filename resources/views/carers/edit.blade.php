@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Care'],
        ['label' => 'Carers', 'url' => route('carers.index')],
        ['label' => $carer->name],
    ]" />
@endsection

@section('content')
    <x-page-header title="Edit {{ $carer->name }}" description="Update the carer's login and access details. Onboarding assessment is managed separately.">
        <x-slot:action>
            <a class="btn btn-action btn-action-primary" href="{{ route('carers.assessments.edit', $carer) }}"><i class="fa-solid fa-list-check"></i>Assessment</a>
        </x-slot:action>
    </x-page-header>

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('carers.update', $carer) }}">
        @csrf
        @method('PUT')
        @include('carers.partials.form', ['carer' => $carer, 'homes' => $homes, 'passwordRequired' => false, 'submitLabel' => 'Update carer', 'cancelUrl' => route('carers.show', $carer)])
    </form>
@endsection
