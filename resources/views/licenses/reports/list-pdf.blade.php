<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale()==='ar'?'rtl':'ltr' }}">
<head>
<meta charset="utf-8">
<title>Licenses Report</title>
<style>
@page{margin:8mm;size:A4 landscape}
body{font-family:DejaVu Sans,sans-serif;color:#203449;font-size:8px;line-height:1.35}
body.pdf-rtl{direction:rtl;text-align:right}
body.pdf-ltr{direction:ltr;text-align:left}
h1{color:#18334f;font-size:16px;margin:0 0 4px}
.meta{color:#687989;margin-bottom:10px}
.pdf-rtl .meta,.pdf-rtl th,.pdf-rtl td{text-align:right}
.pdf-ltr .meta,.pdf-ltr th,.pdf-ltr td{text-align:left}
table{width:100%;max-width:100%;border-collapse:collapse;table-layout:fixed}
th,td{border:1px solid #ccd9e3;padding:4px;vertical-align:top;overflow-wrap:anywhere;word-wrap:break-word}
th{background:#18334f;color:#fff}
tr:nth-child(even) td{background:#f5f8fa}
.badge{font-weight:bold}
.footer{margin-top:10px;color:#687989;text-align:center}
.ltr{direction:ltr!important;text-align:left!important;unicode-bidi:plaintext}
</style>
</head>
<body class="{{ app()->getLocale() === 'ar' ? 'pdf-rtl' : 'pdf-ltr' }}">
@php
$nameOf=static function($i){if(!$i)return '—';if(is_string($i))return $i;if(method_exists($i,'displayName'))return $i->displayName();if(method_exists($i,'localizedName'))return $i->localizedName();$f=app()->getLocale()==='ar'?'name_ar':'name_en';return data_get($i,$f)?:data_get($i,'name')?:data_get($i,'hr_name')?:'—';};
$dateOf=static fn($v)=>$v?($v instanceof \DateTimeInterface?$v->format('Y-m-d'):substr((string)$v,0,10)):'—';
$items=$licenses ?? collect();
@endphp
<h1>{{ $reportTitle ?? __('licenses.pdf.list_title') }}</h1>
<div class="meta">{{ __('licenses.pdf.generated_at') }}: {{ ($generatedAt ?? now())->format('Y-m-d H:i') }} @if(!empty($filterSummary)) · {{ __('licenses.pdf.filters') }}: {{ $filterSummary }} @endif</div>
<table>
<thead><tr>
<th>{{ __('licenses.fields.license_number') }}</th>
<th>{{ __('licenses.fields.type') }}</th>
<th>{{ __('licenses.fields.authority') }}</th>
<th>{{ __('licenses.fields.hospital_branch') }}</th>
<th>{{ __('licenses.fields.departments') }}</th>
<th>{{ __('licenses.fields.responsible') }}</th>
<th>{{ __('licenses.fields.expiry_date') }}</th>
<th>{{ __('licenses.fields.status') }}</th>
<th>{{ __('licenses.fields.renewal_stage') }}</th>
</tr></thead>
<tbody>
@forelse($items as $license)
<tr>
<td class="ltr">{{ $license->license_number?:'#'.$license->id }}</td>
<td>{{ $nameOf($license->licenseType ?? $license->type ?? null) }}</td>
<td>{{ $nameOf($license->authority ?? null) }}</td>
<td>{{ $nameOf($license->hospitalBranch ?? null) }}</td>
<td>{{ ($license->departments ?? $license->branches ?? collect())->map(fn($department)=>$nameOf($department))->implode('، ')?:'—' }}</td>
<td>{{ $nameOf($license->responsibleUser ?? $license->responsible ?? null) }}</td>
<td class="ltr">{{ $dateOf($license->expiry_date) }}</td>
<td class="badge">{{ $nameOf($license->statusRelation ?? $license->status ?? null) }}</td>
<td>{{ $nameOf($license->renewalStage ?? $license->stage ?? null) }}</td>
</tr>
@empty
<tr><td colspan="9">{{ __('licenses.empty') }}</td></tr>
@endforelse
</tbody>
</table>
<div class="footer">{{ __('licenses.pdf.confidential') }} · {{ __('licenses.results',['count'=>count($items)]) }}</div>
</body>
</html>
