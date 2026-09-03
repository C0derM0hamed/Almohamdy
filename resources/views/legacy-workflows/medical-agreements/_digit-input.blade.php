@php
    $name = $name ?? 'number';
    $dataAttribute = $dataAttribute ?? '';
    $label = $label ?? 'رقم';
    $maxLength = (int) ($maxLength ?? 12);
    $digitValue = (string) old($name, $value ?? '');
@endphp

<div class="hm-digit-input" data-digit-input data-digit-max="{{ $maxLength }}">
    <input
        type="text"
        class="hm-digit-input__value"
        name="{{ $name }}"
        value="{{ $digitValue }}"
        maxlength="{{ $maxLength }}"
        {{ $dataAttribute }}
        data-required="true"
        autocomplete="off"
        tabindex="-1"
        aria-hidden="true"
    >
    <div class="hm-digit-input__boxes" role="group" aria-label="{{ $label }}" dir="ltr">
        @for ($index = 0; $index < $maxLength; $index++)
            <input
                type="text"
                class="hm-digit-input__box"
                value="{{ substr($digitValue, $index, 1) }}"
                maxlength="1"
                inputmode="numeric"
                autocomplete="off"
                aria-label="{{ $label }} {{ $index + 1 }}"
                data-digit-box
            >
        @endfor
    </div>
</div>
