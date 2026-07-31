@extends('layouts.public-reply')

@section('title', __('government_circulars.formal.result_title'))

@section('content')
    <div class="hm-public-reply__card text-center">
        <div class="hm-public-reply__card-body py-5">
            <div class="mb-3">
                <span class="hm-public-reply__attach-link" style="pointer-events:none; padding:0.85rem;" aria-hidden="true">
                    <i class="bi {{ $success ? 'bi-check-circle' : 'bi-exclamation-circle' }} fs-3"></i>
                </span>
            </div>
            <h1>{{ $success ? __('government_circulars.formal.result_success') : __('government_circulars.formal.result_error') }}</h1>
            <p class="subtitle mb-0">{{ $message }}</p>
        </div>
    </div>
@endsection
