@extends('layouts.auth')

@section('auth_title', __('password_recovery.otp_title'))

@section('content')
    @component('auth.partials.hope-login-shell', ['authImage' => asset('assets/images/auth/02.png')])
        <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center mb-3">
            <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}">
        </a>

        <h2 class="mb-2 text-center">{{ __('password_recovery.otp_title') }}</h2>
        <p class="text-center text-muted mb-4">{{ __('password_recovery.otp_subtitle') }}</p>

        @if (session('status'))
            <div class="hm-auth-alert hm-auth-alert--success" role="status">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @error('otp')
            <div class="hm-auth-alert hm-auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                <span>{{ $message }}</span>
            </div>
        @enderror

        <form method="POST" action="{{ route('password.otp.verify') }}" novalidate>
            @csrf
            <div class="form-group">
                <label for="otp" class="form-label">{{ __('password_recovery.otp') }}</label>
                <input type="text" id="otp" name="otp" value="" maxlength="6"
                       class="form-control text-center @error('otp') is-invalid @enderror"
                       inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" dir="ltr" required autofocus>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">{{ __('password_recovery.verify_otp') }}</button>
                <a href="{{ route('password.forgot') }}" class="btn btn-link">{{ __('password_recovery.start_over') }}</a>
            </div>
        </form>
    @endcomponent
@endsection
