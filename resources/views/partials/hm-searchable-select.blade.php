@php
    $selectedLabel = '';

    foreach ($options as $option) {
        if ((string) $option->value === (string) $selected) {
            $selectedLabel = $option->label;
            break;
        }

        foreach ($option->children as $child) {
            if ((string) $child->value === (string) $selected) {
                $selectedLabel = $child->label;
                break 2;
            }
        }
    }
@endphp

<div
    class="hm-searchable-select"
    data-hm-searchable-select
    @if (!empty($navigateOnSelect)) data-navigate-on-select @endif
>
    <label class="hm-clinician-filter-field__label" for="{{ $id }}-trigger">{{ $label }}</label>
    <div class="hm-searchable-select__control">
        <button
            type="button"
            id="{{ $id }}-trigger"
            class="hm-searchable-select__trigger"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-labelledby="{{ $id }}-label"
            @if (!empty($disabled)) disabled @endif
        >
            <span class="hm-searchable-select__value">{{ $selectedLabel }}</span>
            <i class="bi bi-chevron-down hm-searchable-select__chevron" aria-hidden="true"></i>
        </button>
        <div class="hm-searchable-select__menu" hidden>
            <div class="hm-searchable-select__search-wrap">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input
                    type="search"
                    class="hm-searchable-select__search"
                    placeholder="{{ $searchPlaceholder ?? __('doctors_directory.search_placeholder') }}"
                    autocomplete="off"
                    aria-label="{{ $label }}"
                >
            </div>
            <ul class="hm-searchable-select__list" role="listbox" aria-label="{{ $label }}">
                @foreach ($options as $option)
                    @if ($option->hasChildren())
                        <li
                            class="hm-searchable-select__option hm-searchable-select__option--parent{{ collect($option->children)->contains(fn ($child) => (string) $child->value === (string) $selected) ? ' is-child-selected' : '' }}{{ (string) $option->value === (string) $selected ? ' is-selected' : '' }}"
                            role="presentation"
                        >
                            <button
                                type="button"
                                class="hm-searchable-select__option-btn hm-searchable-select__option-btn--parent"
                                data-value="{{ $option->value }}"
                                @if ($option->url) data-url="{{ $option->url }}" @endif
                                aria-haspopup="true"
                                aria-expanded="false"
                            >
                                <span>{{ $option->label }}</span>
                                <i class="bi {{ app()->getLocale() === 'ar' ? 'bi-chevron-left' : 'bi-chevron-right' }} hm-searchable-select__submenu-chevron" aria-hidden="true"></i>
                            </button>
                            <ul class="hm-searchable-select__sublist" role="group" aria-label="{{ $option->label }}">
                                @foreach ($option->children as $child)
                                    <li
                                        class="hm-searchable-select__option hm-searchable-select__option--child{{ (string) $child->value === (string) $selected ? ' is-selected' : '' }}"
                                        role="option"
                                        @if ((string) $child->value === (string) $selected) aria-selected="true" @endif
                                    >
                                        <button
                                            type="button"
                                            class="hm-searchable-select__option-btn"
                                            data-value="{{ $child->value }}"
                                            @if ($child->url) data-url="{{ $child->url }}" @endif
                                        >
                                            {{ $child->label }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li
                            class="hm-searchable-select__option{{ (string) $option->value === (string) $selected ? ' is-selected' : '' }}"
                            role="option"
                            @if ((string) $option->value === (string) $selected) aria-selected="true" @endif
                        >
                            <button
                                type="button"
                                class="hm-searchable-select__option-btn"
                                data-value="{{ $option->value }}"
                                @if ($option->url) data-url="{{ $option->url }}" @endif
                            >
                                {{ $option->label }}
                            </button>
                        </li>
                    @endif
                @endforeach
            </ul>
            <p class="hm-searchable-select__empty" hidden>{{ __('doctors_directory.no_results') }}</p>
        </div>
    </div>
    @if (empty($navigateOnSelect) && !empty($name))
        <input type="hidden" name="{{ $name }}" value="{{ $selected }}">
    @endif
</div>
