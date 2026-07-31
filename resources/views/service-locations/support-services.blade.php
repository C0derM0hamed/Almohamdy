@extends('layouts.app')

@section('title', __('service_locations.support_services'))

@section('sidebar_heading', $label)
@section('sidebar_subheading', __('service_locations.support_services_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-services-redesign.css') }}?v={{ filemtime(public_path('css/hm-services-redesign.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-service-locations.css') }}?v={{ filemtime(public_path('css/hm-service-locations.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-hs">
        @include('hospital-services.partials.hs-breadcrumb', [
            'items' => [
                ['label' => __('service_locations.title'), 'url' => route('modules.service-locations.index')],
                ['label' => $label, 'url' => route('modules.service-locations.show', $location->id)],
                ['label' => __('service_locations.support_services'), 'chip' => true],
            ],
        ])

        <section class="hs-page-hero" aria-labelledby="supportServicesTitle">
            <div>
                <h1 id="supportServicesTitle">{{ __('service_locations.support_services') }}</h1>
                <p>{{ __('service_locations.support_services_subtitle') }}</p>
            </div>
            <div class="hs-page-hero-art" aria-hidden="true"></div>
        </section>

        @if ($items->count() > 0)
            <div class="sl-support-grid">
                @foreach ($items as $item)
                    <article class="sl-support-card">
                        <header class="sl-support-card__header">
                            <span class="sl-support-card__icon" aria-hidden="true">
                                <i class="bi bi-heart-pulse"></i>
                            </span>
                            <h2 class="sl-support-card__title">{{ trim((string) $item->service_name) }}</h2>
                        </header>

                        <dl class="sl-support-card__details">
                            <div class="sl-support-detail">
                                <dt>
                                    <i class="bi bi-door-closed" aria-hidden="true"></i>
                                    {{ __('service_locations.room_number') }}
                                </dt>
                                <dd>{{ trim((string) ($item->room_number ?? '')) ?: '—' }}</dd>
                            </div>

                            <div class="sl-support-detail">
                                <dt>
                                    <i class="bi bi-telephone" aria-hidden="true"></i>
                                    {{ __('service_locations.extension_number') }}
                                </dt>
                                <dd>{{ trim((string) ($item->phone_ext ?? '')) ?: '—' }}</dd>
                            </div>

                            <div class="sl-support-detail">
                                <dt>
                                    <i class="bi bi-calendar-week" aria-hidden="true"></i>
                                    {{ __('service_locations.working_days') }}
                                </dt>
                                <dd>{{ trim((string) ($item->duty_days ?? '')) ?: '—' }}</dd>
                            </div>

                            <div class="sl-support-detail">
                                <dt>
                                    <i class="bi bi-clock" aria-hidden="true"></i>
                                    {{ __('service_locations.working_hours') }}
                                </dt>
                                <dd>{{ trim((string) ($item->duty_time ?? '')) ?: '—' }}</dd>
                            </div>

                            @if (trim((string) ($item->notice ?? '')) !== '')
                                <div class="sl-support-detail sl-support-detail--full">
                                    <dt>
                                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                                        {{ __('service_locations.notice') }}
                                    </dt>
                                    <dd>{{ trim((string) $item->notice) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </article>
                @endforeach
            </div>
        @else
            <div class="hs-empty">
                <i class="bi bi-life-preserver" aria-hidden="true"></i>
                <p class="mb-0">{{ __('service_locations.no_support_services') }}</p>
            </div>
        @endif

        <div class="sl-page-actions">
            <a href="{{ route('modules.service-locations.show', $location->id) }}" class="hs-btn hs-btn--ghost">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ __('service_locations.back_to_opd') }}
            </a>
        </div>
    </div>
@endsection
