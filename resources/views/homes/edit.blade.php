@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Homes', 'url' => route('homes.index')],
        ['label' => $home->name],
    ]" />
@endsection

@section('content')
    <x-page-header title="Edit Home" description="Update home details, logo, and manager assignment." />

    <x-form-errors />

    <form class="form-workspace" method="POST" action="{{ route('homes.update', $home) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('homes.partials.form', ['submitLabel' => 'Update home'])
    </form>
@endsection
