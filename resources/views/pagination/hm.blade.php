@if ($paginator->hasPages())
    <nav class="hm-pagination-wrap" aria-label="{{ __('dashboard.pagination') }}">
        <ul class="hm-pagination">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="hm-pagination__item hm-pagination__item--nav is-disabled">
                    <span class="hm-pagination__link hm-pagination__link--nav" aria-hidden="true">
                        <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                        <span class="hm-pagination__label">@lang('pagination.previous')</span>
                    </span>
                </li>
            @else
                <li class="hm-pagination__item hm-pagination__item--nav">
                    <a class="hm-pagination__link hm-pagination__link--nav" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                        <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                        <span class="hm-pagination__label">@lang('pagination.previous')</span>
                    </a>
                </li>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="hm-pagination__item hm-pagination__item--page is-disabled">
                        <span class="hm-pagination__link hm-pagination__dots">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="hm-pagination__item hm-pagination__item--page is-active" aria-current="page">
                                <span class="hm-pagination__link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="hm-pagination__item hm-pagination__item--page">
                                <a class="hm-pagination__link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="hm-pagination__item hm-pagination__item--nav">
                    <a class="hm-pagination__link hm-pagination__link--nav" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                        <span class="hm-pagination__label">@lang('pagination.next')</span>
                        <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i>
                    </a>
                </li>
            @else
                <li class="hm-pagination__item hm-pagination__item--nav is-disabled">
                    <span class="hm-pagination__link hm-pagination__link--nav" aria-hidden="true">
                        <span class="hm-pagination__label">@lang('pagination.next')</span>
                        <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
