@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('auth_title', __('login.title')) — {{ __('dashboard.brand_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/hh-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/core/libs.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css') }}?v=2.0.0">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}?v=2.0.0">
    @if ($isRtl)
        <link rel="stylesheet" href="{{ asset('assets/css/rtl.min.css') }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preload" href="{{ asset('fonts/noto-kufi-arabic/NotoKufiArabic-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link href="{{ asset('css/hm-fonts.css') }}?v={{ $hmAssetVersion }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hope-auth-bridge.css') }}?v={{ filemtime(public_path('css/hm-hope-auth-bridge.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hope-overlays.css') }}?v={{ filemtime(public_path('css/hm-hope-overlays.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-page-transitions.css') }}?v={{ filemtime(public_path('css/hm-page-transitions.css')) }}" rel="stylesheet">
    <script>
        (function () {
            try {
                if (sessionStorage.getItem('hm-page-nav') !== '1') {
                    document.documentElement.classList.add('hm-page-instant');
                }
            } catch (e) {}
        })();
    </script>
    @stack('styles')
</head>
<body class="hm-auth-body {{ $isRtl ? 'hm-locale-ar' : 'hm-locale-en' }} @yield('auth_body_class')">
    <nav class="hm-auth-lang hm-auth-lang-hope" aria-label="{{ __('dashboard.language') }}">
        <a href="{{ route('lang.ar') }}" class="hm-auth-lang__btn {{ $locale === 'ar' ? 'is-active' : '' }}" lang="ar">{{ __('dashboard.language_ar') }}</a>
        <a href="{{ route('lang.en') }}" class="hm-auth-lang__btn {{ $locale === 'en' ? 'is-active' : '' }}" lang="en">{{ __('dashboard.language_en') }}</a>
    </nav>

    <div class="hm-page-root hm-auth-page-root">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script src="{{ asset('js/hm-page-transitions.js') }}?v={{ filemtime(public_path('js/hm-page-transitions.js')) }}" defer></script>
    <script src="{{ asset('js/hm-auth-navigation.js') }}?v={{ filemtime(public_path('js/hm-auth-navigation.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
