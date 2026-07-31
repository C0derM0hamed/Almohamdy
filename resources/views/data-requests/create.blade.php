@extends('layouts.app')

@section('title', __('data_requests.create'))
@section('sidebar_heading', __('data_requests.title'))
@section('sidebar_subheading', __('data_requests.create_subtitle'))

@push('styles')
    <link href="{{ asset('css/hm-government-circulars.css') }}?v={{ filemtime(public_path('css/hm-government-circulars.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-inspection-visits.css') }}?v={{ filemtime(public_path('css/hm-inspection-visits.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-gc hm-iv">
        <nav class="gc-breadcrumb" aria-label="breadcrumb">
            <a href="{{ route($homeRoute) }}">{{ __('dashboard.title') }}</a>
            <span>/</span>
            <a href="{{ route('modules.data-requests.index') }}">{{ __('data_requests.list') }}</a>
            <span>/</span>
            <span class="is-chip">{{ __('data_requests.create') }}</span>
        </nav>

        <div class="gc-page-head">
            <div>
                <h1>{{ __('data_requests.create') }}</h1>
                <p>{{ __('data_requests.create_subtitle') }}</p>
            </div>
        </div>

        <section class="gc-panel">
            <form method="POST" action="{{ route('modules.data-requests.store') }}" enctype="multipart/form-data" novalidate>
                @csrf

                <div class="gc-form-grid">
                    <div class="gc-field">
                        <label for="branch_id">{{ __('data_requests.fields.branch') }}</label>
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
                        <label for="entity_id">{{ __('data_requests.fields.entity') }}</label>
                        <select id="entity_id" name="entity_id" class="form-select @error('entity_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($entityOptions as $entity)
                                <option value="{{ $entity->id }}" @selected(old('entity_id') == $entity->id)>
                                    {{ $entity->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('entity_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="request_date">{{ __('data_requests.fields.request_date') }}</label>
                        <input id="request_date" type="date" name="request_date" value="{{ old('request_date') }}" class="form-control @error('request_date') is-invalid @enderror" required>
                        @error('request_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="receipt_date">{{ __('data_requests.fields.receipt_date') }}</label>
                        <input id="receipt_date" type="datetime-local" name="receipt_date" value="{{ old('receipt_date') }}" class="form-control @error('receipt_date') is-invalid @enderror" required>
                        @error('receipt_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="receiving_method_id">{{ __('data_requests.fields.receiving_method') }}</label>
                        <select id="receiving_method_id" name="receiving_method_id" class="form-select @error('receiving_method_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($receivingMethodOptions as $method)
                                <option value="{{ $method->id }}" @selected(old('receiving_method_id') == $method->id)>
                                    {{ $method->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('receiving_method_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="data_type_id">{{ __('data_requests.fields.data_type') }}</label>
                        <select id="data_type_id" name="data_type_id" class="form-select @error('data_type_id') is-invalid @enderror" required>
                            <option value="">—</option>
                            @foreach ($dataTypeOptions as $type)
                                <option
                                    value="{{ $type->id }}"
                                    data-entity="{{ $type->id_sub }}"
                                    @selected(old('data_type_id') == $type->id)
                                >
                                    {{ $type->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('data_type_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="subject">{{ __('data_requests.fields.subject') }}</label>
                        <input id="subject" type="text" name="subject" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror" maxlength="100" required>
                        @error('subject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="section_ids">{{ __('data_requests.fields.section') }}</label>
                        <select id="section_ids" name="section_ids[]" class="form-select @error('section_ids') is-invalid @enderror @error('section_ids.*') is-invalid @enderror" multiple size="5" required>
                            @foreach ($sectionOptions as $section)
                                <option value="{{ $section->id }}" @selected(collect(old('section_ids', []))->contains($section->id))>
                                    {{ $section->localizedName() }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('data_requests.fields.section_hint') }}</small>
                        @error('section_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('section_ids.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="deadline">{{ __('data_requests.fields.deadline') }}</label>
                        <input id="deadline" type="datetime-local" name="deadline" value="{{ old('deadline') }}" class="form-control @error('deadline') is-invalid @enderror" required>
                        @error('deadline') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field">
                        <label for="reminder_at">{{ __('data_requests.fields.reminder_at') }}</label>
                        <input id="reminder_at" type="datetime-local" name="reminder_at" value="{{ old('reminder_at') }}" class="form-control @error('reminder_at') is-invalid @enderror">
                        @error('reminder_at') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <label for="attachment_files">{{ __('data_requests.fields.attachments') }}</label>
                        <input id="attachment_files" type="file" name="attachment_files[]" class="form-control @error('attachment_files') is-invalid @enderror @error('attachment_files.*') is-invalid @enderror" multiple>
                        @error('attachment_files') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @error('attachment_files.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="gc-field gc-span-2">
                        <div class="gc-decl">
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_entity') is-invalid @enderror" type="checkbox" name="confirm_entity" id="confirm_entity" value="1" @checked(old('confirm_entity'))>
                                <label class="form-check-label" for="confirm_entity">{{ __('data_requests.declarations.confirm_entity') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_subject') is-invalid @enderror" type="checkbox" name="confirm_subject" id="confirm_subject" value="1" @checked(old('confirm_subject'))>
                                <label class="form-check-label" for="confirm_subject">{{ __('data_requests.declarations.confirm_subject') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input @error('confirm_department') is-invalid @enderror" type="checkbox" name="confirm_department" id="confirm_department" value="1" @checked(old('confirm_department'))>
                                <label class="form-check-label" for="confirm_department">{{ __('data_requests.declarations.confirm_department') }}</label>
                            </div>
                            @error('confirm_entity') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('confirm_subject') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @error('confirm_department') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('data_requests.actions.save') }}</button>
                    <a href="{{ route('modules.data-requests.index') }}" class="btn btn-outline-secondary">{{ __('data_requests.actions.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var entity = document.getElementById('entity_id');
            var dataType = document.getElementById('data_type_id');
            if (!entity || !dataType) return;

            function syncDataTypes() {
                var selectedEntity = entity.value;
                Array.prototype.forEach.call(dataType.options, function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    var match = option.getAttribute('data-entity') === selectedEntity;
                    option.hidden = !match;
                    if (!match && option.selected) {
                        dataType.value = '';
                    }
                });
            }

            entity.addEventListener('change', syncDataTypes);
            syncDataTypes();
        })();
    </script>
@endpush
