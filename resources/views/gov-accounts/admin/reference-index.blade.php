@extends('layouts.app')
@section('title', __('gov_accounts.references.'.$reference))
@section('sidebar_heading', __('gov_accounts.title'))
@push('styles')<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">@endpush
@section('content')
<div class="hm-licenses">
    @include('licenses.partials.page-header', [
        'title' => __('gov_accounts.references.'.$reference), 'subtitle' => __('gov_accounts.admin.subtitle'), 'icon' => 'bi-list-ul',
        'actions' => new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e(route('modules.gov-accounts.admin.index')).'">'.e(__('gov_accounts.actions.back')).'</a><a class="lic-btn lic-btn--primary" href="'.e(route('modules.gov-accounts.admin.'.$reference.'.create')).'"><i class="bi bi-plus-lg"></i>'.e(__('gov_accounts.actions.add')).'</a>'),
    ])
    @include('licenses.partials.feedback')
    <section class="lic-panel"><div class="lic-table-wrap"><table class="lic-table"><thead><tr>
        @if($reference === 'department-heads')<th>{{ __('gov_accounts.fields.department') }}</th><th>{{ __('gov_accounts.fields.user') }}</th>
        @else @if($reference === 'services')<th>{{ __('gov_accounts.fields.authority') }}</th>@endif<th>{{ __('gov_accounts.fields.name_ar') }}</th><th>{{ __('gov_accounts.fields.name_en') }}</th><th>{{ __('gov_accounts.fields.ranking') }}</th>@endif
        <th>{{ __('gov_accounts.fields.publish') }}</th><th>{{ __('gov_accounts.actions.actions') }}</th>
    </tr></thead><tbody>
    @forelse($items as $item)<tr>
        @if($reference === 'department-heads')<td>{{ $item->department?->localizedName() ?? '—' }}</td><td>{{ $item->user?->displayName() ?? '—' }}</td>
        @else @if($reference === 'services')<td>{{ $item->authority?->localizedName() ?? '—' }}</td>@endif<td>{{ $item->name_ar }}</td><td>{{ $item->name_en }}</td><td>{{ (int)$item->ranking }}</td>@endif
        <td>{{ $item->publish ? __('gov_accounts.enabled') : __('gov_accounts.disabled') }}</td><td><div class="lic-table__actions">
            <a class="lic-btn lic-btn--sm" href="{{ route('modules.gov-accounts.admin.'.$reference.'.edit', $item) }}">{{ __('gov_accounts.actions.edit') }}</a>
            <form method="POST" action="{{ route('modules.gov-accounts.admin.'.$reference.'.publish', $item) }}">@csrf @method('PATCH')<button class="lic-btn lic-btn--sm" type="submit">{{ __('gov_accounts.actions.toggle') }}</button></form>
        </div></td>
    </tr>@empty<tr><td colspan="7" class="lic-empty">{{ __('gov_accounts.admin.empty') }}</td></tr>@endforelse
    </tbody></table></div>@if($items->total())<div class="lic-pagination">{{ $items->withQueryString()->links('pagination.hm') }}</div>@endif</section>
</div>
@endsection
