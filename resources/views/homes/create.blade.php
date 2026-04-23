@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Homes', 'url' => route('homes.index')],
        ['label' => 'New Home'],
    ]" />
@endsection

@section('content')
    <x-page-header title="New Home" description="Capture the operational details needed to register a care home." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('homes.store') }}" enctype="multipart/form-data">
        @csrf
        @include('homes.partials.form', ['submitLabel' => 'Create home'])
    </form>
@endsection
