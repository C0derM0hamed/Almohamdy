@php
    $paginator = $paginator ?? null;
    $summaryKey = $summaryKey ?? 'inquiries.results_summary';
@endphp

@if ($paginator && $paginator->total() > 0)
    <div class="fm-pager">
        <p class="fm-pager__meta">
            {!! trans($summaryKey, [
                'shown' => '<strong>'.e($paginator->count()).'</strong>',
                'total' => '<strong>'.e($paginator->total()).'</strong>',
            ]) !!}
        </p>
        <div class="fm-pager__btns">
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}">{{ __('pagination.next') }}</a>
            @else
                <span class="fm-pager__num">{{ __('pagination.next') }}</span>
            @endif
            <span class="fm-pager__num is-active">{{ $paginator->currentPage() }}</span>
            @if ($paginator->onFirstPage())
                <span class="fm-pager__num">{{ __('pagination.previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}">{{ __('pagination.previous') }}</a>
            @endif
        </div>
    </div>
@endif
