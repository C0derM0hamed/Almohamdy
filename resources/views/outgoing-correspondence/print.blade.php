<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('outgoing_correspondence.print.title') }} — {{ $item->displayNumber() }}</title>
    <link href="{{ asset('css/hm-fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('css/hm-print.css') }}?v={{ filemtime(public_path('css/hm-print.css')) }}" rel="stylesheet">
</head>
<body class="hm-print">
    <div class="hm-print__toolbar no-print">
        <button type="button" onclick="window.print()">{{ __('outgoing_correspondence.print.print') }}</button>
        <a href="{{ route('modules.outgoing-correspondence.show', $item->id) }}">{{ __('outgoing_correspondence.back_to_detail') }}</a>
    </div>

    <article class="hm-print__sheet">
        <header class="hm-print__header">
            <img src="{{ asset('images/brand/hh-logo-horizontal.png') }}" alt="{{ __('dashboard.brand_name') }}">
            <div>
                <h1>{{ __('outgoing_correspondence.print.title') }}</h1>
                <p>{{ $item->displayNumber() }}</p>
            </div>
        </header>

        <dl class="hm-print__meta">
            <div><dt>{{ __('outgoing_correspondence.fields.issue_date') }}</dt><dd>{{ optional($item->issue_date)->format('Y-m-d') ?: '—' }}</dd></div>
            <div><dt>{{ __('outgoing_correspondence.fields.authority') }}</dt><dd>{{ $item->authority?->localizedName() ?: '—' }}</dd></div>
            <div><dt>{{ __('outgoing_correspondence.fields.recipient_name') }}</dt><dd>{{ $item->sender ?: '—' }}</dd></div>
            <div><dt>{{ __('outgoing_correspondence.fields.branch') }}</dt><dd>{{ $item->branch?->localizedName() ?: '—' }}</dd></div>
            <div class="hm-print__full"><dt>{{ __('outgoing_correspondence.fields.subject') }}</dt><dd>{{ $item->subject() ?: '—' }}</dd></div>
        </dl>

        <section class="hm-print__body">
            <h2>{{ __('outgoing_correspondence.fields.letter_content') }}</h2>
            <div class="hm-print__content">{!! nl2br(e($item->letter_content ?: '—')) !!}</div>
        </section>
    </article>
</body>
</html>
