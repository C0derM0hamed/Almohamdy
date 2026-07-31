@extends('layouts.app')

@section('title', __('inquiries.details_title', ['id' => $inquiry->id]))
@section('sidebar_heading', __('inquiries.title'))
@section('sidebar_subheading', __('inquiries.subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-inquiries.css') }}?v={{ filemtime(public_path('css/hm-inquiries.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php $listRoute = $direction === 'incoming' ? route('modules.inquiries.incoming.index') : route('modules.inquiries.outgoing.index'); @endphp
    <div class="hm-inq hm-inq--timeline">
        <section class="inq-page-hero inq-page-hero--timeline">
            <div>
                <h1>{{ __('inquiries.details_title', ['id' => $inquiry->id]) }}</h1>
                <p>{{ $inquiry->enquirerDisplayName() }} — {{ $inquiry->mobile }}</p>
                <div class="inq-page-hero__actions">
                    <a href="{{ route('modules.inquiries.pdf', ['direction' => $direction, 'inquiry' => $inquiry->id]) }}" class="btn hm-btn hm-btn--outline hm-inq-btn">
                        <i class="bi bi-file-earmark-pdf"></i> {{ __('inquiries.download_pdf') }}
                    </a>
                </div>
            </div>
        </section>
        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @include('inquiries.partials.timeline-modal-body', compact('direction', 'inquiry', 'timeline', 'statusLabel', 'statusColor'))
        <div class="inq-back-row"><a href="{{ $listRoute }}" class="inq-back-btn">{{ __('inquiries.view_list') }}</a></div>
    </div>
@endsection
