@extends('layouts.public-reply')

@section('title', __('outgoing_correspondence.department_revise.result_title'))

@section('content')
    <div class="hm-public-reply__card text-center">
        <h1>{{ $success ? __('outgoing_correspondence.department_revise.result_success') : __('outgoing_correspondence.department_revise.result_error') }}</h1>
        <p class="subtitle mb-0">{{ $message }}</p>
    </div>
@endsection
