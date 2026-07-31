@extends('layouts.auth')

@section('auth_title', __('password_recovery.title'))

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp

    @component('auth.partials.hope-login-shell', ['authImage' => asset('assets/images/auth/02.png')])
        <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center mb-3">
            <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}">
        </a>

        <h2 class="mb-2 text-center">{{ __('login.portal_title') }}</h2>
        <p class="text-center mb-1">{{ __('password_recovery.title') }}</p>
        <p class="text-center text-muted mb-4">{{ __('password_recovery.subtitle') }}</p>

        @if (session('status'))
            <div class="hm-auth-alert hm-auth-alert--success" role="status">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="hm-auth-alert hm-auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                <span>
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.send') }}" novalidate>
            @csrf

            <div class="form-group">
                <label for="username" class="form-label">{{ __('password_recovery.username') }}</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username') }}"
                    class="form-control @error('username') is-invalid @enderror"
                    placeholder="{{ __('password_recovery.username_placeholder') }}"
                    required
                    autofocus
                    autocomplete="username"
                    dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                >
                @error('username')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="mobile" class="form-label">{{ __('password_recovery.mobile') }}</label>
                <input
                    type="tel"
                    id="mobile"
                    name="mobile"
                    value="{{ old('mobile') }}"
                    class="form-control @error('mobile') is-invalid @enderror"
                    placeholder="{{ __('password_recovery.mobile_placeholder') }}"
                    required
                    autocomplete="tel"
                    inputmode="tel"
                    dir="ltr"
                >
                @error('mobile')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">{{ __('password_recovery.send_otp') }}</button>
                <a href="{{ url('/login') }}" class="btn btn-link">{{ __('password_recovery.back_to_login') }}</a>
            </div>
        </form>
    @endcomponent
@endsection
