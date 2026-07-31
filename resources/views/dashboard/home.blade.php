@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('content')
    <div class="hm-hope-dashboard">
        <div class="card border-0 shadow-sm mb-4 hm-hope-welcome">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <p class="text-muted mb-1">{{ __('dashboard.hello') }}</p>
                        <h2 class="mb-1">{{ $userName }}</h2>
                        <p class="mb-0 text-muted">{{ __('dashboard.modules') }}</p>
                    </div>
                    <div class="hm-hope-welcome__icon" aria-hidden="true">
                        <i class="bi bi-hospital"></i>
                    </div>
                </div>
            </div>
        </div>

        @if (count($widgets) > 0)
            <div class="row g-3 mb-4">
                @foreach ($widgets as $widget)
                    <div class="col-sm-6 col-xl-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1 text-muted">{{ $widget->label }}</p>
                                        <h4 class="mb-0 counter">{{ $widget->value }}</h4>
                                    </div>
                                    <div class="hm-hope-stat-icon hm-hope-stat-icon--{{ $widget->variant }}">
                                        <i class="bi {{ $widget->icon }}" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="mb-1">{{ __('dashboard.quick_access') }}</h3>
                <p class="text-muted mb-0">{{ __('dashboard.modules') }}</p>
            </div>
        </div>

        <div class="row g-3">
            @forelse ($cards as $card)
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <a href="{{ $card->url }}" class="text-decoration-none hm-hope-module-link">
                        <div class="card h-100 hm-hope-module-card">
                            <div class="card-body d-flex flex-column">
                                <div class="hm-hope-module-card__icon">
                                    <i class="bi {{ $card->icon }}" aria-hidden="true"></i>
                                </div>
                                <h5 class="mt-3 mb-2 text-dark">{{ $card->title }}</h5>
                                @if (! empty($card->description))
                                    <p class="text-muted small mb-0 flex-grow-1">{{ $card->description }}</p>
                                @endif
                                <span class="hm-hope-module-card__arrow mt-3" aria-hidden="true">
                                    <i class="bi bi-arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5 text-muted">
                            <i class="bi bi-grid fs-2 d-block mb-2" aria-hidden="true"></i>
                            <p class="mb-0">{{ __('dashboard.coming_soon') }}</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
