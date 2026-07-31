@php
    $fieldId = $id ?? $name;
    $inputValue = $value ?? old($name, '');
    $hasError = $errors->has($name);
    $fieldType = $type ?? 'text';
@endphp

<div @class(['dda-form-field', 'dda-form-field--full' => ! empty($full)])>
    <label for="{{ $fieldId }}">
        {{ $label }}
        @if (! empty($required))
            <span class="dda-form-required" aria-hidden="true">*</span>
        @endif
    </label>

    @if ($fieldType === 'select')
        <select
            id="{{ $fieldId }}"
            name="{{ $name }}"
            class="dda-form-select @error($name) is-invalid @enderror"
            @if (! empty($required)) required @endif
        >
            @if (! empty($emptyOption))
                <option value="">{{ $emptyOption }}</option>
            @endif
            @foreach ($options ?? [] as $optionId => $optionLabel)
                <option value="{{ $optionId }}" @selected((string) $inputValue === (string) $optionId)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($fieldType === 'textarea')
        <textarea
            id="{{ $fieldId }}"
            name="{{ $name }}"
            rows="{{ $rows ?? 3 }}"
            class="dda-form-control dda-form-control--textarea @error($name) is-invalid @enderror"
            @if (! empty($maxlength)) maxlength="{{ $maxlength }}" @endif
        >{{ $inputValue }}</textarea>
    @elseif ($fieldType === 'file')
        <input
            type="file"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            class="dda-form-control dda-form-control--file @error($name) is-invalid @enderror"
            @if (! empty($accept)) accept="{{ $accept }}" @endif
        >
    @else
        <input
            type="{{ $fieldType }}"
            id="{{ $fieldId }}"
            name="{{ $name }}"
            value="{{ $inputValue }}"
            class="dda-form-control @error($name) is-invalid @enderror"
            @if (! empty($required)) required @endif
            @if (! empty($maxlength)) maxlength="{{ $maxlength }}" @endif
            @if (isset($min)) min="{{ $min }}" @endif
            @if (isset($max)) max="{{ $max }}" @endif
            @if (isset($step)) step="{{ $step }}" @endif
            @if (! empty($placeholder)) placeholder="{{ $placeholder }}" @endif
        >
    @endif

    @error($name)
        <div class="dda-form-error">{{ $message }}</div>
    @enderror
</div>
