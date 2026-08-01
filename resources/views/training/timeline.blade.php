@extends('layouts.app')
@section('title', __('training.timeline'))
@section('content')
<div class="hm-module-page"><div class="d-flex justify-content-between mb-4"><h1 class="h3">{{ __('training.timeline') }} #{{ $training->id }}</h1><a class="btn btn-outline-secondary" href="{{ url()->previous() }}">{{ __('training.back') }}</a></div>
<div class="card mb-4"><div class="card-body"><strong>{{ __('training.employee') }}:</strong> {{ $training->employee?->displayName() }} — {{ $training->employee?->hr_username }}<br><strong>{{ __('training.branch') }}:</strong> {{ $training->branch?->localizedName() }}</div></div>
<div class="vstack gap-3">@foreach($timeline as $item)<div class="card"><div class="card-body"><div class="d-flex justify-content-between"><strong>{{ $item->status?->name_ar ?? $item->status_id }}</strong><time>{{ $item->created_at }}</time></div>@if(trim((string)$item->details)!=='')<p class="mt-2 mb-1"><strong>{{ __('training.reason') }}:</strong> {{ $item->details }}</p>@endif<p class="text-muted mb-0">{{ __('training.editor') }}: {{ $item->author?->displayName() ?? ($item->created_by == 0 ? $training->employee?->displayName() : '—') }}</p></div></div>@endforeach</div></div>
@endsection
