@extends('layouts.app')

@section('title', __('correspondence.create'))
@section('sidebar_heading', __('correspondence.title'))
@section('sidebar_subheading', __('correspondence.create_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.correspondence.index') }}">{{ __('correspondence.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('correspondence.create') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('correspondence.create') }}</h1>
                <p>{{ __('correspondence.create_subtitle') }}</p>
            </div>
        </div>

        <section class="gc-panel">
            <form method="POST" action="{{ route('modules.correspondence.store') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="gc-form-grid">
                    <div class="gc-field">
                        <label for="branch_id">{{ __('correspondence.fields.branch') }}</label>
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
                        <label for="sector_id">{{ __('correspondence.fields.sector') }}</label>
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
                        <label for="authority_id">{{ __('correspondence.fields.authority') }}</label>
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
                        <label for="sender_title_id">{{ __('correspondence.fields.sender_title') }}</label>
                        <select id="sender_title_id" name="sender_title_id" class="form-select @error('sender_title_id') is-invalid @enderror" required>
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
                        <label for="sender_gender">{{ __('correspondence.fields.sender_gender') }}</label>
                        <select id="sender_gender" name="sender_gender" class="form-select @error('sender_gender') is-invalid @enderror" required>
                            <option value="">—</option>
                            <option value="1" @selected(old('sender_gender') === '1')>{{ __('correspondence.fields.male') }}</option>
                            <option value="2" @selected(old('sender_gender') === '2')>{{ __('correspondence.fields.female') }}</option>
                        </select>
                        @error('sender_gender') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="sender">{{ __('correspondence.fields.sender') }}</label>
                        <input id="sender" type="text" name="sender" value="{{ old('sender') }}" class="form-control @error('sender') is-invalid @enderror" maxlength="200" required>
                        @error('sender') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="job_title">{{ __('correspondence.fields.job_title') }}</label>
                        <input id="job_title" type="text" name="job_title" value="{{ old('job_title') }}" class="form-control @error('job_title') is-invalid @enderror" maxlength="200" required>
                        @error('job_title') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="receiving_mechanism_id">{{ __('correspondence.fields.receiving_mechanism') }}</label>
                        <select id="receiving_mechanism_id" name="receiving_mechanism_id" class="form-select @error('receiving_mechanism_id') is-invalid @enderror" required>
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
                        <label for="issue_date">{{ __('correspondence.fields.issue_date') }}</label>
                        <input id="issue_date" type="datetime-local" name="issue_date" value="{{ old('issue_date') }}" class="form-control @error('issue_date') is-invalid @enderror" required>
                        @error('issue_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="received_date">{{ __('correspondence.fields.received_date') }}</label>
                        <input id="received_date" type="datetime-local" name="received_date" value="{{ old('received_date') }}" class="form-control @error('received_date') is-invalid @enderror" required>
                        @error('received_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="subject">{{ __('correspondence.fields.subject') }}</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror" maxlength="500" required>
                        @error('subject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="section_id">{{ __('correspondence.fields.section') }}</label>
                        <select id="section_id" name="section_id" class="form-select @error('section_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section->id }}" @selected(old('section_id') == $section->id)>
                                    {{ $section->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('section_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="response_deadline">{{ __('correspondence.fields.response_deadline') }}</label>
                        <input id="response_deadline" type="datetime-local" name="response_deadline" value="{{ old('response_deadline') }}" class="form-control @error('response_deadline') is-invalid @enderror" required>
                        @error('response_deadline') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="attachment_files">{{ __('correspondence.fields.attachments') }}</label>
                        <input id="attachment_files" type="file" name="attachment_files[]" class="form-control @error('attachment_files') is-invalid @enderror @error('attachment_files.*') is-invalid @enderror" multiple>
                        @error('attachment_files') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('attachment_files.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <div class="gc-decl">
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_sender') is-invalid @enderror" type="checkbox" name="confirm_sender" id="confirm_sender" value="1" @checked(old('confirm_sender'))>
                                <label class="form-check-label" for="confirm_sender">{{ __('correspondence.declarations.confirm_sender') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_subject') is-invalid @enderror" type="checkbox" name="confirm_subject" id="confirm_subject" value="1" @checked(old('confirm_subject'))>
                                <label class="form-check-label" for="confirm_subject">{{ __('correspondence.declarations.confirm_subject') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_department') is-invalid @enderror" type="checkbox" name="confirm_department" id="confirm_department" value="1" @checked(old('confirm_department'))>
                                <label class="form-check-label" for="confirm_department">{{ __('correspondence.declarations.confirm_department') }}</label>
                            </div>
                            @error('confirm_sender') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('confirm_subject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('confirm_department') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('correspondence.actions.save') }}</button>
                    <a href="{{ route('modules.correspondence.index') }}" class="btn btn-outline-secondary">{{ __('correspondence.actions.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection
