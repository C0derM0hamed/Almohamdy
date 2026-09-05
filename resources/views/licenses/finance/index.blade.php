@extends('layouts.app')

@section('title', __('licenses.payments.title'))
@section('sidebar_heading', __('licenses.title'))
@section('sidebar_subheading', __('licenses.payments.subtitle'))

@push('styles')
<link href="{{ asset('css/hm-licenses.css') }}?v={{ filemtime(public_path('css/hm-licenses.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $url = static fn ($name, $params = []) => \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : '#';
    $nameOf = static function ($item) { if(!$item)return '—'; if(is_string($item))return $item; if(method_exists($item,'displayName'))return $item->displayName(); if(method_exists($item,'localizedName'))return $item->localizedName(); $f=app()->getLocale()==='ar'?'name_ar':'name_en'; return data_get($item,$f)?:data_get($item,'name')?:data_get($item,'hr_name')?:'—'; };
    $dateOf = static fn ($value) => $value ? ($value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i') : substr((string)$value,0,16)) : '—';
    $items = $paymentRequests ?? $requests ?? collect();
    $filters = array_merge(['search'=>'','status'=>'','department_id'=>'','from_date'=>'','to_date'=>''], $filters ?? request()->only(['search','status','department_id','from_date','to_date']));
    $metrics = array_merge(['received'=>0,'in_progress'=>0,'needs_documents'=>0,'paid'=>0], $statusCounters ?? $counters ?? []);
@endphp
<div class="hm-licenses">
    @include('licenses.partials.page-header', ['title'=>__('licenses.payments.title'),'subtitle'=>__('licenses.payments.subtitle'),'icon'=>'bi-wallet2','actions'=>new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e($url('modules.licenses.dashboard')).'"><i class="bi bi-speedometer2"></i>'.e(__('licenses.dashboard')).'</a>')])
    @include('licenses.partials.feedback')

    <section class="lic-stat-grid lic-stat-grid--four lic-stat-grid--compact" aria-label="{{ __('licenses.payments.status') }}">
        @foreach ([['received','bi-inbox'],['in_progress','bi-hourglass-split'],['needs_documents','bi-file-earmark-excel'],['paid','bi-check2-circle']] as [$key,$icon])
            <a class="lic-stat text-decoration-none" href="{{ $url('modules.licenses.finance.index',['status'=>$key]) }}"><span class="lic-stat__icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span><span class="lic-stat__copy"><span class="lic-stat__label">{{ __('licenses.payments.statuses.'.$key) }}</span><strong class="lic-stat__value">{{ (int)($metrics[$key] ?? 0) }}</strong><span class="lic-stat__hint">{{ __('licenses.payments.statuses.'.$key) }}</span></span></a>
        @endforeach
    </section>

    <section class="lic-toolbar" aria-labelledby="financeFiltersTitle"><h2 id="financeFiltersTitle" class="lic-toolbar__title"><i class="bi bi-funnel"></i>{{ __('licenses.filters.title') }}</h2>
        <form method="GET" action="{{ $url('modules.licenses.finance.index') }}"><div class="lic-filter-grid">
            <div class="lic-field"><label for="finance_search">{{ __('licenses.filters.search') }}</label><input id="finance_search" type="search" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="{{ __('licenses.filters.search_placeholder') }}"></div>
            <div class="lic-field"><label for="finance_status">{{ __('licenses.payments.status') }}</label><select id="finance_status" name="status" class="form-select"><option value="">{{ __('licenses.all') }}</option>@foreach(($paymentStatusOptions ?? $statuses ?? collect()) as $status)<option value="{{ data_get($status,'key') ?: data_get($status,'id') }}" @selected((string)$filters['status']===(string)(data_get($status,'key')?:data_get($status,'id')))>{{ $nameOf($status) }}</option>@endforeach</select></div>
            <div class="lic-field"><label for="finance_department">{{ __('licenses.filters.department') }}</label><select id="finance_department" name="department_id" class="form-select"><option value="">{{ __('licenses.all') }}</option>@foreach(($departmentOptions ?? $departments ?? $branchOptions ?? $branches ?? collect()) as $department)<option value="{{ $department->id }}" @selected((string)$filters['department_id']===(string)$department->id)>{{ $nameOf($department) }}</option>@endforeach</select></div>
            <div class="lic-field"><label for="finance_from">{{ __('licenses.filters.expiry_from') }}</label><input id="finance_from" type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control"></div>
            <div class="lic-field"><label for="finance_to">{{ __('licenses.filters.expiry_to') }}</label><input id="finance_to" type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control"></div>
            <div class="lic-filter-actions"><button class="lic-btn lic-btn--primary" type="submit">{{ __('licenses.apply_filters') }}</button><a class="lic-btn" href="{{ $url('modules.licenses.finance.index') }}">{{ __('licenses.reset') }}</a></div>
        </div></form>
    </section>

    <section class="lic-panel"><div class="lic-panel__head"><h2 class="lic-panel__title"><i class="bi bi-receipt"></i>{{ __('licenses.payments.title') }}</h2><span>{{ __('licenses.results',['count'=>method_exists($items,'total')?$items->total():count($items)]) }}</span></div>
        <div class="lic-table-wrap"><table class="lic-table"><thead><tr><th>{{ __('licenses.payments.request_number') }}</th><th>{{ __('licenses.index') }}</th><th>{{ __('licenses.payments.amount') }}</th><th>{{ __('licenses.payments.status') }}</th><th>{{ __('licenses.payments.requested_by') }}</th><th>{{ __('licenses.payments.requested_at') }}</th><th>{{ __('licenses.actions') }}</th></tr></thead><tbody>
        @forelse($items as $payment)
            @php
                $licenseRecord = $payment->license;
                $paymentStatus = $payment->statusRelation ?? $payment->status ?? null;
                $statusKey = data_get($paymentStatus, 'code') ?: data_get($paymentStatus, 'key') ?: 'unknown';
                $licenseTitle = $licenseRecord?->title ?: $licenseRecord?->license_number ?: '—';
                $previewDetails = [
                    'number' => '#'.$payment->id,
                    'title' => $licenseTitle,
                    'license_number' => $licenseRecord?->license_number ?: '—',
                    'amount' => number_format((float) $payment->amount, 2).' '.($payment->currency ?: 'SAR'),
                    'invoice_number' => $payment->invoice_number ?: '—',
                    'bank_name' => $payment->bank_name ?: '—',
                    'requested_by' => $nameOf($payment->requester ?? $payment->requestedBy ?? null),
                    'requested_at' => $dateOf($payment->created_at),
                    'status' => $nameOf($paymentStatus),
                    'status_key' => $statusKey,
                    'url' => $url('modules.licenses.finance.show', $payment->getRouteKey()),
                ];
            @endphp
            <tr>
                <td><button type="button" class="lic-table__primary lic-table__primary--button lic-sensitive" data-bs-toggle="modal" data-bs-target="#licenseFinanceQuickViewModal" data-license-preview='@json($previewDetails)'>#{{ $payment->id }}</button></td>
                <td>{{ $licenseTitle }}<span class="lic-table__sub lic-sensitive">{{ $licenseRecord?->license_number }}</span></td>
                <td class="lic-sensitive">{{ number_format((float)$payment->amount,2) }} {{ $payment->currency ?: 'SAR' }}</td>
                <td><span class="lic-status lic-status--{{ $statusKey }}">{{ $nameOf($paymentStatus) }}</span></td>
                <td>{{ $nameOf($payment->requester ?? $payment->requestedBy ?? null) }}</td>
                <td>{{ $dateOf($payment->created_at) }}</td>
                <td><button type="button" class="lic-btn lic-btn--sm" data-bs-toggle="modal" data-bs-target="#licenseFinanceQuickViewModal" data-license-preview='@json($previewDetails)' aria-haspopup="dialog"><i class="bi bi-eye"></i>{{ __('licenses.view') }}</button></td>
            </tr>
        @empty<tr><td colspan="7" class="lic-empty">{{ __('licenses.payments.empty') }}</td></tr>@endforelse
        </tbody></table></div>
        @if(method_exists($items,'links') && $items->total()>0)<div class="lic-pagination"><span>{{ __('licenses.results',['count'=>$items->total()]) }}</span>{{ $items->withQueryString()->links('pagination.hm') }}</div>@endif
    </section>
</div>
@endsection

@push('modals')
    @include('licenses.partials.finance-quick-view-modal')
@endpush

@push('scripts')<script src="{{ asset('js/hm-licenses.js') }}?v={{ filemtime(public_path('js/hm-licenses.js')) }}"></script>@endpush
