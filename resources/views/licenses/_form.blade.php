@php
    $record = $license ?? null;
    $nameOf = static function ($item) {
        if (! $item) return '—';
        if (method_exists($item, 'displayName')) return $item->displayName();
        if (method_exists($item, 'localizedName')) return $item->localizedName();
        $localeField = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        return data_get($item, $localeField) ?: data_get($item, 'name') ?: data_get($item, 'full_name') ?: data_get($item, 'hr_name') ?: '—';
    };
    $authoritiesList = $authorityOptions ?? $authorities ?? collect();
    $typesList = $typeOptions ?? $licenseTypes ?? $types ?? collect();
    $departmentsList = $departmentOptions ?? $departments ?? $branchOptions ?? $branches ?? collect();
    $responsiblesList = $responsibleOptions ?? $users ?? $responsibleUsers ?? collect();
    $stagesList = $stageOptions ?? $renewalStages ?? $stages ?? collect();
    $selectedDepartments = collect(old('department_ids', old('branch_ids', $record?->departments?->pluck('id')->all() ?? $record?->branches?->pluck('id')->all() ?? [])))->map(fn ($id) => (string) $id)->all();
    $dateValue = static function ($value) {
        if (! $value) return '';
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
    };
@endphp

<p class="lic-help mb-3">{{ __('licenses.required_hint') }}</p>
<div class="lic-form-grid">
    <div class="lic-field lic-field--span-2">
        <label for="hospital_branch_display">{{ __('licenses.fields.hospital_branch') }}</label>
        <input id="hospital_branch_display" type="text" class="form-control" value="{{ $hospitalBranch?->localizedName() ?? $record?->hospitalBranch?->localizedName() ?? '—' }}" readonly>
    </div>

    <div class="lic-field">
        <label for="authority_id">{{ __('licenses.fields.authority') }} <span class="lic-required" aria-hidden="true">*</span></label>
        <select id="authority_id" name="authority_id" class="form-select @error('authority_id') is-invalid @enderror" required aria-describedby="authority_id_error">
            <option value="">—</option>
            @foreach ($authoritiesList as $item)
                <option value="{{ $item->id }}" @selected((string) old('authority_id', $record?->authority_id ?? $record?->license_authority_id) === (string) $item->id)>{{ $nameOf($item) }}</option>
            @endforeach
        </select>
        @error('authority_id')<div id="authority_id_error" class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="lic-field">
        <label for="type_id">{{ __('licenses.fields.type') }} <span class="lic-required" aria-hidden="true">*</span></label>
        <select id="type_id" name="type_id" class="form-select @error('type_id') is-invalid @enderror" required>
            <option value="">—</option>
            @foreach ($typesList as $item)
                <option value="{{ $item->id }}" @selected((string) old('type_id', $record?->type_id ?? $record?->license_type_id) === (string) $item->id)>{{ $nameOf($item) }}</option>
            @endforeach
        </select>
        @error('type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="lic-field">
        <label for="title">{{ __('licenses.fields.title') }}</label>
        <input id="title" type="text" name="title" maxlength="255" value="{{ old('title', $record?->title) }}" class="form-control @error('title') is-invalid @enderror" autocomplete="off">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="lic-field">
        <label for="license_number">{{ __('licenses.fields.license_number') }}</label>
        <input id="license_number" type="text" name="license_number" maxlength="150" inputmode="numeric" pattern="[0-9]*" value="{{ old('license_number', $record?->license_number) }}" class="form-control lic-sensitive @error('license_number') is-invalid @enderror" autocomplete="off">
        @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <fieldset class="lic-field lic-field--span-2">
        <legend class="lic-label">{{ __('licenses.fields.departments') }} <span class="lic-required" aria-hidden="true">*</span></legend>
        <div class="lic-checkbox-grid @error('department_ids') border-danger @enderror">
            @foreach ($departmentsList as $department)
                <label class="lic-checkbox" for="department_{{ $department->id }}">
                    <input id="department_{{ $department->id }}" type="checkbox" name="department_ids[]" value="{{ $department->id }}" @checked(in_array((string) $department->id, $selectedDepartments, true))>
                    <span>{{ $nameOf($department) }}</span>
                </label>
            @endforeach
        </div>
        @error('department_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('department_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </fieldset>

    <div class="lic-field">
        <label for="responsible_user_id">{{ __('licenses.fields.responsible') }} <span class="lic-required" aria-hidden="true">*</span></label>
        <select id="responsible_user_id" name="responsible_user_id" class="form-select @error('responsible_user_id') is-invalid @enderror" required>
            <option value="">—</option>
            @foreach ($responsiblesList as $item)
                <option value="{{ $item->hr_id ?? $item->id }}" @selected((string) old('responsible_user_id', $record?->responsible_user_id) === (string) ($item->hr_id ?? $item->id))>{{ $nameOf($item) }}</option>
            @endforeach
        </select>
        @error('responsible_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="lic-field">
        <label for="issue_date">{{ __('licenses.fields.issue_date') }} <span class="lic-required" aria-hidden="true">*</span></label>
        <input id="issue_date" type="date" name="issue_date" value="{{ old('issue_date', $dateValue($record?->issue_date)) }}" class="form-control @error('issue_date') is-invalid @enderror" required>
        @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="lic-field">
        <label for="expiry_date">{{ __('licenses.fields.expiry_date') }} <span class="lic-required" aria-hidden="true">*</span></label>
        <input id="expiry_date" type="date" name="expiry_date" value="{{ old('expiry_date', $dateValue($record?->expiry_date)) }}" class="form-control @error('expiry_date') is-invalid @enderror" required>
        @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="lic-field">
        <label for="renewal_stage_id">{{ __('licenses.fields.renewal_stage') }}</label>
        <select id="renewal_stage_id" name="renewal_stage_id" class="form-select @error('renewal_stage_id') is-invalid @enderror">
            <option value="">{{ __('licenses.none') }}</option>
            @foreach ($stagesList as $item)
                <option value="{{ $item->id }}" @selected((string) old('renewal_stage_id', $record?->renewal_stage_id) === (string) $item->id)>{{ $nameOf($item) }}</option>
            @endforeach
        </select>
        @error('renewal_stage_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="lic-field lic-field--span-2">
        <label for="notes">{{ __('licenses.fields.notes') }}</label>
        <textarea id="notes" name="notes" maxlength="5000" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $record?->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if (($includeAttachments ?? false) === true)
        <div class="lic-field lic-field--span-2">
            <label for="attachments">{{ __('licenses.fields.attachments') }}</label>
            <input id="attachments" type="file" name="attachments[]" multiple accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx" class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror">
            <p class="lic-help">{{ __('licenses.attachments.allowed') }}</p>
            @error('attachments')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @error('attachments.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    @endif
</div>
