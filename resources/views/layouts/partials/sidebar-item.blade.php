@if ($item->isGroup && $item->hasChildren())
    <li class="nav-item" data-sidebar-group>
        <a href="#{{ $item->collapseId }}"
           class="nav-link {{ $item->active ? 'active' : '' }}"
           data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $item->active ? 'true' : 'false' }}"
           aria-controls="{{ $item->collapseId }}">
            <i class="icon" aria-hidden="true"><i class="bi {{ $item->icon }}"></i></i>
            <span class="item-name">{{ $item->title }}</span>
            <i class="right-icon" aria-hidden="true">
                <i class="bi bi-chevron-{{ $isRtl ? 'left' : 'right' }} hm-sidebar-chevron-closed"></i>
                <i class="bi bi-chevron-down hm-sidebar-chevron-open"></i>
            </i>
        </a>
        <ul class="sub-nav collapse {{ $item->active ? 'show' : '' }}" id="{{ $item->collapseId }}">
            @foreach ($item->children as $child)
                @include('layouts.partials.sidebar-item', ['item' => $child])
            @endforeach
        </ul>
    </li>
@else
    <li class="nav-item" data-sidebar-search-item data-sidebar-search="{{ $item->title }} {{ $item->route }}">
        <a href="{{ $item->url }}" class="nav-link {{ $item->active ? 'active' : '' }}" @if ($item->active) aria-current="page" @endif>
            <i class="icon" aria-hidden="true"><i class="bi {{ $item->icon }}"></i></i>
            <span class="item-name">{{ $item->title }}</span>
        </a>
    </li>
@endif
