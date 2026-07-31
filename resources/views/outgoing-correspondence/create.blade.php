@extends('layouts.app')

@section('title', __('outgoing_correspondence.create'))
@section('sidebar_heading', __('outgoing_correspondence.title'))
@section('sidebar_subheading', __('outgoing_correspondence.create_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.outgoing-correspondence.index') }}">{{ __('outgoing_correspondence.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('outgoing_correspondence.create') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('outgoing_correspondence.create') }}</h1>
                <p>{{ __('outgoing_correspondence.create_subtitle') }}</p>
            </div>
        </div>

        <section class="gc-panel">
            <form method="POST" action="{{ route('modules.outgoing-correspondence.store') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="gc-form-grid">
                    <div class="gc-field gc-span-2">
                        <label for="template_id">{{ __('outgoing_correspondence.fields.template') }}</label>
                        <select id="template_id" class="form-select">
                            <option value="">{{ __('outgoing_correspondence.fields.template_none') }}</option>
                            @foreach ($templateOptions as $template)
                                <option value="{{ $template->id }}">
                                    {{ $template->localizedTitle() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="gc-field">
                        <label for="branch_id">{{ __('outgoing_correspondence.fields.branch') }}</label>
                        <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($branchOptions as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                    {{ $branch->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="sector_id">{{ __('outgoing_correspondence.fields.sector') }}</label>
                        <select id="sector_id" name="sector_id" class="form-select @error('sector_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($sectorOptions as $sector)
                                <option value="{{ $sector->id }}" @selected(old('sector_id') == $sector->id)>
                                    {{ $sector->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('sector_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="authority_id">{{ __('outgoing_correspondence.fields.authority') }}</label>
                        <select id="authority_id" name="authority_id" class="form-select @error('authority_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($authorityOptions as $authority)
                                <option value="{{ $authority->id }}" @selected(old('authority_id') == $authority->id)>
                                    {{ $authority->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('authority_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="sender_title_id">{{ __('outgoing_correspondence.fields.sender_title') }}</label>
                        <select id="sender_title_id" name="sender_title_id" class="form-select @error('sender_title_id') is-invalid @enderror">
                            <option value="">—</option>
                            @foreach ($senderTitleOptions as $title)
                                <option value="{{ $title->id }}" @selected(old('sender_title_id') == $title->id)>
                                    {{ $title->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('sender_title_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="recipient_name">{{ __('outgoing_correspondence.fields.recipient_name') }}</label>
                        <input id="recipient_name" type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="form-control @error('recipient_name') is-invalid @enderror" maxlength="400" required>
                        @error('recipient_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="sender_gender">{{ __('outgoing_correspondence.fields.sender_gender') }}</label>
                        <select id="sender_gender" name="sender_gender" class="form-select @error('sender_gender') is-invalid @enderror">
                            <option value="">—</option>
                            <option value="1" @selected(old('sender_gender') === '1')>{{ __('outgoing_correspondence.fields.male') }}</option>
                            <option value="2" @selected(old('sender_gender') === '2')>{{ __('outgoing_correspondence.fields.female') }}</option>
                        </select>
                        @error('sender_gender') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="job_title">{{ __('outgoing_correspondence.fields.job_title') }}</label>
                        <input id="job_title" type="text" name="job_title" value="{{ old('job_title') }}" class="form-control @error('job_title') is-invalid @enderror" maxlength="1000">
                        @error('job_title') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="receiving_mechanism_id">{{ __('outgoing_correspondence.fields.receiving_mechanism') }}</label>
                        <select id="receiving_mechanism_id" name="receiving_mechanism_id" class="form-select @error('receiving_mechanism_id') is-invalid @enderror">
                            <option value="">—</option>
                            @foreach ($receivingMechanismOptions as $mechanism)
                                <option value="{{ $mechanism->id }}" @selected(old('receiving_mechanism_id') == $mechanism->id)>
                                    {{ $mechanism->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('receiving_mechanism_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="issue_date">{{ __('outgoing_correspondence.fields.issue_date') }}</label>
                        <input id="issue_date" type="datetime-local" name="issue_date" value="{{ old('issue_date') }}" class="form-control @error('issue_date') is-invalid @enderror" required>
                        @error('issue_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="response_deadline">{{ __('outgoing_correspondence.fields.response_deadline') }}</label>
                        <input id="response_deadline" type="datetime-local" name="response_deadline" value="{{ old('response_deadline') }}" class="form-control @error('response_deadline') is-invalid @enderror">
                        @error('response_deadline') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="subject">{{ __('outgoing_correspondence.fields.subject') }}</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror" maxlength="1000" required>
                        @error('subject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="letter_content">{{ __('outgoing_correspondence.fields.letter_content') }}</label>
                        <textarea id="letter_content" name="letter_content" rows="10" class="form-control @error('letter_content') is-invalid @enderror" required>{{ old('letter_content') }}</textarea>
                        @error('letter_content') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="attachment_files">{{ __('outgoing_correspondence.fields.attachments') }}</label>
                        <input id="attachment_files" type="file" name="attachment_files[]" class="form-control @error('attachment_files') is-invalid @enderror @error('attachment_files.*') is-invalid @enderror" multiple>
                        @error('attachment_files') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('attachment_files.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <div class="gc-decl">
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_recipient') is-invalid @enderror" type="checkbox" name="confirm_recipient" id="confirm_recipient" value="1" @checked(old('confirm_recipient'))>
                                <label class="form-check-label" for="confirm_recipient">{{ __('outgoing_correspondence.declarations.confirm_recipient') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_content') is-invalid @enderror" type="checkbox" name="confirm_content" id="confirm_content" value="1" @checked(old('confirm_content'))>
                                <label class="form-check-label" for="confirm_content">{{ __('outgoing_correspondence.declarations.confirm_content') }}</label>
                            </div>
                            @error('confirm_recipient') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('confirm_content') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('outgoing_correspondence.actions.save') }}</button>
                    <a href="{{ route('modules.outgoing-correspondence.index') }}" class="btn btn-outline-secondary">{{ __('outgoing_correspondence.actions.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    @php
        $templatesForJs = $templateOptions->mapWithKeys(fn ($t) => [
            (string) $t->id => [
                'title' => (string) $t->title,
                'content' => (string) $t->letter_content,
            ],
        ]);
    @endphp
    <script>
        (function () {
            var template = document.getElementById('template_id');
            var subject = document.getElementById('subject');
            var content = document.getElementById('letter_content');
            if (!template || !subject || !content) return;

            var templates = @json($templatesForJs);

            template.addEventListener('change', function () {
                var selected = templates[template.value];
                if (!selected) return;
                if (selected.title) subject.value = selected.title;
                if (selected.content) content.value = selected.content;
            });
        })();
    </script>
@endpush
