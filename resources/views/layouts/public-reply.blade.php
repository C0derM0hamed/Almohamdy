@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('dashboard.brand_name'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/hh-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/core/libs.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css') }}?v=2.0.0">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}?v=2.0.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/hm-fonts.css') }}?v={{ $hmAssetVersion ?? 1 }}" rel="stylesheet">
    <link href="{{ asset('css/hm-public-reply.css') }}?v={{ filemtime(public_path('css/hm-public-reply.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-bankdash-theme.css') }}?v={{ filemtime(public_path('css/hm-bankdash-theme.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="hm-public-reply {{ $isRtl ? 'is-rtl' : 'is-ltr' }}">
    <header class="hm-public-reply__header">
        <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}" class="hm-public-reply__logo">
        <nav class="hm-public-reply__lang" aria-label="{{ __('dashboard.language') }}">
            <a href="{{ route('lang.ar') }}" class="{{ $locale === 'ar' ? 'is-active' : '' }}" lang="ar">{{ __('dashboard.language_ar') }}</a>
            <a href="{{ route('lang.en') }}" class="{{ $locale === 'en' ? 'is-active' : '' }}" lang="en">{{ __('dashboard.language_en') }}</a>
        </nav>
    </header>

    <main class="hm-public-reply__main">
        @yield('content')
    </main>

    <footer class="hm-public-reply__footer">
        {{ __('dashboard.brand_name') }}
    </footer>
    @stack('scripts')
</body>
</html>
