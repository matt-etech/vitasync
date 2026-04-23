@extends('layouts.app')

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => 'Workspace', 'url' => route('dashboard')],
        ['label' => 'Welcome'],
    ]" />
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3">VitaSync</h1>
            <p class="mb-0 text-secondary">Use the login page to access identity and access control.</p>
        </div>
    </div>
@endsection
