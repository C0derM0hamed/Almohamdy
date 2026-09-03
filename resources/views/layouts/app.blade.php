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

    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}?v={{ filemtime(public_path('vendor/bootstrap-icons/bootstrap-icons.min.css')) }}" rel="stylesheet">
    <link rel="preload" href="{{ asset('fonts/noto-kufi-arabic/NotoKufiArabic-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link href="{{ asset('css/hm-fonts.css') }}?v={{ $hmAssetVersion }}" rel="stylesheet">
    <link href="{{ asset('css/hm-app.css') }}?v={{ filemtime(public_path('css/hm-app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-components.css') }}?v={{ filemtime(public_path('css/hm-components.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-ui-global.css') }}?v={{ filemtime(public_path('css/hm-ui-global.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-number-boxes.css') }}?v={{ filemtime(public_path('css/hm-number-boxes.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hope-ui-bridge.css') }}?v={{ filemtime(public_path('css/hm-hope-ui-bridge.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-hope-overlays.css') }}?v={{ filemtime(public_path('css/hm-hope-overlays.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-page-transitions.css') }}?v={{ filemtime(public_path('css/hm-page-transitions.css')) }}" rel="stylesheet">
    <script>
        (function () {
            try {
                var desktopSidebar = window.matchMedia('(min-width: 1200px)').matches;

                // Resolve the sidebar mode before the page is painted. Hope UI
                // can add sidebar-mini later during its deferred bootstrap;
                // marking the shell as preloading lets our final sidebar CSS
                // own that first frame instead of showing a broken rail.
                if (desktopSidebar && sessionStorage.getItem('hm-sidebar-pinned') !== '1') {
                    // Select the final rail state before the sidebar is
                    // painted. The preload class suppresses only the first
                    // width/label transition; normal interaction transitions
                    // are restored by hm-sidebar-toggle after layout settles.
                    document.documentElement.classList.add('hm-sidebar-is-collapsed', 'hm-sidebar-preload');
                } else if (!desktopSidebar) {
                    // Mobile/tablet starts as a closed full-width drawer. It
                    // must never inherit the desktop icon-rail state.
                    document.documentElement.classList.add('hm-sidebar-preload');
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
    <link href="{{ asset('css/hm-bankdash-theme.css') }}?v={{ filemtime(public_path('css/hm-bankdash-theme.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-figma-tokens.css') }}?v={{ filemtime(public_path('css/hm-figma-tokens.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-figma-sidebar.css') }}?v={{ filemtime(public_path('css/hm-figma-sidebar.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-figma-header.css') }}?v={{ filemtime(public_path('css/hm-figma-header.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-figma-module.css') }}?v={{ filemtime(public_path('css/hm-figma-module.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-figma-system.css') }}?v={{ filemtime(public_path('css/hm-figma-system.css')) }}" rel="stylesheet">
    @stack('workflow_styles')
</head>
<body class="hm-app-body {{ $isRtl ? 'hm-locale-ar' : 'hm-locale-en' }}" data-hm-home-url="{{ route($homeRoute ?? 'dashboard', $homeRouteParams ?? []) }}">
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
    </div>

    @include('layouts.partials.sidebar')

    <main class="main-content">
        @hasSection('figma_page_header')
        @else
            <div class="position-relative hm-hope-banner">
                @include('layouts.partials.navbar')
            </div>
        @endif

        <div class="content-inner hm-content-inner hm-page-root">
            @yield('content')
        </div>

        {{-- Keep page modals inside the replaceable main shell, but outside
             .hm-page-root so transform/filter transitions cannot trap them
             behind the Bootstrap backdrop. --}}
        @stack('modals')

    @include('layouts.partials.footer')
</main>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}?v=5.3.3"></script>
    <script src="{{ asset('assets/js/hope-ui.js') }}" defer></script>
    <script src="{{ asset('js/hm-sidebar-toggle.js') }}?v={{ filemtime(public_path('js/hm-sidebar-toggle.js')) }}" defer></script>
    <script src="{{ asset('js/hm-sidebar-search.js') }}?v={{ filemtime(public_path('js/hm-sidebar-search.js')) }}" defer></script>
    <script src="{{ asset('js/hm-logout-confirm.js') }}?v={{ filemtime(public_path('js/hm-logout-confirm.js')) }}" defer></script>
    <script src="{{ asset('js/hm-number-boxes.js') }}?v={{ filemtime(public_path('js/hm-number-boxes.js')) }}" defer></script>
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
