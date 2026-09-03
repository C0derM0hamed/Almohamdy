@extends('layouts.app')
@section('title', __('legal_claims.title'))
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
@php
    $localeColumn = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $localized = static fn (object $record, string $field): string => (string) (
        data_get($record, $field.'_'.$localeColumn)
        ?: data_get($record, $field.'_ar')
        ?: data_get($record, $field.'_en')
        ?: '—'
    );
@endphp
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => __('dashboard.modules')], ['label' => __('legal_claims.title')]], 'title' => __('legal_claims.title'), 'subtitle' => __('legal_claims.scope'), 'heroIconSrc' => asset('images/figma/workflows/legal.svg'), 'heroIconSize' => 32, 'actionUrl' => $canCreate ? route('modules.legal-claims.create') : null, 'actionLabel' => __('legal_claims.create'), 'actionIconSrc' => asset('images/figma/technical-failures/add.svg')])
    <section class="wf-table-panel wf-table-panel--contained mt-3" aria-label="{{ __('legal_claims.summary_statuses') }}">
        <div class="d-flex align-items-center justify-content-between px-3 pt-3"><h2 class="h6 mb-0">{{ __('legal_claims.summary_statuses') }}</h2><span class="text-muted small">{{ __('legal_claims.summary_hint') }}</span></div>
        <div class="table-responsive p-3">@foreach($statusDashboard->chunk(7) as $statusRow)<table class="wf-table mb-3"><thead><tr>@foreach($statusRow as $status)<th>{{ $localized($status, 'name') }}</th>@endforeach</tr></thead><tbody><tr>@foreach($statusRow as $status)<td class="text-center"><strong>{{ $status->count }}</strong></td>@endforeach</tr></tbody></table>@endforeach</div>
    </section>
    <form class="wf-search-panel mt-3" method="get"><h2>{{ __('legal_claims.search') }}</h2><div class="wf-filter-grid wf-filter-grid--technical">
        <div class="wf-field"><label for="claim-mobile">{{ __('legal_claims.search_by_case') }}</label><input id="claim-mobile" name="mobile" value="{{ $filters['mobile'] }}" inputmode="numeric"></div>
        <div class="wf-field"><label for="claim-status">{{ __('legal_claims.status') }}</label><span class="wf-select-wrap"><select id="claim-status" name="status_id"><option value="">{{ __('legal_claims.all') }}</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected($filters['status_id'] === $status->id)>{{ $localized($status, 'name') }}</option>@endforeach</select><img src="{{ asset('images/figma/technical-failures/select.svg') }}" alt=""></span></div>
        <div class="wf-field"><label for="claim-to">{{ __('legal_claims.to') }} {{ __('legal_claims.date') }}</label><span class="wf-date-wrap"><input id="claim-to" type="date" name="to" value="{{ $filters['to'] }}"><img src="{{ asset('images/figma/technical-failures/calendar.svg') }}" alt=""></span></div>
        <div class="wf-field"><label for="claim-from">{{ __('legal_claims.from') }} {{ __('legal_claims.date') }}</label><span class="wf-date-wrap"><input id="claim-from" type="date" name="from" value="{{ $filters['from'] }}"><img src="{{ asset('images/figma/technical-failures/calendar.svg') }}" alt=""></span></div>
        <button class="wf-search-btn" type="submit" aria-label="{{ __('legal_claims.search') }}"><i class="bi bi-search"></i></button>
    </div><div class="row g-3 mt-1"><div class="col-md-3"><label class="form-label" for="claim-statement-filter">{{ __('legal_claims.statement_filter') }}</label><select id="claim-statement-filter" class="form-select" name="statement_filter"><option value="">{{ __('legal_claims.all') }}</option><option value="has_statements" @selected($filters['statement_filter'] === 'has_statements')>{{ __('legal_claims.has_statements') }}</option><option value="without_statements" @selected($filters['statement_filter'] === 'without_statements')>{{ __('legal_claims.without_statements') }}</option></select></div></div><div class="mt-3 d-flex gap-2"><button class="btn btn-dark" type="submit">{{ __('legal_claims.search') }}</button><a class="btn btn-primary" href="{{ route('modules.legal-claims.index') }}">{{ __('legal_claims.reset') }}</a></div></form>
    <section class="wf-table-panel wf-table-panel--contained mt-3">@include('layouts.partials.figma-workflow-table-head', ['title' => __('legal_claims.title'), 'items' => $claims])
        <div class="table-responsive"><table class="wf-table"><thead><tr><th>{{ __('legal_claims.file_number') }}</th><th>{{ __('legal_claims.hospital') }}</th><th>{{ __('legal_claims.payment_type') }}</th><th>{{ __('legal_claims.liable_idno') }}</th><th>{{ __('legal_claims.approval_status') }}</th><th>{{ __('legal_claims.case_number') }}</th><th>{{ __('legal_claims.claim_amount') }}</th><th>{{ __('legal_claims.status') }}</th><th>{{ __('legal_claims.actions') }}</th></tr></thead><tbody>
        @forelse($claims as $claim)<tr><td><span class="wf-code">{{ $claim->file_number }}</span></td><td>{{ $localized($claim, 'company_name') }}</td><td>{{ $localized($claim, 'payment_name') }}</td><td><span class="wf-code">{{ $claim->liable_idno ?: '—' }}</span></td><td>{{ $localized($claim, 'approval_status_name') }}</td><td><span class="wf-code">{{ $claim->case_number ?: '—' }}</span></td><td>{{ $claim->amount_rest ?: '—' }}</td><td><span class="wf-status">{{ $localized($claim, 'status_name') !== '—' ? $localized($claim, 'status_name') : $claim->status }}</span></td><td><a class="wf-view" href="{{ route('modules.legal-claims.show', $claim->id) }}" title="{{ __('legal_claims.open') }}"><i class="bi bi-eye"></i></a></td></tr>
        @empty<tr><td colspan="9" class="text-center text-muted py-5">{{ __('legal_claims.empty') }}</td></tr>@endforelse
        </tbody></table></div><div class="p-3">{{ $claims->links() }}</div>
    </section>
</div>
@endsection
