@php
    $isEdit = isset($doctor);
    $formAction = $isEdit
        ? route('modules.doctors-admin.doctors.update', $doctor->id)
        : route('modules.doctors-admin.doctors.store');
    $publishChecked = old('publish', $isEdit ? $doctor->publish === '1' : false);
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="dda-form hm-doctors-admin-doctor-form">
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

    @if (! $isEdit)
        <div class="dda-form-intro">
            <span class="dda-form-intro__icon" aria-hidden="true"><i class="bi bi-info-circle"></i></span>
            <p>{{ __('doctors_directory_admin.doctors_create_basic_hint') }}</p>
        </div>
    @endif

    <div class="dda-form-section">
        <header class="dda-form-section__head">
            <span class="dda-form-section__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
            <div>
                <h2>{{ __('doctors_directory_admin.sections.basic_information') }}</h2>
                <p>{{ __('doctors_directory_admin.sections.basic_information_hint') }}</p>
            </div>
        </header>

        <div class="dda-form-grid dda-form-grid--2">
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'name_en',
                'label' => __('doctors_directory_admin.fields.name_en'),
                'required' => true,
                'maxlength' => 255,
                'value' => old('name_en', $isEdit ? $doctor->name_en : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'name_ar',
                'label' => __('doctors_directory_admin.fields.name_ar'),
                'required' => true,
                'maxlength' => 255,
                'value' => old('name_ar', $isEdit ? $doctor->name_ar : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'code',
                'label' => __('doctors_directory_admin.fields.code'),
                'required' => true,
                'maxlength' => 50,
                'value' => old('code', $isEdit ? $doctor->code : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'specialized_clinics_id',
                'label' => __('doctors_directory_admin.fields.speciality'),
                'type' => 'select',
                'required' => true,
                'emptyOption' => __('doctors_directory_admin.select_speciality'),
                'options' => $specialities,
                'value' => old('specialized_clinics_id', $isEdit ? $doctor->specialized_clinics_id : ($selectedSpecialityId ?? '')),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'country_id',
                'label' => __('doctors_directory_admin.fields.country'),
                'type' => 'select',
                'emptyOption' => __('doctors_directory_admin.select_country'),
                'options' => $countries,
                'value' => old('country_id', $isEdit ? ($doctor->country_id ?? '') : ''),
            ])
        </div>
    </div>

    <div class="dda-form-section">
        <header class="dda-form-section__head">
            <span class="dda-form-section__icon dda-form-section__icon--green" aria-hidden="true"><i class="bi bi-briefcase"></i></span>
            <div>
                <h2>{{ __('doctors_directory_admin.sections.professional_information') }}</h2>
                <p>{{ __('doctors_directory_admin.sections.professional_information_hint') }}</p>
            </div>
        </header>

        <div class="dda-form-grid dda-form-grid--2">
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'specialization_en',
                'label' => __('doctors_directory_admin.fields.specialization_en'),
                'maxlength' => 255,
                'value' => old('specialization_en', $isEdit ? $doctor->specialization_en : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'specialization_ar',
                'label' => __('doctors_directory_admin.fields.specialization_ar'),
                'maxlength' => 255,
                'value' => old('specialization_ar', $isEdit ? $doctor->specialization_ar : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'age',
                'label' => __('doctors_directory_admin.fields.age'),
                'maxlength' => 100,
                'value' => old('age', $isEdit ? $doctor->age : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'price',
                'label' => __('doctors_directory_admin.fields.price'),
                'type' => 'number',
                'min' => 0,
                'step' => 1,
                'value' => old('price', $isEdit ? $doctor->price : 0),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'holds_en',
                'label' => __('doctors_directory_admin.fields.holds_en'),
                'type' => 'textarea',
                'full' => true,
                'rows' => 3,
                'value' => old('holds_en', $isEdit ? $doctor->holds_en : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'holds_ar',
                'label' => __('doctors_directory_admin.fields.holds_ar'),
                'type' => 'textarea',
                'full' => true,
                'rows' => 3,
                'value' => old('holds_ar', $isEdit ? $doctor->holds_ar : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'cases_en',
                'label' => __('doctors_directory_admin.fields.cases_en'),
                'type' => 'textarea',
                'full' => true,
                'rows' => 3,
                'value' => old('cases_en', $isEdit ? $doctor->cases_en : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'cases_ar',
                'label' => __('doctors_directory_admin.fields.cases_ar'),
                'type' => 'textarea',
                'full' => true,
                'rows' => 3,
                'value' => old('cases_ar', $isEdit ? $doctor->cases_ar : ''),
            ])
        </div>
    </div>

    <div class="dda-form-section">
        <header class="dda-form-section__head">
            <span class="dda-form-section__icon dda-form-section__icon--teal" aria-hidden="true"><i class="bi bi-telephone"></i></span>
            <div>
                <h2>{{ __('doctors_directory_admin.sections.contact_information') }}</h2>
            </div>
        </header>

        <div class="dda-form-grid dda-form-grid--2">
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'mobile',
                'label' => __('doctors_directory_admin.fields.mobile'),
                'maxlength' => 50,
                'value' => old('mobile', $isEdit ? $doctor->mobile : ''),
            ])
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'email',
                'label' => __('doctors_directory_admin.fields.email'),
                'type' => 'email',
                'maxlength' => 255,
                'value' => old('email', $isEdit ? $doctor->email : ''),
            ])
        </div>
    </div>

    <div class="dda-form-section">
        <header class="dda-form-section__head">
            <span class="dda-form-section__icon dda-form-section__icon--purple" aria-hidden="true"><i class="bi bi-sliders"></i></span>
            <div>
                <h2>{{ __('doctors_directory_admin.sections.directory_settings') }}</h2>
                <p>{{ __('doctors_directory_admin.sections.directory_settings_hint') }}</p>
            </div>
        </header>

        <div class="dda-form-grid dda-form-grid--2">
            @include('doctors-directory-admin.doctors._field', [
                'name' => 'ranking',
                'label' => __('doctors_directory_admin.fields.ranking'),
                'type' => 'number',
                'min' => 0,
                'step' => 1,
                'value' => old('ranking', $isEdit ? $doctor->ranking : 0),
            ])

            <div class="dda-form-field">
                <label for="photo">{{ __('doctors_directory_admin.fields.photo') }}</label>
                <div class="dda-form-upload">
                    @if ($isEdit && $doctor->photoUrl())
                        <div class="dda-form-upload__preview">
                            <img src="{{ $doctor->photoUrl() }}" alt="">
                        </div>
                        <label class="dda-form-check" for="remove_photo">
                            <input type="checkbox" id="remove_photo" name="remove_photo" value="1" @checked(old('remove_photo'))>
                            <span>{{ __('doctors_directory_admin.remove_photo') }}</span>
                        </label>
                    @else
                        <div class="dda-form-upload__placeholder" aria-hidden="true">
                            <i class="bi bi-image"></i>
                        </div>
                    @endif
                    <div class="dda-form-upload__control">
                        <label for="photo" class="dda-form-upload__label">
                            <input type="file" id="photo" name="photo" class="dda-form-upload__input @error('photo') is-invalid @enderror" accept="image/*">
                            <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                            <span>{{ __('doctors_directory_admin.upload_photo') }}</span>
                        </label>
                        <p class="dda-form-upload__hint">{{ __('doctors_directory_admin.upload_photo_hint') }}</p>
                    </div>
                </div>
                @error('photo')<div class="dda-form-error">{{ $message }}</div>@enderror
            </div>

            <div class="dda-form-field dda-form-field--full">
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
                        <span>{{ __('doctors_directory_admin.fields.published_help') }}</span>
                    </span>
                </label>
                @error('publish')<div class="dda-form-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    @if ($isEdit)
        <div class="dda-form-meta">
            <div class="dda-form-meta__item">
                <small>{{ __('doctors_directory_admin.fields.id') }}</small>
                <strong>#{{ $doctor->id }}</strong>
            </div>
            <div class="dda-form-meta__item">
                <small>{{ __('doctors_directory_admin.fields.legacy_status') }}</small>
                <strong>{{ $doctor->status ?? '—' }}</strong>
            </div>
            @if ($doctor->created_at)
                <div class="dda-form-meta__item">
                    <small>{{ __('doctors_directory_admin.fields.created_at') }}</small>
                    <strong>{{ $doctor->created_at }}</strong>
                </div>
            @endif
            @if ($doctor->updated_at)
                <div class="dda-form-meta__item">
                    <small>{{ __('doctors_directory_admin.fields.updated_at') }}</small>
                    <strong>{{ $doctor->updated_at }}</strong>
                </div>
            @endif
        </div>
    @endif

    <div class="dda-form-actions dda-form-actions--page">
        <div class="dda-form-actions__start">
            <a href="{{ route('modules.doctors-admin.doctors.index') }}" class="btn hm-btn hm-btn--outline dda-btn">
                {{ __('doctors_directory_admin.cancel') }}
            </a>
            @if ($isEdit && ! empty($previewUrl))
                <a href="{{ $previewUrl }}" class="btn hm-btn hm-btn--outline dda-btn" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    {{ __('doctors_directory_admin.preview_profile') }}
                </a>
            @endif
        </div>
        <button type="submit" class="btn hm-btn hm-btn--primary dda-btn dda-btn--submit">
            <i class="bi bi-check-lg" aria-hidden="true"></i>
            {{ $isEdit ? __('doctors_directory_admin.save') : __('doctors_directory_admin.create_doctor') }}
        </button>
    </div>
</form>
