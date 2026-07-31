@php
    $isRtl = app()->getLocale() === 'ar';
    $userLevel = (string) session('hr_user_level', '0');
    $userRoleLabel = __('dashboard.levels.'.$userLevel);
@endphp

<nav class="nav navbar navbar-expand-lg navbar-light iq-navbar">
    <div class="container-fluid navbar-inner">
        <a href="{{ route($homeRoute ?? 'dashboard') }}" class="navbar-brand hm-navbar-brand-mobile hm-hope-logo">
            <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}" style="height:28px;width:auto;">
        </a>

        <button
            type="button"
            id="hmSidebarToggle"
            class="hm-navbar-sidebar-toggle"
            aria-controls="hmAppSidebar"
            aria-expanded="true"
            data-label-show="{{ __('dashboard.toggle_sidebar_show') }}"
            data-label-hide="{{ __('dashboard.toggle_sidebar_hide') }}"
            aria-label="{{ __('dashboard.toggle_sidebar_hide') }}"
        >
            <span class="hm-navbar-sidebar-toggle__icon hm-navbar-sidebar-toggle__icon--open" aria-hidden="true">
                <i class="bi bi-list"></i>
            </span>
            <span class="hm-navbar-sidebar-toggle__icon hm-navbar-sidebar-toggle__icon--closed" aria-hidden="true">
                <i class="bi bi-layout-sidebar-inset"></i>
            </span>
        </button>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#hmNavbarSupportedContent" aria-controls="hmNavbarSupportedContent" aria-expanded="false" aria-label="{{ __('dashboard.menu') }}">
            <span class="navbar-toggler-icon">
                <span class="mt-2 navbar-toggler-bar bar1"></span>
                <span class="navbar-toggler-bar bar2"></span>
                <span class="navbar-toggler-bar bar3"></span>
            </span>
        </button>

        <div class="collapse navbar-collapse" id="hmNavbarSupportedContent">
            <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link hm-notify-btn" id="hmNotifyMenu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('dashboard.notifications') }}">
                        <svg class="icon-24" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M19.7695 11.6453C19.039 10.7923 18.7071 10.0531 18.7071 8.79716V8.37013C18.7071 6.73354 18.3304 5.67907 17.5115 4.62459C16.2493 2.98699 14.1244 2 12.0442 2H11.9558C9.91935 2 7.86106 2.94167 6.577 4.5128C5.71333 5.58842 5.29293 6.68822 5.29293 8.37013V8.79716C5.29293 10.0531 4.98284 10.7923 4.23049 11.6453C3.67691 12.2738 3.5 13.0815 3.5 13.9557C3.5 14.8309 3.78723 15.6598 4.36367 16.3336C5.11602 17.1413 6.17846 17.6569 7.26375 17.7466C8.83505 17.9258 10.4063 17.9933 12.0005 17.9933C13.5937 17.9933 15.165 17.8805 16.7372 17.7466C17.8215 17.6569 18.884 17.1413 19.6363 16.3336C20.2118 15.6598 20.5 14.8309 20.5 13.9557C20.5 13.0815 20.3231 12.2738 19.7695 11.6453Z" fill="currentColor"></path>
                            <path opacity="0.4" d="M14.0088 19.2283C13.5088 19.1215 10.4627 19.1215 9.96275 19.2283C9.53539 19.327 9.07324 19.5566 9.07324 20.0602C9.09809 20.5406 9.37935 20.9646 9.76895 21.2335L9.76795 21.2345C10.2718 21.6273 10.8632 21.877 11.4824 21.9667C11.8123 22.012 12.1482 22.01 12.4901 21.9667C13.1083 21.877 13.6997 21.6273 14.2036 21.2345L14.2026 21.2335C14.5922 20.9646 14.8734 20.5406 14.8983 20.0602C14.8983 19.5566 14.4361 19.327 14.0088 19.2283Z" fill="currentColor"></path>
                        </svg>
                        @if (($notificationCount ?? 0) > 0)
                            <span class="hm-navbar__badge">{{ $notificationCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-end hm-dropdown-menu" aria-labelledby="hmNotifyMenu">
                        <div class="hm-dropdown-menu__header">{{ __('dashboard.notifications') }}</div>
                        @forelse ($notifications ?? [] as $notification)
                            <div class="hm-dropdown-menu__item">{{ $notification['title'] ?? '' }}</div>
                        @empty
                            <div class="hm-dropdown-menu__empty">{{ __('dashboard.no_notifications') }}</div>
                        @endforelse
                    </div>
                </li>

                <li class="nav-item">
                    <div class="hm-hope-lang" role="group" aria-label="{{ __('dashboard.language') }}">
                        <a href="{{ route('lang.ar') }}" class="{{ app()->getLocale() === 'ar' ? 'is-active' : '' }}" lang="ar">{{ __('dashboard.language_ar') }}</a>
                        <a href="{{ route('lang.en') }}" class="{{ app()->getLocale() === 'en' ? 'is-active' : '' }}" lang="en">{{ __('dashboard.language_en') }}</a>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a class="py-0 nav-link d-flex align-items-center" href="#" id="hmUserMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="hm-user-dropdown__avatar">{{ $userInitials ?? 'U' }}</span>
                        <div class="caption ms-3 d-none d-md-block">
                            <h6 class="mb-0 caption-title">{{ $userName ?? '' }}</h6>
                            <p class="mb-0 caption-sub-title">{{ $userRoleLabel }}</p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end hm-dropdown-menu" aria-labelledby="hmUserMenu">
                        <li class="hm-user-dropdown__header">
                            <span class="hm-user-dropdown__avatar">{{ $userInitials ?? 'U' }}</span>
                            <div>
                                <p class="hm-user-dropdown__name">{{ $userName ?? '' }}</p>
                                <p class="hm-user-dropdown__role">{{ $userRoleLabel }}</p>
                            </div>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="hm-user-dropdown__item">
                                    <i class="bi bi-box-arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                                    <span>{{ __('dashboard.logout') }}</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
