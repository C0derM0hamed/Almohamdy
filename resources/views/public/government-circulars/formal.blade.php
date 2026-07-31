@extends('layouts.public-reply')

@section('title', __('government_circulars.formal.title'))

@section('content')
    <div class="hm-public-reply__card">
        <div class="hm-public-reply__card-header">
            <div class="d-flex align-items-start gap-3">
                <span class="hm-public-reply__attach-link" style="pointer-events:none; padding:0.65rem;" aria-hidden="true">
                    <i class="bi bi-file-earmark-text fs-5"></i>
                </span>
                <div>
                    <h1>{{ __('government_circulars.formal.title') }}</h1>
                    <p class="subtitle">{{ __('government_circulars.formal.subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="hm-public-reply__card-body">
            <div class="hm-public-reply__meta">
                <div class="hm-public-reply__meta-item">
                    <span>{{ __('government_circulars.fields.authority') }}</span>
                    <strong>{{ $circular->authority?->localizedName() ?: '—' }}</strong>
                </div>
                <div class="hm-public-reply__meta-item">
                    <span>{{ __('government_circulars.fields.classification') }}</span>
                    <strong>{{ $circular->classification?->localizedName() ?: '—' }}</strong>
                </div>
                <div class="hm-public-reply__meta-item">
                    <span>{{ __('government_circulars.fields.issue_date') }}</span>
                    <strong>{{ optional($circular->issue_date)->format('Y-m-d') ?: '—' }}</strong>
                </div>
                <div class="hm-public-reply__meta-item">
                    <span>{{ __('government_circulars.fields.received_date') }}</span>
                    <strong>{{ optional($circular->received_date)->format('Y-m-d H:i') ?: '—' }}</strong>
                </div>
                <div class="hm-public-reply__meta-item">
                    <span>{{ __('government_circulars.fields.receiving_mechanism') }}</span>
                    <strong>{{ $circular->receivingMechanism?->localizedName() ?: '—' }}</strong>
                </div>
                <div class="hm-public-reply__meta-item">
                    <span>{{ __('government_circulars.fields.notification_type') }}</span>
                    <strong>{{ $circular->notificationType?->localizedName() ?: '—' }}</strong>
                </div>
                <div class="hm-public-reply__meta-item">
                    <span>{{ __('government_circulars.fields.branch') }}</span>
                    <strong>{{ $circular->branch?->localizedName() ?: '—' }}</strong>
                </div>
                <div class="hm-public-reply__meta-item">
                    <span>{{ __('government_circulars.fields.section') }}</span>
                    <strong>{{ $sectionNames !== [] ? implode('، ', $sectionNames) : '—' }}</strong>
                </div>
                <div class="hm-public-reply__meta-item hm-public-reply__meta-item--full">
                    <span>{{ __('government_circulars.fields.subject') }}</span>
                    <strong>{{ $circular->subject ?: '—' }}</strong>
                </div>
            </div>

            <div class="alert alert-primary" role="status">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                {{ __('government_circulars.formal.instruction') }}
            </div>

            <h2 class="hm-public-reply__section-title">{{ __('government_circulars.fields.attachments') }}</h2>
            @if ($attachmentUrls === [])
                <p class="text-muted mb-0">{{ __('government_circulars.no_attachment') }}</p>
            @else
                <div class="hm-public-reply__attachments">
                    @foreach ($attachmentUrls as $index => $url)
                        <a class="hm-public-reply__attach-link" href="{{ $url }}" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            {{ $index === 0
                                ? __('government_circulars.formal.open_primary')
                                : __('government_circulars.formal.open_attachment', ['n' => $index]) }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
