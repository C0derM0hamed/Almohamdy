@extends('layouts.app')

@section('title', __('inquiries.create_title'))
@section('sidebar_heading', __('inquiries.title'))
@section('sidebar_subheading', __('inquiries.subtitle'))
@section('figma_page_header', 'true')

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inquiries.css') }}?v={{ filemtime(public_path('css/hm-inquiries.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-fm hm-gc hm-inq hm-inq-create">
        @include('layouts.partials.figma-module-header', [
            'compact' => true,
            'crumbs' => [
                ['label' => __('inquiries.outgoing'), 'url' => route('modules.inquiries.outgoing.index')],
                ['label' => __('inquiries.create_title')],
            ],
            'title' => __('inquiries.create_title'),
            'subtitle' => '',
        ])

        <div class="inq-create-hero">
            <span class="inq-create-hero__icon" aria-hidden="true">
                <img src="{{ asset('images/figma/inquiries/create.svg') }}" alt="" width="32" height="32">
            </span>
            <div>
                <h1>{{ __('inquiries.create_title') }}</h1>
                <p>{{ __('inquiries.create_subtitle') }}</p>
            </div>
        </div>

        <section class="gc-panel">
            <form method="POST" action="{{ route('modules.inquiries.outgoing.store') }}" novalidate>
                @csrf
                <div class="gc-form-grid">
                    <div class="gc-field">
                        <label for="enquirer">{{ __('inquiries.form_fields.enquirer') }}</label>
                        <input id="enquirer" name="enquirer" value="{{ old('enquirer') }}" maxlength="100" class="form-control @error('enquirer') is-invalid @enderror" required>
                        @error('enquirer') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="gc-field">
                        <label for="mobile">{{ __('inquiries.form_fields.mobile') }}</label>
                        <input id="mobile" name="mobile" value="{{ old('mobile') }}" type="tel" inputmode="numeric" maxlength="10" pattern="05[0-9]{8}" autocomplete="tel" class="form-control @error('mobile') is-invalid @enderror" required>
                        @error('mobile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="gc-field">
                        <label for="inquired_section">{{ __('inquiries.form_fields.department') }}</label>
                        <select id="inquired_section" name="inquired_section" class="form-select @error('inquired_section') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($departmentOptions as $department)
                                <option value="{{ $department->id }}" @selected(old('inquired_section') == $department->id)>{{ $department->legacyNavName() }}</option>
                            @endforeach
                        </select>
                        @error('inquired_section') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="gc-field">
                        <label for="job_title">{{ __('inquiries.form_fields.recipient_job_title') }}</label>
                        <select id="job_title" name="job_title" class="form-select @error('job_title') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($jobTitleOptions as $jobTitle)
                                <option value="{{ $jobTitle->id }}" data-branch="{{ $jobTitle->branch_id }}" @selected(old('job_title') == $jobTitle->id)>{{ $jobTitle->localizedName() }}</option>
                            @endforeach
                        </select>
                        @error('job_title') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="gc-field gc-span-2">
                        <label for="inquiry_id">{{ __('inquiries.form_fields.inquiry_type') }}</label>
                        <select id="inquiry_id" name="inquiry_id" class="form-select @error('inquiry_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($inquiryTypeOptions as $type)
                                <option value="{{ $type->id }}" @selected((string) old('inquiry_id') === (string) $type->id)>{{ $type->localizedName() }}</option>
                            @endforeach
                            <option value="0" @selected((string) old('inquiry_id') === '0')>{{ __('inquiries.other') }}</option>
                        </select>
                        @error('inquiry_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="gc-field gc-span-2" id="inquiryDetailsField">
                        <label for="inquiry_details">{{ __('inquiries.form_fields.details') }}</label>
                        <textarea id="inquiry_details" name="inquiry_details" maxlength="255" rows="4" class="form-control @error('inquiry_details') is-invalid @enderror">{{ old('inquiry_details') }}</textarea>
                        @error('inquiry_details') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="inq-create-actions">
                    <a class="btn btn-outline-secondary" href="{{ route('modules.inquiries.outgoing.index') }}">{{ __('inquiries.cancel') }}</a>
                    <button class="btn btn-primary" type="submit">{{ __('inquiries.save') }}</button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var branch = document.getElementById('inquired_section');
    var job = document.getElementById('job_title');
    if (!branch || !job) return;
    function syncJobs() {
        Array.prototype.forEach.call(job.options, function (option) {
            if (!option.value) return;
            var visible = option.getAttribute('data-branch') === branch.value;
            option.hidden = !visible;
            option.disabled = !visible;
            if (!visible && option.selected) job.value = '';
        });
    }
    branch.addEventListener('change', syncJobs);
    syncJobs();
})();
</script>
@endpush
