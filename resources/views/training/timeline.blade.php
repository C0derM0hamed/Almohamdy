@extends('layouts.app')
@section('title', __('training.timeline'))
@push('styles')
<link href="{{ asset('css/hm-detail-stat-cards.css') }}?v={{ filemtime(public_path('css/hm-detail-stat-cards.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-module-page"><div class="d-flex justify-content-between mb-4"><h1 class="h3">{{ __('training.timeline') }} #{{ $training->id }}</h1><a class="btn btn-outline-secondary" href="{{ route($routes['show'], $training->id) }}">{{ __('training.back') }}</a></div>
<div class="hm-detail-stats">
    <article class="hm-detail-stat hm-detail-stat--primary">
        <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-person"></i></span>
        <span class="hm-detail-stat__copy"><span class="hm-detail-stat__label">{{ __('training.employee') }}</span><strong class="hm-detail-stat__value">{{ $training->employee?->displayName() ?: '—' }}</strong><span class="hm-detail-stat__meta">{{ $training->employee?->hr_username ?: '#'.$training->id }}</span></span>
    </article>
    <article class="hm-detail-stat hm-detail-stat--dark">
        <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
        <span class="hm-detail-stat__copy"><span class="hm-detail-stat__label">{{ __('training.branch') }}</span><strong class="hm-detail-stat__value">{{ $training->branch?->localizedName() ?? '—' }}</strong><span class="hm-detail-stat__meta">{{ __('training.timeline') }}</span></span>
    </article>
    <article class="hm-detail-stat hm-detail-stat--primary">
        <span class="hm-detail-stat__icon" aria-hidden="true"><i class="bi bi-activity"></i></span>
        <span class="hm-detail-stat__copy"><span class="hm-detail-stat__label">{{ __('training.status') }}</span><strong class="hm-detail-stat__value">{{ \App\Support\LocaleText::localizedValue($training->currentStatus?->name_ar ?? null, $training->currentStatus?->name_en ?? null) ?: $training->status }}</strong><span class="hm-detail-stat__meta">#{{ $training->id }}</span></span>
    </article>
</div>
<div class="vstack gap-3">@foreach($timeline as $item)<div class="card"><div class="card-body"><div class="d-flex justify-content-between"><strong>{{ \App\Support\LocaleText::localizedValue($item->status?->name_ar ?? null, $item->status?->name_en ?? null) ?: $item->status_id }}</strong><time>{{ $item->created_at }}</time></div>@if(trim((string)$item->details)!=='')<p class="mt-2 mb-1"><strong>{{ __('training.reason') }}:</strong> {{ $item->details }}</p>@endif<p class="text-muted mb-0">{{ __('training.editor') }}: {{ $item->author?->displayName() ?? ($item->created_by == 0 ? $training->employee?->displayName() : '—') }}</p></div></div>@endforeach</div></div>
@endsection
