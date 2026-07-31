@extends('layouts.public-reply')

@section('title', __('correspondence.department_reply.result_title'))

@section('content')
    <div class="hm-public-reply__card text-center">
        <h1>{{ $success ? __('correspondence.department_reply.result_success') : __('correspondence.department_reply.result_error') }}</h1>
        <p class="subtitle mb-0">{{ $message }}</p>
    </div>
@endsection
