@extends('layouts.public-reply')
@section('title', $recipient->notice->title)
@section('content')
<article class="hm-public-reply__card">
    <div class="hm-public-reply__card-header"><h1>{{ $recipient->notice->title }}</h1></div>
    <div class="hm-public-reply__card-body">
        <p>{{ $recipient->notice->description }}</p>
        <div class="hm-public-reply__meta">
            <div class="hm-public-reply__meta-item"><span>{{ __('gov_accounts.fields.branch') }}</span><strong>{{ $recipient->notice->hospitalBranch?->localizedName() ?? '—' }}</strong></div>
            <div class="hm-public-reply__meta-item"><span>{{ __('gov_accounts.fields.authority') }}</span><strong>{{ $recipient->notice->authority?->localizedName() }}</strong></div>
            <div class="hm-public-reply__meta-item"><span>{{ __('gov_accounts.fields.event_date') }}</span><strong>{{ $recipient->notice->event_date?->format('Y-m-d') }} {{ $recipient->notice->event_time }}</strong></div>
            <div class="hm-public-reply__meta-item"><span>{{ __('gov_accounts.fields.attendance_method') }}</span><strong>{{ __('gov_accounts.attendance.'.$recipient->notice->attendance_method) }}</strong></div>
            @if($recipient->notice->location)<div class="hm-public-reply__meta-item"><span>{{ __('gov_accounts.fields.location') }}</span><strong>{{ $recipient->notice->location }}</strong></div>@endif
        </div>
        @if($recipient->notice->meeting_url)<p><a class="hm-public-reply__submit" rel="noopener noreferrer" href="{{ $recipient->notice->meeting_url }}">{{ __('gov_accounts.notices.join') }}</a></p>@endif
        @if($recipient->notice->notes)<p>{{ $recipient->notice->notes }}</p>@endif
    </div>
</article>
@endsection
