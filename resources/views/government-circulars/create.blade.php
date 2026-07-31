@extends('layouts.app')

@section('title', __('government_circulars.create'))
@section('sidebar_heading', __('government_circulars.title'))
@section('sidebar_subheading', __('government_circulars.create_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-gc">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.government-circulars.index') }}">{{ __('government_circulars.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('government_circulars.create') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('government_circulars.create') }}</h1>
                <p>{{ __('government_circulars.create_subtitle') }}</p>
            </div>
        </div>

        <section class="gc-panel">
            <form method="POST" action="{{ route('modules.government-circulars.store') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="gc-form-grid">
                    <div class="gc-field">
                        <label for="authority_id">{{ __('government_circulars.fields.authority') }}</label>
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
                        <label for="classification_id">{{ __('government_circulars.fields.classification') }}</label>
                        <select id="classification_id" name="classification_id" class="form-select @error('classification_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($classificationOptions as $classification)
                                <option
                                    value="{{ $classification->id }}"
                                    data-authority="{{ $classification->government_circulars_issuing_authority_id }}"
                                    @selected(old('classification_id') == $classification->id)
                                >
                                    {{ $classification->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('classification_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="issue_date">{{ __('government_circulars.fields.issue_date') }}</label>
                        <input id="issue_date" type="date" name="issue_date" value="{{ old('issue_date') }}" class="form-control @error('issue_date') is-invalid @enderror" required>
                        @error('issue_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="received_date">{{ __('government_circulars.fields.received_date') }}</label>
                        <input id="received_date" type="date" name="received_date" value="{{ old('received_date') }}" class="form-control @error('received_date') is-invalid @enderror" required>
                        @error('received_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="receiving_mechanism_id">{{ __('government_circulars.fields.receiving_mechanism') }}</label>
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
                        <label for="notification_type">{{ __('government_circulars.fields.notification_type') }}</label>
                        <select id="notification_type" name="notification_type" class="form-select @error('notification_type') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($notificationTypeOptions as $type)
                                <option value="{{ $type->id }}" @selected(old('notification_type') == $type->id)>
                                    {{ $type->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('notification_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="branch_id">{{ __('government_circulars.fields.branch') }}</label>
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
                        <label for="section_ids">{{ __('government_circulars.fields.section') }}</label>
                        <select id="section_ids" name="section_ids[]" class="form-select @error('section_ids') is-invalid @enderror @error('section_ids.*') is-invalid @enderror" multiple size="5" required>
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section->id }}" @selected(collect(old('section_ids', []))->contains($section->id))>
                                    {{ $section->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('government_circulars.fields.section_hint') }}</small>
                        @error('section_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('section_ids.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="cc_section_ids">{{ __('government_circulars.fields.cc_section') }}</label>
                        <select id="cc_section_ids" name="cc_section_ids[]" class="form-select @error('cc_section_ids') is-invalid @enderror @error('cc_section_ids.*') is-invalid @enderror" multiple size="5">
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section->id }}" @selected(collect(old('cc_section_ids', []))->contains($section->id))>
                                    {{ $section->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('government_circulars.fields.cc_section_hint') }}</small>
                        @error('cc_section_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="subject">{{ __('government_circulars.fields.subject') }}</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror" maxlength="400" required>
                        @error('subject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="circular_file">{{ __('government_circulars.fields.file') }}</label>
                        <input id="circular_file" type="file" name="circular_file" class="form-control @error('circular_file') is-invalid @enderror">
                        @error('circular_file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="circular_files">{{ __('government_circulars.fields.extra_files') }}</label>
                        <input id="circular_files" type="file" name="circular_files[]" class="form-control @error('circular_files') is-invalid @enderror @error('circular_files.*') is-invalid @enderror" multiple>
                        <small class="text-muted">{{ __('government_circulars.fields.extra_files_hint') }}</small>
                        @error('circular_files') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('circular_files.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <strong>{{ __('government_circulars.declarations.title') }}</strong>
                        <div class="gc-decl mt-2">
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_entity') is-invalid @enderror" type="checkbox" name="confirm_entity" id="confirm_entity" value="1" @checked(old('confirm_entity'))>
                                <label class="form-check-label" for="confirm_entity">{{ __('government_circulars.declarations.confirm_entity') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_subject') is-invalid @enderror" type="checkbox" name="confirm_subject" id="confirm_subject" value="1" @checked(old('confirm_subject'))>
                                <label class="form-check-label" for="confirm_subject">{{ __('government_circulars.declarations.confirm_subject') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_attachments') is-invalid @enderror" type="checkbox" name="confirm_attachments" id="confirm_attachments" value="1" @checked(old('confirm_attachments'))>
                                <label class="form-check-label" for="confirm_attachments">{{ __('government_circulars.declarations.confirm_attachments') }}</label>
                            </div>
                            @error('confirm_entity') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('confirm_subject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('confirm_attachments') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('government_circulars.actions.save') }}</button>
                    <a href="{{ route('modules.government-circulars.index') }}" class="btn btn-outline-secondary">{{ __('government_circulars.actions.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var authority = document.getElementById('authority_id');
            var classification = document.getElementById('classification_id');
            if (!authority || !classification) return;

            function syncClassifications() {
                var selectedAuthority = authority.value;
                Array.prototype.forEach.call(classification.options, function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    var match = option.getAttribute('data-authority') === selectedAuthority;
                    option.hidden = !match;
                    if (!match && option.selected) {
                        classification.value = '';
                    }
                });
            }

            authority.addEventListener('change', syncClassifications);
            syncClassifications();
        })();
    </script>
@endpush
