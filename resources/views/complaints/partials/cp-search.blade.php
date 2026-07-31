<form method="GET" action="{{ $searchAction ?? route('modules.complaints') }}" class="cp-search {{ $searchFormClass ?? '' }}">
    <div class="cp-search__field">
        <label for="complaintSearch">{{ __('complaints.filters.search') }}</label>
        <div class="cp-search__input-wrap">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input
                type="search"
                id="complaintSearch"
                name="search"
                value="{{ $filters['search'] }}"
                class="cp-search__input"
                placeholder="{{ __('complaints.filters.search') }}"
                maxlength="100"
            >
        </div>
    </div>

    <div class="cp-search__field cp-search__field--status">
        <label for="complaintStatus">{{ __('complaints.filters.status') }}</label>
        <select id="complaintStatus" name="status" class="cp-search__select">
            <option value="">{{ __('complaints.filters.status') }}</option>
            <option value="0" @selected((string) $filters['status'] === '0')>
                {{ __('complaints.status.new') }}
            </option>
            @foreach ($statusOptions as $statusOption)
                <option value="{{ $statusOption->id }}" @selected((string) $filters['status'] === (string) $statusOption->id)>
                    {{ $statusOption->localizedName() }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="hm-btn hm-btn--primary cp-search__submit">
        {{ __('complaints.search') }}
    </button>

    @if ($hasFilters)
        <a href="{{ $resetUrl ?? route('modules.complaints') }}" class="hm-btn hm-btn--ghost cp-search__reset">
            {{ __('complaints.reset') }}
        </a>
    @endif
</form>
