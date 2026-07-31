@php
    $isEdit = isset($speciality);
    $formAction = $isEdit
        ? route('modules.doctors-admin.specialities.update', $speciality->id)
        : route('modules.doctors-admin.specialities.store');
    $publishChecked = old('publish', $isEdit ? $speciality->publish === '1' : false);
@endphp

<form method="POST" action="{{ $formAction }}" class="dda-form">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="dda-form-alert" role="alert">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <span>{{ __('doctors_directory_admin.form_has_errors') }}</span>
        </div>
    @endif

    <div class="dda-form-section">
        <header class="dda-form-section__head">
            <span class="dda-form-section__icon" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
            <div>
                <h2>{{ __('doctors_directory_admin.sections.basic_information') }}</h2>
                <p>{{ __('doctors_directory_admin.speciality_form_basic_hint') }}</p>
            </div>
        </header>

        <div class="dda-form-grid dda-form-grid--2">
            <div class="dda-form-field">
                <label for="subject_en">{{ __('doctors_directory_admin.fields.name_en') }}</label>
                <input
                    type="text"
                    id="subject_en"
                    name="subject_en"
                    value="{{ old('subject_en', $isEdit ? $speciality->subject_en : '') }}"
                    class="dda-form-control @error('subject_en') is-invalid @enderror"
                    maxlength="255"
                    required
                >
                @error('subject_en')
                    <div class="dda-form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="dda-form-field">
                <label for="subject_ar">{{ __('doctors_directory_admin.fields.name_ar') }}</label>
                <input
                    type="text"
                    id="subject_ar"
                    name="subject_ar"
                    value="{{ old('subject_ar', $isEdit ? $speciality->subject_ar : '') }}"
                    class="dda-form-control @error('subject_ar') is-invalid @enderror"
                    maxlength="255"
                    required
                >
                @error('subject_ar')
                    <div class="dda-form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="dda-form-field dda-form-field--full">
                <label for="clinics_id">{{ __('doctors_directory_admin.fields.clinic') }}</label>
                <select id="clinics_id" name="clinics_id" class="dda-form-select @error('clinics_id') is-invalid @enderror">
                    @foreach ($clinics as $clinicId => $clinicName)
                        <option value="{{ $clinicId }}" @selected((string) old('clinics_id', $isEdit ? $speciality->clinics_id : 0) === (string) $clinicId)>
                            {{ $clinicName }}
                        </option>
                    @endforeach
                </select>
                @error('clinics_id')
                    <div class="dda-form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="dda-form-section">
        <header class="dda-form-section__head">
            <span class="dda-form-section__icon dda-form-section__icon--green" aria-hidden="true"><i class="bi bi-eye"></i></span>
            <div>
                <h2>{{ __('doctors_directory_admin.sections.directory_settings') }}</h2>
                <p>{{ __('doctors_directory_admin.speciality_form_publish_hint') }}</p>
            </div>
        </header>

        <label class="dda-form-switch" for="publish">
            <input
                type="checkbox"
                id="publish"
                name="publish"
                value="1"
                class="dda-form-switch__input @error('publish') is-invalid @enderror"
                @checked($publishChecked)
            >
            <span class="dda-form-switch__track" aria-hidden="true"></span>
            <span class="dda-form-switch__copy">
                <strong>{{ __('doctors_directory_admin.fields.published') }}</strong>
                <span>{{ __('doctors_directory_admin.speciality_form_publish_hint') }}</span>
            </span>
        </label>
        @error('publish')
            <div class="dda-form-error">{{ $message }}</div>
        @enderror
    </div>

    @if ($isEdit)
        <div class="dda-form-meta">
            <div class="dda-form-meta__item">
                <small>{{ __('doctors_directory_admin.fields.id') }}</small>
                <strong>#{{ $speciality->id }}</strong>
            </div>
            @if ($speciality->created_at)
                <div class="dda-form-meta__item">
                    <small>{{ __('doctors_directory_admin.fields.created_at') }}</small>
                    <strong>{{ $speciality->created_at }}</strong>
                </div>
            @endif
            @if ($speciality->updated_at)
                <div class="dda-form-meta__item">
                    <small>{{ __('doctors_directory_admin.fields.updated_at') }}</small>
                    <strong>{{ $speciality->updated_at }}</strong>
                </div>
            @endif
        </div>
    @endif

    <div class="dda-form-actions">
        <button type="submit" class="btn hm-btn hm-btn--primary dda-btn">
            <i class="bi bi-check-lg" aria-hidden="true"></i>
            {{ $isEdit ? __('doctors_directory_admin.save') : __('doctors_directory_admin.create') }}
        </button>
        <a href="{{ route('modules.doctors-admin.specialities.index') }}" class="btn hm-btn hm-btn--outline dda-btn">
            {{ __('doctors_directory_admin.cancel') }}
        </a>
    </div>
</form>
