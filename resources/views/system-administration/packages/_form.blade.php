@php
    $sectionLabel = $sectionOptions[(int) $package->service_id]
        ?? $package->section?->localizedName()
        ?? '—';
    $publishChecked = old('publish', $package->publish === '1');
@endphp

<form method="POST" action="{{ route('modules.system-admin.packages.update', $package->id) }}" class="dda-form">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div class="dda-form-alert" role="alert">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <span>{{ __('system_administration.form_has_errors') }}</span>
        </div>
    @endif

    <div class="dda-form-section">
        <header class="dda-form-section__head">
            <span class="dda-form-section__icon" aria-hidden="true"><i class="bi bi-hospital"></i></span>
            <div>
                <h2>{{ __('system_administration.sections.basic_information') }}</h2>
                <p>{{ __('system_administration.form_basic_hint') }}</p>
            </div>
        </header>

        <div class="dda-form-grid dda-form-grid--2">
            <div class="dda-form-field">
                <label for="code1">{{ __('system_administration.fields.code') }}</label>
                <input
                    type="text"
                    id="code1"
                    name="code1"
                    value="{{ old('code1', $package->code1) }}"
                    class="dda-form-control @error('code1') is-invalid @enderror"
                    maxlength="100"
                    required
                >
                @error('code1')
                    <div class="dda-form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="dda-form-field">
                <label for="price">{{ __('system_administration.fields.price') }}</label>
                <input
                    type="text"
                    id="price"
                    name="price"
                    value="{{ old('price', $package->price) }}"
                    class="dda-form-control @error('price') is-invalid @enderror"
                    maxlength="100"
                    placeholder="—"
                >
                @error('price')
                    <div class="dda-form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="dda-form-field">
                <label for="name_en">{{ __('system_administration.fields.name_en') }}</label>
                <input
                    type="text"
                    id="name_en"
                    name="name_en"
                    value="{{ old('name_en', $package->name_en) }}"
                    class="dda-form-control @error('name_en') is-invalid @enderror"
                    maxlength="500"
                    required
                >
                @error('name_en')
                    <div class="dda-form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="dda-form-field">
                <label for="name_ar">{{ __('system_administration.fields.name_ar') }}</label>
                <input
                    type="text"
                    id="name_ar"
                    name="name_ar"
                    value="{{ old('name_ar', $package->name_ar) }}"
                    class="dda-form-control @error('name_ar') is-invalid @enderror"
                    maxlength="500"
                    required
                >
                @error('name_ar')
                    <div class="dda-form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="dda-form-field dda-form-field--full">
                <label for="section_label">{{ __('system_administration.fields.section') }}</label>
                <input
                    type="text"
                    id="section_label"
                    value="{{ $sectionLabel }}"
                    class="dda-form-control"
                    readonly
                    disabled
                >
            </div>
        </div>
    </div>

    <div class="dda-form-section">
        <header class="dda-form-section__head">
            <span class="dda-form-section__icon dda-form-section__icon--green" aria-hidden="true"><i class="bi bi-eye"></i></span>
            <div>
                <h2>{{ __('system_administration.sections.directory_settings') }}</h2>
                <p>{{ __('system_administration.form_publish_hint') }}</p>
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
                <strong>{{ __('system_administration.fields.published') }}</strong>
                <span>{{ __('system_administration.form_publish_hint') }}</span>
            </span>
        </label>
        @error('publish')
            <div class="dda-form-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="dda-form-meta">
        <div class="dda-form-meta__item">
            <small>{{ __('system_administration.fields.id') }}</small>
            <strong>#{{ $package->id }}</strong>
        </div>
        @if ($package->created_at)
            <div class="dda-form-meta__item">
                <small>{{ __('system_administration.fields.created_at') }}</small>
                <strong>{{ $package->created_at }}</strong>
            </div>
        @endif
        @if ($package->updated_at)
            <div class="dda-form-meta__item">
                <small>{{ __('system_administration.fields.updated_at') }}</small>
                <strong>{{ $package->updated_at }}</strong>
            </div>
        @endif
    </div>

    <div class="dda-form-actions">
        <button type="submit" class="btn hm-btn hm-btn--primary dda-btn">
            <i class="bi bi-check-lg" aria-hidden="true"></i>
            {{ __('system_administration.save') }}
        </button>
        <a href="{{ route('modules.system-admin.packages.index') }}" class="btn hm-btn hm-btn--outline dda-btn">
            {{ __('system_administration.cancel') }}
        </a>
    </div>
</form>

<form
    method="POST"
    action="{{ route('modules.system-admin.packages.destroy', $package->id) }}"
    class="mt-3"
    onsubmit="return confirm(@json(__('system_administration.confirm_delete')));"
>
    @csrf
    @method('DELETE')
    <button type="submit" class="btn hm-btn hm-btn--outline dda-btn text-danger">
        <i class="bi bi-trash" aria-hidden="true"></i>
        {{ __('system_administration.delete') }}
    </button>
</form>
