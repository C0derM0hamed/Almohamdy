@extends('layouts.app')

@section('title', __('inspection_visits.create'))
@section('sidebar_heading', __('inspection_visits.title'))
@section('sidebar_subheading', __('inspection_visits.create_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    @php
        $oldFindings = old('findings', []);
        if (! is_array($oldFindings) || $oldFindings === []) {
            $oldFindings = [['type' => 1, 'title' => '']];
        }
    @endphp

    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.inspection-visits.index') }}">{{ __('inspection_visits.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('inspection_visits.create') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('inspection_visits.create') }}</h1>
                <p>{{ __('inspection_visits.create_subtitle') }}</p>
            </div>
        </div>

        <section class="gc-panel">
            <form method="POST" action="{{ route('modules.inspection-visits.store') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="gc-form-grid">
                    <div class="gc-field">
                        <label for="visit_type_id">{{ __('inspection_visits.fields.visit_type') }}</label>
                        <select id="visit_type_id" name="visit_type_id" class="form-select @error('visit_type_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($visitTypeOptions as $type)
                                <option value="{{ $type->id }}" @selected(old('visit_type_id') == $type->id)>
                                    {{ $type->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('visit_type_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="branch_id">{{ __('inspection_visits.fields.branch') }}</label>
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
                        <label for="visit_date">{{ __('inspection_visits.fields.visit_date') }}</label>
                        <input id="visit_date" type="datetime-local" name="visit_date" value="{{ old('visit_date') }}" class="form-control @error('visit_date') is-invalid @enderror" required>
                        @error('visit_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="authority_id">{{ __('inspection_visits.fields.authority') }}</label>
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
                        <label for="section_id">{{ __('inspection_visits.fields.section') }}</label>
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
                        <label for="representative_name">{{ __('inspection_visits.fields.representative_name') }}</label>
                        <input id="representative_name" type="text" name="representative_name" value="{{ old('representative_name') }}" class="form-control @error('representative_name') is-invalid @enderror" maxlength="255">
                        @error('representative_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="subject">{{ __('inspection_visits.fields.subject') }}</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror" maxlength="255" required>
                        @error('subject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="report">{{ __('inspection_visits.fields.report') }}</label>
                        <textarea id="report" name="report" rows="4" class="form-control @error('report') is-invalid @enderror">{{ old('report') }}</textarea>
                        @error('report') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="abuses_status">{{ __('inspection_visits.fields.abuses_status') }}</label>
                        <select id="abuses_status" name="abuses_status" class="form-select @error('abuses_status') is-invalid @enderror" required>
                            <option value="2" @selected(old('abuses_status', '2') === '2')>{{ __('inspection_visits.fields.no') }}</option>
                            <option value="1" @selected(old('abuses_status') === '1')>{{ __('inspection_visits.fields.yes') }}</option>
                        </select>
                        @error('abuses_status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="notes_status">{{ __('inspection_visits.fields.notes_status') }}</label>
                        <select id="notes_status" name="notes_status" class="form-select @error('notes_status') is-invalid @enderror" required>
                            <option value="2" @selected(old('notes_status', '2') === '2')>{{ __('inspection_visits.fields.no') }}</option>
                            <option value="1" @selected(old('notes_status') === '1')>{{ __('inspection_visits.fields.yes') }}</option>
                        </select>
                        @error('notes_status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field" id="replyTimeWrap" hidden>
                        <label for="reply_time">{{ __('inspection_visits.fields.reply_time') }}</label>
                        <input id="reply_time" type="datetime-local" name="reply_time" value="{{ old('reply_time') }}" class="form-control @error('reply_time') is-invalid @enderror">
                        @error('reply_time') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2" id="findingsWrap" hidden>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <strong>{{ __('inspection_visits.fields.findings') }}</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addFindingBtn">
                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                                {{ __('inspection_visits.actions.add_finding') }}
                            </button>
                        </div>

                        <div id="findingsList" class="iv-findings">
                            @foreach ($oldFindings as $index => $finding)
                                <div class="iv-finding-row" data-finding-row>
                                    <select name="findings[{{ $index }}][type]" class="form-select">
                                        <option value="1" @selected((int) ($finding['type'] ?? 1) === 1)>{{ __('inspection_visits.fields.violation') }}</option>
                                        <option value="2" @selected((int) ($finding['type'] ?? 1) === 2)>{{ __('inspection_visits.fields.note') }}</option>
                                    </select>
                                    <input
                                        type="text"
                                        name="findings[{{ $index }}][title]"
                                        value="{{ $finding['title'] ?? '' }}"
                                        class="form-control"
                                        placeholder="{{ __('inspection_visits.fields.finding_title') }}"
                                    >
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-finding aria-label="{{ __('inspection_visits.actions.remove_finding') }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        @error('findings') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="attachments">{{ __('inspection_visits.fields.attachments') }}</label>
                        <input id="attachments" type="file" name="attachments[]" class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror" multiple>
                        @error('attachments') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('attachments.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <div class="form-check">
                            <input class="form-check-input @error('confirm_details') is-invalid @enderror" type="checkbox" name="confirm_details" id="confirm_details" value="1" @checked(old('confirm_details'))>
                            <label class="form-check-label" for="confirm_details">{{ __('inspection_visits.declarations.confirm_details') }}</label>
                        </div>
                        @error('confirm_details') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('inspection_visits.actions.save') }}</button>
                    <a href="{{ route('modules.inspection-visits.index') }}" class="btn btn-outline-secondary">{{ __('inspection_visits.actions.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var abuses = document.getElementById('abuses_status');
            var notes = document.getElementById('notes_status');
            var replyWrap = document.getElementById('replyTimeWrap');
            var findingsWrap = document.getElementById('findingsWrap');
            var findingsList = document.getElementById('findingsList');
            var addBtn = document.getElementById('addFindingBtn');
            if (!abuses || !notes || !replyWrap || !findingsWrap || !findingsList || !addBtn) return;

            function syncVisibility() {
                var hasViolations = abuses.value === '1';
                var hasNotes = notes.value === '1';
                replyWrap.hidden = !hasViolations;
                findingsWrap.hidden = !(hasViolations || hasNotes);
            }

            function nextIndex() {
                return findingsList.querySelectorAll('[data-finding-row]').length;
            }

            function bindRemove(row) {
                var btn = row.querySelector('[data-remove-finding]');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    if (findingsList.querySelectorAll('[data-finding-row]').length <= 1) {
                        row.querySelector('input[type="text"]').value = '';
                        return;
                    }
                    row.remove();
                });
            }

            addBtn.addEventListener('click', function () {
                var index = nextIndex();
                var row = document.createElement('div');
                row.className = 'iv-finding-row';
                row.setAttribute('data-finding-row', '');
                row.innerHTML =
                    '<select name="findings[' + index + '][type]" class="form-select">' +
                    '<option value="1">{{ __('inspection_visits.fields.violation') }}</option>' +
                    '<option value="2">{{ __('inspection_visits.fields.note') }}</option>' +
                    '</select>' +
                    '<input type="text" name="findings[' + index + '][title]" class="form-control" placeholder="{{ __('inspection_visits.fields.finding_title') }}">' +
                    '<button type="button" class="btn btn-outline-danger btn-sm" data-remove-finding aria-label="{{ __('inspection_visits.actions.remove_finding') }}">' +
                    '<i class="bi bi-trash" aria-hidden="true"></i></button>';
                findingsList.appendChild(row);
                bindRemove(row);
            });

            findingsList.querySelectorAll('[data-finding-row]').forEach(bindRemove);
            abuses.addEventListener('change', syncVisibility);
            notes.addEventListener('change', syncVisibility);
            syncVisibility();
        })();
    </script>
@endpush
