@php
    $sidebarHeading = trim($__env->yieldContent('sidebar_heading'));
    $sidebarSubheading = trim($__env->yieldContent('sidebar_subheading'));

    if ($sidebarHeading === '' && ! empty($sidebarContext['heading'] ?? null)) {
        $sidebarHeading = (string) $sidebarContext['heading'];
        $sidebarSubheading = (string) ($sidebarContext['subheading'] ?? '');
    }
@endphp

<aside id="hmAppSidebar" class="sidebar sidebar-default sidebar-white sidebar-base navs-rounded-all" data-toggle="main-sidebar" data-sidebar="responsive">
    <div class="sidebar-header d-flex align-items-center justify-content-start">
        <a href="{{ route($homeRoute ?? 'dashboard') }}" class="navbar-brand hm-hope-logo">
            <div class="logo-main">
                <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}" class="logo-normal">
                <img src="{{ asset('images/brand/hh-icon.png') }}" alt="{{ __('dashboard.brand_name') }}" class="logo-mini" style="height:32px;width:auto;">
            </div>
        </a>
    </div>

    <div class="sidebar-body pt-0 data-scrollbar">
        @if ($sidebarHeading !== '')
            <div class="hm-sidebar-context">
                <h1 class="hm-sidebar-context__title">{{ $sidebarHeading }}</h1>
                @if ($sidebarSubheading !== '')
                    <p class="hm-sidebar-context__subtitle">{{ $sidebarSubheading }}</p>
                @endif
            </div>
        @endif

        <div class="sidebar-list">
            <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
                <li class="nav-item static-item">
                    <a class="nav-link static-item disabled" href="#" tabindex="-1">
                        <span class="default-icon">{{ __('dashboard.modules') }}</span>
                        <span class="mini-icon">-</span>
                    </a>
                </li>

                @foreach ($sidebarItems ?? [] as $item)
                    @if ($item->isGroup && $item->hasChildren())
                        <li class="nav-item">
                            <a
                                href="#{{ $item->collapseId }}"
                                class="nav-link {{ $item->active ? 'active' : '' }}"
                                data-bs-toggle="collapse"
                                role="button"
                                aria-expanded="{{ $item->active ? 'true' : 'false' }}"
                                aria-controls="{{ $item->collapseId }}"
                            >
                                <i class="icon" aria-hidden="true">
                                    <i class="bi {{ $item->icon }}"></i>
                                </i>
                                <span class="item-name">{{ $item->title }}</span>
                                <i class="right-icon" aria-hidden="true">
                                    <i class="bi bi-chevron-down"></i>
                                </i>
                            </a>
                            <ul
                                class="sub-nav collapse {{ $item->active ? 'show' : '' }}"
                                id="{{ $item->collapseId }}"
                                data-bs-parent="#sidebar-menu"
                            >
                                @foreach ($item->children as $child)
                                    <li class="nav-item">
                                        <a
                                            href="{{ $child->url }}"
                                            class="nav-link {{ $child->active ? 'active' : '' }}"
                                            @if ($child->active) aria-current="page" @endif
                                        >
                                            <i class="icon" aria-hidden="true">
                                                <i class="bi {{ $child->icon }}"></i>
                                            </i>
                                            <span class="item-name">{{ $child->title }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a
                                href="{{ $item->url }}"
                                class="nav-link {{ $item->active ? 'active' : '' }}"
                                @if ($item->active) aria-current="page" @endif
                            >
                                <i class="icon" aria-hidden="true">
                                    <i class="bi {{ $item->icon }}"></i>
                                </i>
                                <span class="item-name">{{ $item->title }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

    <div class="sidebar-footer"></div>
</aside>
