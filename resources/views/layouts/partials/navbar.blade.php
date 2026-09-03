<nav class="nav navbar navbar-expand navbar-light iq-navbar hm-figma-navbar">
    <div class="container-fluid navbar-inner">
        <div class="hm-navbar-head">
            <a href="{{ route($homeRoute ?? 'dashboard', $homeRouteParams ?? []) }}" class="navbar-brand hm-navbar-brand-mobile hm-hope-logo">
                <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}" style="height:28px;width:auto;">
            </a>
            @include('layouts.partials.figma-sidebar-toggle')
        </div>

        @include('layouts.partials.figma-header-tools')
    </div>
</nav>
