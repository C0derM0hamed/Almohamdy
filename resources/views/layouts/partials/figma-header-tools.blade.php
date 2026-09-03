@php
    $isRtl = app()->getLocale() === 'ar';
    $currentLanguage = $isRtl ? __('dashboard.language_ar_native') : __('dashboard.language_en_native');
@endphp

<div class="hm-figma-tools">
    <div class="dropdown">
        <button
            type="button"
            class="hm-figma-tools__icon"
            id="hmFigmaSettings"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="{{ __('dashboard.settings') }}"
        >
            <img src="{{ asset('images/figma/header/settings.svg') }}" alt="" width="18" height="18">
        </button>
        <ul class="dropdown-menu dropdown-menu-start hm-figma-tools__menu" aria-labelledby="hmFigmaSettings">
            <li>
                <a class="dropdown-item" href="{{ route('modules.settings.index') }}">
                    {{ __('dashboard.settings') }}
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('profile.password.edit') }}">
                    {{ __('dashboard.change_password') }}
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('modules.legacy-office.signature.edit') }}">
                    {{ __('dashboard.signature') }}
                </a>
            </li>
        </ul>
    </div>

    <div class="dropdown">
        <button
            type="button"
            class="hm-figma-tools__icon"
            id="hmFigmaNotify"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="{{ __('dashboard.notifications') }}"
        >
            <img src="{{ asset('images/figma/header/notifications.svg') }}" alt="" width="18" height="18">
            @if (($notificationCount ?? count($notifications ?? [])) > 0)
                <span class="hm-figma-tools__dot" aria-hidden="true"></span>
            @endif
        </button>
        <div class="dropdown-menu dropdown-menu-start hm-figma-tools__menu" aria-labelledby="hmFigmaNotify">
            <div class="hm-figma-tools__menu-title">{{ __('dashboard.notifications') }}</div>
            @forelse ($notifications ?? [] as $notification)
                <div class="hm-figma-tools__menu-item">{{ $notification['title'] ?? '' }}</div>
            @empty
                <div class="hm-figma-tools__menu-empty">{{ __('dashboard.no_notifications') }}</div>
            @endforelse
        </div>
    </div>

    <div class="dropdown">
        <button
            type="button"
            class="hm-figma-tools__lang"
            id="hmFigmaLang"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="{{ __('dashboard.language') }}"
        >
            <img src="{{ asset('images/figma/header/globe.svg') }}" alt="" width="18" height="18">
            <span>{{ $currentLanguage }}</span>
            <img src="{{ asset('images/figma/header/chevron-down.svg') }}" alt="" width="16" height="16">
        </button>
        <ul class="dropdown-menu dropdown-menu-start hm-figma-tools__menu" aria-labelledby="hmFigmaLang">
            <li>
                <a class="dropdown-item {{ $isRtl ? 'active' : '' }}" href="{{ route('lang.ar') }}" lang="ar" data-no-transition="true">
                    {{ __('dashboard.language_ar_native') }}
                </a>
            </li>
            <li>
                <a class="dropdown-item {{ $isRtl ? '' : 'active' }}" href="{{ route('lang.en') }}" lang="en" data-no-transition="true">
                    {{ __('dashboard.language_en_native') }}
                </a>
            </li>
        </ul>
    </div>
</div>
