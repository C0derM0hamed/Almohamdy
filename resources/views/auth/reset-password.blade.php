@extends('layouts.auth')

@section('auth_title', __('password_recovery.reset_title'))

@section('content')
    @component('auth.partials.hope-login-shell', ['authImage' => asset('assets/images/auth/02.png')])
        <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center mb-3">
            <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}">
        </a>

        <h2 class="mb-2 text-center">{{ __('password_recovery.reset_title') }}</h2>
        <p class="text-center text-muted mb-4">{{ __('password_recovery.reset_subtitle') }}</p>

        @if ($errors->any())
            <div class="hm-auth-alert hm-auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.reset.store') }}" novalidate>
            @csrf
            <div class="form-group">
                <label for="password" class="form-label">{{ __('password_recovery.new_password') }}</label>
                <input type="password" id="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       minlength="8" maxlength="72" autocomplete="new-password" required autofocus>
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-label">{{ __('password_recovery.confirm_password') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" minlength="8" maxlength="72" autocomplete="new-password" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">{{ __('password_recovery.reset_password') }}</button>
            </div>
        </form>
    @endcomponent
@endsection
