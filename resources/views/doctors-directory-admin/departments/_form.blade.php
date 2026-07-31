@php
    $isEdit = isset($section);
    $formAction = $isEdit
        ? route('modules.doctors-admin.departments.update', $section->id)
        : route('modules.doctors-admin.departments.store');
    $publishChecked = old('publish', $isEdit ? ((int) $section->publish === 1) : false);
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
            <span class="dda-form-section__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
            <div>
                <h2>{{ __('doctors_directory_admin.department_form_assignment_title') }}</h2>
                <p>{{ __('doctors_directory_admin.departments_create_subtitle') }}</p>
            </div>
        </header>

        <div class="dda-form-grid dda-form-grid--2">
            <div class="dda-form-field">
                <label for="speciality_id">{{ __('doctors_directory_admin.fields.speciality') }}</label>
                <select id="speciality_id" name="speciality_id" class="dda-form-select @error('speciality_id') is-invalid @enderror" required>
                    <option value="">{{ __('doctors_directory_admin.select_speciality') }}</option>
                    @foreach ($specialities as $id => $label)
                        <option value="{{ $id }}" @selected((string) old('speciality_id', $isEdit ? $section->specialized_clinics_id : ($selectedSpecialityId ?? '')) === (string) $id)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('speciality_id')
                    <div class="dda-form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="dda-form-field">
                <label for="department_id">{{ __('doctors_directory_admin.fields.department') }}</label>
                <select id="department_id" name="department_id" class="dda-form-select @error('department_id') is-invalid @enderror" required>
                    <option value="">{{ __('doctors_directory_admin.select_department') }}</option>
                    @foreach ($departmentOptions as $id => $label)
                        <option value="{{ $id }}" @selected((string) old('department_id', $isEdit ? $section->outpatient_clinics_id : '') === (string) $id)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('department_id')
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
                <p>{{ __('doctors_directory_admin.department_form_publish_hint') }}</p>
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
                <span>{{ __('doctors_directory_admin.department_form_publish_hint') }}</span>
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
                <strong>#{{ $section->id }}</strong>
            </div>
            <div class="dda-form-meta__item">
                <small>{{ __('doctors_directory_admin.fields.updated_by') }}</small>
                <strong>{{ $section->updated_by ?? '—' }}</strong>
            </div>
        </div>
    @endif

    <div class="dda-form-actions">
        <button type="submit" class="btn hm-btn hm-btn--primary dda-btn">
            <i class="bi bi-check-lg" aria-hidden="true"></i>
            {{ $isEdit ? __('doctors_directory_admin.save') : __('doctors_directory_admin.create') }}
        </button>
        <a href="{{ route('modules.doctors-admin.departments.index') }}" class="btn hm-btn hm-btn--outline dda-btn">
            {{ __('doctors_directory_admin.cancel') }}
        </a>
    </div>
</form>
