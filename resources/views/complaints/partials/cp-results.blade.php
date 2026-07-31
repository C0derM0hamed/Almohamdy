@if ($complaints->count() > 0)
    <div class="cp-results">
        @include('complaints.partials._cp-results-header')
        @foreach ($complaints as $complaint)
            @include('complaints.partials.cp-result-card', ['complaint' => $complaint])
        @endforeach
    </div>

    <div class="cp-pagination d-flex justify-content-center">
        {{ $complaints->links('pagination.hm') }}
    </div>
@else
    <div class="cp-empty">
        <i class="bi bi-chat-square-text" aria-hidden="true"></i>
        <p class="mb-0">{{ $hasFilters ? __('complaints.no_results') : __('complaints.no_complaints') }}</p>
    </div>
@endif
