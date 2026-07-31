@php
    $modalMode = $modalMode ?? false;
@endphp

<div class="gc-receipt-body">
    @unless ($modalMode)
        <div class="gc-page-head">
            <div>
                <h1>{{ __('government_circulars.receipt.title') }}</h1>
                <p>{{ $circular->subject }}</p>
                <p class="mb-0 mt-2">
                    <span class="gc-badge" style="background:#1a4f86;">
                        {{ __('government_circulars.receipt.summary', ['viewed' => $viewedCount, 'total' => $reports->count()]) }}
                    </span>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2 no-print">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                    {{ __('government_circulars.receipt.print') }}
                </button>
                <a href="{{ route('modules.government-circulars.show', $circular->id) }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('government_circulars.back_to_detail') }}
                </a>
            </div>
        </div>
    @else
        <p class="mb-3">
            <span class="gc-badge" style="background:#1a4f86;">
                {{ __('government_circulars.receipt.summary', ['viewed' => $viewedCount, 'total' => $reports->count()]) }}
            </span>
        </p>
    @endunless

    <section class="gc-panel mb-3">
        <div class="gc-detail-grid">
            <div class="gc-detail-item">
                <span class="gc-detail-item__label">{{ __('government_circulars.fields.authority') }}</span>
                <span class="gc-detail-item__value">{{ $circular->authority?->localizedName() ?: '—' }}</span>
            </div>
            <div class="gc-detail-item">
                <span class="gc-detail-item__label">{{ __('government_circulars.fields.classification') }}</span>
                <span class="gc-detail-item__value">{{ $circular->classification?->localizedName() ?: '—' }}</span>
            </div>
            <div class="gc-detail-item">
                <span class="gc-detail-item__label">{{ __('government_circulars.fields.issue_date') }}</span>
                <span class="gc-detail-item__value">{{ optional($circular->issue_date)->format('Y-m-d') ?: '—' }}</span>
            </div>
            <div class="gc-detail-item">
                <span class="gc-detail-item__label">{{ __('government_circulars.fields.subject') }}</span>
                <span class="gc-detail-item__value">{{ $circular->subject ?: '—' }}</span>
            </div>
        </div>
    </section>

    <section class="gc-panel">
        <div class="gc-table-wrap">
            @if ($reports->isEmpty())
                <div class="gc-empty">{{ __('government_circulars.receipt.empty') }}</div>
            @else
                <table class="gc-table">
                    <thead>
                        <tr>
                            <th>{{ __('government_circulars.receipt.recipient') }}</th>
                            <th>{{ __('government_circulars.receipt.department') }}</th>
                            <th>{{ __('government_circulars.receipt.channels') }}</th>
                            <th>{{ __('government_circulars.receipt.viewing_status') }}</th>
                            <th>{{ __('government_circulars.receipt.sent_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reports as $report)
                            @php
                                $admin = $report->administrator;
                                $viewed = $report->hasBeenViewed();
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $admin?->displayName() ?: '—' }}</strong>
                                    <div class="text-muted small">{{ $admin?->email ?: '—' }}</div>
                                    <div class="text-muted small" dir="ltr">{{ $admin?->mobile ?: '—' }}</div>
                                </td>
                                <td>{{ $admin?->section?->localizedName() ?: '—' }}</td>
                                <td>
                                    <span class="badge text-bg-light border">Email</span>
                                    <span class="badge text-bg-light border">SMS</span>
                                </td>
                                <td>
                                    @if ($viewed)
                                        <span class="gc-badge" style="background:#15803d;">{{ __('government_circulars.receipt.viewed') }}</span>
                                        <div class="text-muted small mt-1">
                                            @if ($report->seen_by_email_at)
                                                Email: {{ $report->seen_by_email_at->format('Y-m-d H:i') }}
                                            @endif
                                            @if ($report->seen_by_sms_at)
                                                <div>SMS: {{ $report->seen_by_sms_at->format('Y-m-d H:i') }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="gc-badge" style="background:#b45309;">{{ __('government_circulars.receipt.not_viewed') }}</span>
                                    @endif
                                </td>
                                <td>{{ optional($report->created_at)->format('Y-m-d H:i') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</div>
