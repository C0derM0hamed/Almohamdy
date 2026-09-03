@php
    $userLevel = (string) session('hr_user_level', '0');
    $userRoleLabel = __('dashboard.levels.'.$userLevel);
    $isRtl = app()->getLocale() === 'ar';
@endphp
<aside id="hmAppSidebar" class="sidebar sidebar-default sidebar-white sidebar-base navs-rounded-all hm-figma-sidebar" data-hm-sidebar-owned data-sidebar="responsive">
    <div class="sidebar-header d-flex align-items-center justify-content-start">
        <div class="hm-figma-sidebar__topbar">
            <a href="{{ $homeUrl ?? route('dashboard') }}" class="navbar-brand hm-hope-logo" data-sidebar-brand>
                <div class="logo-main">
                    <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}" class="logo-normal">
                    <img src="{{ asset('images/brand/hh-icon.png') }}" alt="{{ __('dashboard.brand_name') }}" class="logo-mini" style="height:32px;width:auto;">
                </div>
            </a>
            <button
                type="button"
                id="hmSidebarToggle"
                class="hm-figma-sidebar__toggle"
                data-hm-sidebar-toggle
                aria-controls="hmAppSidebar"
                aria-expanded="true"
                data-label-show="{{ __('dashboard.toggle_sidebar_show') }}"
                data-label-hide="{{ __('dashboard.toggle_sidebar_hide') }}"
                aria-label="{{ __('dashboard.toggle_sidebar_hide') }}"
            >
                <i class="bi bi-layout-sidebar" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="sidebar-body pt-0 data-scrollbar">
        <div class="hm-sidebar-search" data-sidebar-search data-no-results="{{ __('dashboard.sidebar_search_no_results') }}">
            <label class="hm-sidebar-search__field" for="hmSidebarSearchInput">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input
                    type="search"
                    id="hmSidebarSearchInput"
                    class="hm-sidebar-search__input"
                    data-sidebar-search-input
                    placeholder="{{ __('dashboard.sidebar_search_placeholder') }}"
                    autocomplete="off"
                    spellcheck="false"
                    aria-controls="sidebar-menu"
                >
                <button type="button" class="hm-sidebar-search__clear" data-sidebar-search-clear hidden aria-label="{{ __('dashboard.sidebar_search_clear') }}">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </label>
            <p class="hm-sidebar-search__status" data-sidebar-search-status role="status" aria-live="polite" hidden></p>
        </div>
        <div class="sidebar-list">
            <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
                <li class="nav-item static-item">
                    <a class="nav-link static-item disabled" href="#" tabindex="-1">
                        <span class="default-icon">{{ __('dashboard.modules') }}</span>
                        <span class="mini-icon">-</span>
                    </a>
                </li>

                @foreach ($sidebarItems ?? [] as $item)
                    @include('layouts.partials.sidebar-item', ['item' => $item])
                @endforeach
            </ul>
        </div>
    </div>

    <div class="sidebar-footer hm-figma-sidebar-user">
        <div class="hm-figma-sidebar-user__row">
            <div class="hm-figma-sidebar-user__identity">
                <span class="hm-figma-sidebar-user__avatar" aria-hidden="true">{{ $userInitials ?? 'U' }}</span>
                <div class="hm-figma-sidebar-user__meta">
                    <p class="hm-figma-sidebar-user__name">{{ $userName ?? '' }}</p>
                    <p class="hm-figma-sidebar-user__role">{{ $userRoleLabel }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="m-0" data-hm-logout-form>
                @csrf
                <button type="submit" class="hm-figma-sidebar-user__logout" aria-label="{{ __('dashboard.logout') }}">
                    <i class="bi bi-box-arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

<div class="hm-logout-confirm" data-hm-logout-dialog hidden>
    <button type="button" class="hm-logout-confirm__backdrop" data-hm-logout-cancel tabindex="-1" aria-label="{{ __('dashboard.confirm_logout_cancel') }}"></button>
    <div class="hm-logout-confirm__center">
        <section class="hm-logout-confirm__card" role="dialog" aria-modal="true" aria-labelledby="hmLogoutConfirmTitle" aria-describedby="hmLogoutConfirmMessage">
            <div class="hm-logout-confirm__accent" aria-hidden="true"></div>
            <header class="hm-logout-confirm__header">
                <div class="hm-logout-confirm__heading">
                    <span class="hm-logout-confirm__icon" aria-hidden="true">
                        <i class="bi bi-box-arrow-right"></i>
                    </span>
                    <div>
                        <h2 id="hmLogoutConfirmTitle">{{ __('dashboard.confirm_logout_title') }}</h2>
                        <p>{{ __('dashboard.confirm_logout_subtitle') }}</p>
                    </div>
                </div>
                <button type="button" class="hm-logout-confirm__close" data-hm-logout-cancel aria-label="{{ __('dashboard.confirm_logout_cancel') }}">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="hm-logout-confirm__body">
                <p id="hmLogoutConfirmMessage">{{ __('dashboard.confirm_logout') }}</p>
            </div>
            <footer class="hm-logout-confirm__actions">
                <button type="button" class="hm-logout-confirm__button hm-logout-confirm__button--cancel" data-hm-logout-cancel>
                    {{ __('dashboard.confirm_logout_cancel') }}
                </button>
                <button type="button" class="hm-logout-confirm__button hm-logout-confirm__button--submit" data-hm-logout-submit>
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    {{ __('dashboard.confirm_logout_action') }}
                </button>
            </footer>
        </section>
    </div>
</div>
