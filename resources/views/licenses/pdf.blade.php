<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}">
<head>
<meta charset="utf-8">
@php $isListReport = (bool) ($isListReport ?? false); @endphp
<title>{{ $isListReport ? 'Licenses Report' : 'License Record' }}</title>
<style>
@if($isListReport)
@page { size: A4 landscape; margin: 8mm; }
@else
@page { size: A4 portrait; margin: 12mm 10mm; }
@endif
body { font-family: DejaVu Sans, sans-serif; color: #203449; font-size: 11px; line-height: 1.55; }
body.pdf-list { font-size: 8px; line-height: 1.35; }
body.pdf-rtl { direction: rtl; text-align: right; }
body.pdf-ltr { direction: ltr; text-align: left; }
h1 { margin: 0; color: #18334f; font-size: 18px; }
h2 { margin: 16px 0 7px; color: #18334f; font-size: 13px; border-bottom: 2px solid #1f5f96; padding-bottom: 4px; }
.head { width: 100%; border-bottom: 3px solid #1f5f96; padding-bottom: 10px; margin-bottom: 8px; }
.head-table { width: 100%; border: 0; }
.head-table td { border: 0; padding: 0; vertical-align: middle; }
.meta { color: #687989; font-size: .92em; }
.pdf-rtl .meta, .pdf-rtl th, .pdf-rtl td { text-align: right; }
.pdf-ltr .meta, .pdf-ltr th, .pdf-ltr td { text-align: left; }
.badge { display: inline-block; padding: 2px 7px; background: #eaf4fc; color: #1f5f96; font-weight: bold; }
table { width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 6px; }
th, td { border: 1px solid #ccd9e3; padding: 5px 4px; vertical-align: top; overflow-wrap: anywhere; word-wrap: break-word; }
th { background: #f1f6f9; color: #334b61; font-size: .92em; }
.list th { background: #18334f; color: #fff; }
.muted { color: #687989; }
.footer { margin-top: 14px; padding-top: 6px; border-top: 1px solid #ccd9e3; color: #687989; text-align: center; font-size: 8px; }
.ltr { direction: ltr !important; text-align: left !important; unicode-bidi: plaintext; }
.pdf-list .col-num { width: 11%; }
.pdf-list .col-title { width: 13%; }
.pdf-list .col-authority { width: 11%; }
.pdf-list .col-type { width: 11%; }
.pdf-list .col-hospital { width: 10%; }
.pdf-list .col-departments { width: 13%; }
.pdf-list .col-responsible { width: 10%; }
.pdf-list .col-date { width: 8%; }
.pdf-list .col-status { width: 7%; }
.pdf-list .col-stage { width: 6%; }
</style>
</head>
<body class="{{ app()->getLocale() === 'ar' ? 'pdf-rtl' : 'pdf-ltr' }} {{ ($isListReport ?? false) ? 'pdf-list' : '' }}">
@php
$nameOf=static function($i){if(!$i)return '—';if(is_string($i))return $i;if(method_exists($i,'displayName'))return $i->displayName();if(method_exists($i,'localizedName'))return $i->localizedName();$f=app()->getLocale()==='ar'?'name_ar':'name_en';return data_get($i,$f)?:data_get($i,'name')?:data_get($i,'hr_name')?:'—';};
$dateOf=static fn($v,$time=false)=>$v?($v instanceof \DateTimeInterface?$v->format($time?'Y-m-d H:i':'Y-m-d'):substr((string)$v,0,$time?16:10)):'—';
$timelineNotice=static function($notice){if(!is_string($notice)||!str_starts_with($notice,'licenses.timeline.'))return $notice;$key=substr($notice,strlen('licenses.timeline.'));$eventKey='licenses.timeline.events.'.$key;$event=__($eventKey);if($event!==$eventKey)return $event;$rootKey='licenses.timeline.'.$key;$root=__($rootKey);return $root!==$rootKey?$root:$notice;};
$items=collect($licenses ?? []);
@endphp
@if($isListReport)
<div class="head">
    <table class="head-table"><tr>
        <td><h1>{{ __('licenses.pdf.list_title') }}</h1><span class="muted">{{ __('licenses.results',['count'=>$items->count()]) }}</span></td>
        <td class="meta" style="width:32%"><strong>{{ __('licenses.pdf.generated_at') }}</strong><br>{{ $dateOf($generatedAt ?? now(), true) }}</td>
    </tr></table>
</div>
<table class="list">
    <colgroup>
        <col class="col-num"><col class="col-title"><col class="col-authority"><col class="col-type"><col class="col-hospital">
        <col class="col-departments"><col class="col-responsible"><col class="col-date"><col class="col-status"><col class="col-stage">
    </colgroup>
    <thead><tr>
        <th>{{ __('licenses.fields.license_number') }}</th>
        <th>{{ __('licenses.fields.title') }}</th>
        <th>{{ __('licenses.fields.authority') }}</th>
        <th>{{ __('licenses.fields.type') }}</th>
        <th>{{ __('licenses.fields.hospital_branch') }}</th>
        <th>{{ __('licenses.fields.departments') }}</th>
        <th>{{ __('licenses.fields.responsible') }}</th>
        <th>{{ __('licenses.fields.expiry_date') }}</th>
        <th>{{ __('licenses.fields.status') }}</th>
        <th>{{ __('licenses.fields.renewal_stage') }}</th>
    </tr></thead>
    <tbody>
    @forelse($items as $item)
        <tr>
            <td class="ltr">{{ $item->license_number ?: '#'.$item->id }}</td>
            <td>{{ $item->title ?: $nameOf($item->type ?? null) }}</td>
            <td>{{ $nameOf($item->authority ?? null) }}</td>
            <td>{{ $nameOf($item->type ?? null) }}</td>
            <td>{{ $nameOf($item->hospitalBranch ?? null) }}</td>
            <td>{{ ($item->departments ?? $item->branches ?? collect())->map(fn ($department) => $nameOf($department))->implode('، ') ?: '—' }}</td>
            <td>{{ $nameOf($item->responsibleUser ?? null) }}</td>
            <td class="ltr">{{ $dateOf($item->expiry_date) }}</td>
            <td>{{ $nameOf($item->status ?? null) }}</td>
            <td>{{ $nameOf($item->renewalStage ?? null) }}</td>
        </tr>
    @empty
        <tr><td colspan="10">—</td></tr>
    @endforelse
    </tbody>
</table>
<div class="footer">{{ __('licenses.pdf.confidential') }} · {{ __('licenses.pdf.generated_at') }}: {{ $dateOf($generatedAt ?? now(), true) }}</div>
@else
@php
$status=$license->statusRelation ?? $license->status ?? null;$timeline=$timelineEntries ?? $license->timelineEntries ?? $license->timeline ?? collect();$renewals=$renewalHistory ?? $license->renewals ?? collect();$attachments=$license->attachments ?? collect();$payments=$license->paymentRequests ?? collect();
@endphp
<div class="head">
    <table class="head-table"><tr>
        <td><h1>{{ __('licenses.pdf.record_title') }}</h1><span class="muted">{{ $license->title ?: $nameOf($license->licenseType ?? $license->type ?? null) }}</span></td>
        <td class="meta" style="width:32%"><strong class="ltr">{{ $license->license_number ?: '#'.$license->id }}</strong><br><span class="badge">{{ $nameOf($status) }}</span></td>
    </tr></table>
</div>
<h2>{{ __('licenses.sections.summary') }}</h2>
<table>
<tr><th>{{ __('licenses.fields.authority') }}</th><td>{{ $nameOf($license->authority ?? null) }}</td><th>{{ __('licenses.fields.type') }}</th><td>{{ $nameOf($license->licenseType ?? $license->type ?? null) }}</td></tr>
<tr><th>{{ __('licenses.fields.hospital_branch') }}</th><td>{{ $nameOf($license->hospitalBranch ?? null) }}</td><th>{{ __('licenses.fields.departments') }}</th><td>{{ ($license->departments ?? $license->branches ?? collect())->map(fn($department)=>$nameOf($department))->implode('، ') ?: '—' }}</td></tr>
<tr><th>{{ __('licenses.fields.responsible') }}</th><td colspan="3">{{ $nameOf($license->responsibleUser ?? $license->responsible ?? null) }}</td></tr>
<tr><th>{{ __('licenses.fields.issue_date') }}</th><td class="ltr">{{ $dateOf($license->issue_date) }}</td><th>{{ __('licenses.fields.expiry_date') }}</th><td class="ltr">{{ $dateOf($license->expiry_date) }}</td></tr>
<tr><th>{{ __('licenses.fields.renewal_stage') }}</th><td>{{ $nameOf($license->renewalStage ?? $license->stage ?? null) }}</td><th>{{ __('licenses.fields.updated_at') }}</th><td class="ltr">{{ $dateOf($license->updated_at,true) }}</td></tr>
<tr><th>{{ __('licenses.fields.notes') }}</th><td colspan="3">{{ $license->notes ?: '—' }}</td></tr>
</table>
<h2>{{ __('licenses.sections.history') }}</h2>
<table class="list"><thead><tr><th>{{ __('licenses.renewal.previous_expiry') }}</th><th>{{ __('licenses.renewal.new_expiry') }}</th><th>{{ __('licenses.renewal.started_at') }}</th><th>{{ __('licenses.renewal.completed_at') }}</th></tr></thead><tbody>@forelse($renewals->sortByDesc('id') as $renewal)<tr><td class="ltr">{{ $dateOf($renewal->previous_expiry_date) }}</td><td class="ltr">{{ $dateOf($renewal->new_expiry_date) }}</td><td class="ltr">{{ $dateOf($renewal->started_at,true) }}</td><td class="ltr">{{ $dateOf($renewal->completed_at,true) }}</td></tr>@empty<tr><td colspan="4">—</td></tr>@endforelse</tbody></table>
<h2>{{ __('licenses.sections.payments') }}</h2>
<table class="list"><thead><tr><th>{{ __('licenses.payments.request_number') }}</th><th>{{ __('licenses.payments.amount') }}</th><th>{{ __('licenses.payments.status') }}</th><th>{{ __('licenses.payments.requested_at') }}</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>#{{ $payment->id }}</td><td class="ltr">{{ number_format((float)$payment->amount,2) }} {{ $payment->currency?:'SAR' }}</td><td>{{ $nameOf($payment->statusRelation ?? $payment->status ?? null) }}</td><td class="ltr">{{ $dateOf($payment->created_at,true) }}</td></tr>@empty<tr><td colspan="4">—</td></tr>@endforelse</tbody></table>
<h2>{{ __('licenses.sections.attachments') }}</h2>
<table class="list"><thead><tr><th>{{ __('licenses.attachments.file') }}</th><th>{{ __('licenses.attachments.context') }}</th><th>{{ __('licenses.attachments.uploaded_at') }}</th></tr></thead><tbody>@forelse($attachments as $attachment)<tr><td>{{ $attachment->original_name }}</td><td>{{ __('licenses.attachments.contexts.'.($attachment->context?:'license')) }}</td><td class="ltr">{{ $dateOf($attachment->uploaded_at ?? $attachment->created_at,true) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>
<h2>{{ __('licenses.sections.timeline') }}</h2>
<table class="list"><thead><tr><th>{{ __('licenses.notifications.event') }}</th><th>{{ __('licenses.fields.description') }}</th><th>{{ __('licenses.fields.created_at') }}</th></tr></thead><tbody>@forelse($timeline->sortByDesc('id') as $entry)<tr><td>{{ __('licenses.timeline.events.'.($entry->event_type?:'settings_changed')) }}</td><td>{{ $timelineNotice($entry->notice)?:'—' }}</td><td class="ltr">{{ $dateOf($entry->date ?? $entry->created_at,true) }}</td></tr>@empty<tr><td colspan="3">—</td></tr>@endforelse</tbody></table>
<div class="footer">{{ __('licenses.pdf.confidential') }} · {{ __('licenses.pdf.generated_at') }}: {{ $dateOf($generatedAt ?? now(),true) }} @if(!empty($generatedBy)) · {{ __('licenses.pdf.generated_by') }}: {{ $nameOf($generatedBy) }} @endif</div>
@endif
</body>
</html>
