@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $hopeUiVersion = '2.0.0';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('dashboard.title')) — {{ __('dashboard.brand_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/hh-icon.png') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/core/libs.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/hope-ui.min.css') }}?v={{ $hopeUiVersion }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}?v={{ $hopeUiVersion }}">
    @if ($isRtl)
        <link rel="stylesheet" href="{{ asset('assets/css/rtl.min.css') }}">
    @endif

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preload" href="{{ asset('fonts/noto-kufi-arabic/NotoKufiArabic-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link href="{{ asset('css/hm-fonts.css') }}?v={{ $hmAssetVersion }}" rel="stylesheet">
    <link href="{{ asset('css/hm-app.css') }}?v={{ filemtime(public_path('css/hm-app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-components.css') }}?v={{ filemtime(public_path('css/hm-components.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-ui-global.css') }}?v={{ filemtime(public_path('css/hm-ui-global.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hope-ui-bridge.css') }}?v={{ filemtime(public_path('css/hm-hope-ui-bridge.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hope-overlays.css') }}?v={{ filemtime(public_path('css/hm-hope-overlays.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-page-transitions.css') }}?v={{ filemtime(public_path('css/hm-page-transitions.css')) }}" rel="stylesheet">
    <script>
        (function () {
            try {
                if (sessionStorage.getItem('hm-sidebar-collapsed') === '1') {
                    document.documentElement.classList.add('hm-sidebar-is-collapsed');
                }
                if (sessionStorage.getItem('hm-page-nav') !== '1') {
                    document.documentElement.classList.add('hm-page-instant');
                }
            } catch (e) {}
        })();
    </script>
    @stack('styles')
    <link href="{{ asset('css/hm-hope-modules.css') }}?v={{ filemtime(public_path('css/hm-hope-modules.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-cc-hope.css') }}?v={{ filemtime(public_path('css/hm-cc-hope.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-print.css') }}?v={{ filemtime(public_path('css/hm-print.css')) }}" rel="stylesheet">
</head>
<body class="hm-app-body {{ $isRtl ? 'hm-locale-ar' : 'hm-locale-en' }}" data-hm-home-url="{{ route($homeRoute ?? 'dashboard') }}">
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
    </div>

    @include('layouts.partials.sidebar')

    <main class="main-content">
        <div class="position-relative hm-hope-banner">
            @include('layouts.partials.navbar')
        </div>

        <div class="content-inner hm-content-inner hm-page-root">
            @yield('content')
        </div>

        @include('layouts.partials.footer')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script src="{{ asset('assets/js/hope-ui.js') }}" defer></script>
    <script src="{{ asset('js/hm-sidebar-toggle.js') }}?v={{ filemtime(public_path('js/hm-sidebar-toggle.js')) }}" defer></script>
    <script src="{{ asset('js/hm-page-transitions.js') }}?v={{ filemtime(public_path('js/hm-page-transitions.js')) }}" defer></script>
    <script src="{{ asset('js/hm-app-navigation.js') }}?v={{ filemtime(public_path('js/hm-app-navigation.js')) }}" defer></script>
    <script>
        (function () {
            function hideHopeLoader() {
                document.body.classList.add('hm-shell-ready');
                document.querySelectorAll('#loading, .loader.simple-loader').forEach(function (node) {
                    node.style.display = 'none';
                });
            }

            document.addEventListener('DOMContentLoaded', hideHopeLoader);
            window.addEventListener('load', hideHopeLoader);
            setTimeout(hideHopeLoader, 1200);
        })();
    </script>
    @stack('scripts')
</body>
</html>
