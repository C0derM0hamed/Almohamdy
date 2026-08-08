@extends('layouts.auth')

@section('auth_title', __('login.title'))

@section('content')
    @php $isRtl = app()->getLocale() === 'ar'; @endphp

    @component('auth.partials.hope-login-shell')
        <a href="{{ url('/') }}" class="navbar-brand d-flex align-items-center mb-3">
            <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}">
        </a>

        <h2 class="mb-2 text-center hm-hope-auth__title">{{ __('login.portal_title') }}</h2>
        <p class="text-center text-muted mb-1 hm-hope-auth__group">{{ __('login.portal_group') }}</p>
        <p class="text-center mb-3 hm-hope-auth__subtitle">{{ __('login.subtitle') }}</p>

        @if (session('error'))
            <div class="hm-auth-alert hm-auth-alert--danger" role="alert">
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                <span>{{ session('error') }}</span>
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

        <form method="POST" action="{{ url('/login') }}" novalidate id="hmLoginForm">
            @csrf

            <div class="btn-group w-100 mb-3 hm-hope-login-mode" role="group" aria-label="{{ __('login.mode_toggle_label') }}">
                <input type="radio" class="btn-check" name="login_mode" id="loginModeIdentifier" value="identifier" checked>
                <label class="btn btn-outline-primary" for="loginModeIdentifier">{{ __('login.tab_identifier') }}</label>

                <input type="radio" class="btn-check" name="login_mode" id="loginModeMobile" value="mobile">
                <label class="btn btn-outline-primary" for="loginModeMobile">{{ __('login.tab_mobile') }}</label>
            </div>

            <div class="form-group">
                <label for="username" class="form-label" id="usernameLabel">{{ __('login.username') }}</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="{{ old('username') }}"
                    class="form-control @error('username') is-invalid @enderror"
                    placeholder="{{ __('login.username_placeholder') }}"
                    required
                    autofocus
                    autocomplete="username"
                    dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                    data-label-identifier="{{ __('login.username') }}"
                    data-label-mobile="{{ __('login.tab_mobile') }}"
                    data-placeholder-identifier="{{ __('login.username_placeholder') }}"
                    data-placeholder-mobile="{{ __('login.mobile_placeholder') }}"
                >
                @error('username')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">{{ __('login.password') }}</label>
                <div class="position-relative">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="{{ __('login.password_placeholder') }}"
                        required
                        autocomplete="current-password"
                        dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                    >
                    <button type="button" class="btn btn-link position-absolute top-50 translate-middle-y hm-hope-pw-toggle" id="hmPasswordToggle" aria-label="{{ __('dashboard.toggle_password_visibility') }}" style="inset-inline-end:0.25rem;">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check mb-0">
                    <input type="checkbox" name="remember" id="remember" value="1" class="form-check-input" @checked(old('remember'))>
                    <label class="form-check-label" for="remember">{{ __('login.remember_me') }}</label>
                </div>
                <a href="{{ route('password.forgot') }}">{{ __('login.forgot_password') }}</a>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('login.sign_in') }}</button>
            </div>
        </form>

        <footer class="hm-auth-copyright">
            <p class="hm-auth-copyright__line">{{ __('login.copyright_line1') }}</p>
            <p class="hm-auth-copyright__line">{{ __('login.copyright_line2', ['year' => date('Y')]) }}</p>
        </footer>
    @endcomponent

    @include('auth.partials.verify-overlay')
@endsection

@push('scripts')
    <script src="{{ asset('js/hm-auth.js') }}?v={{ filemtime(public_path('js/hm-auth.js')) }}" defer></script>
@endpush
