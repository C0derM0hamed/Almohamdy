@extends('layouts.app')
@section('title', __('publications.details'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h4 mb-1">{{ $post->subject_ar }}</h1><p class="text-muted mb-0">{{ $post->type_name_ar }} · {{ $post->branch_name_ar }}</p></div><a class="btn btn-outline-secondary" href="{{ route('modules.publications.index') }}"><i class="bi bi-arrow-right"></i> {{ __('publications.back') }}</a></div><div class="card border-0 shadow-sm"><div class="card-body"><p class="mb-4">{!! nl2br(e($post->post_ar)) !!}</p>@if($post->post_en)<hr><p dir="ltr">{!! nl2br(e($post->post_en)) !!}</p>@endif@if($post->uploaded_file)<a class="btn btn-outline-primary" href="{{ route('modules.publications.download', $post->id) }}"><i class="bi bi-paperclip"></i> {{ __('publications.download') }}</a>@endif</div></div>
@endsection
