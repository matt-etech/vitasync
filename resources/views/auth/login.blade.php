@extends('layouts.app')

@section('content')
    <div class="login-stage">
        <section class="login-panel">
            <div class="text-center mb-4">
                <img src="{{ asset('logo.png') }}" alt="VitaSync logo" class="login-logo">
                <p class="brand-kicker mt-3 mb-1">VitaSync Access Control</p>
                <h1 class="h3 fw-bold mb-2">Sign in securely</h1>
                <p class="text-secondary mb-0">Manage care platform access, homes, users, and onboarding records.</p>
            </div>

            <div class="card shadow-sm login-card">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h4 fw-bold mb-1">Login</h2>
                    <p class="text-secondary mb-4">Enter your administrator credentials.</p>

                    <form method="POST" action="{{ route('login.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="email">Email address</label>
                            <input
                                class="form-control focus-ring-brand"
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                required
                                autofocus
                            >
                            @error('email')
                                <p class="mt-2 small text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input
                                class="form-control focus-ring-brand"
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                            >
                            @error('password')
                                <p class="mt-2 small text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="form-check mb-4">
                            <input class="form-check-input" name="remember" type="checkbox" value="1">
                            <span class="form-check-label">Remember this device</span>
                        </label>

                        <button class="btn btn-primary w-100 fw-semibold" type="submit">
                            Sign in
                        </button>
                    </form>

                    <p class="small text-secondary text-center mt-4 mb-0">
                        Access is monitored for accountable care operations.
                    </p>
                </div>
            </div>
        </section>
    </div>
@endsection
