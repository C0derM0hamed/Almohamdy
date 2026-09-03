<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><style>body{font-family:dejavusans,sans-serif;font-size:12px}h1{font-size:20px;border-bottom:1px solid #999;padding-bottom:8px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.item{border-bottom:1px solid #ddd;padding:6px}.label{color:#666;font-size:10px}.value{font-weight:bold;white-space:pre-wrap}</style></head>
<body>
@php($title = match ($page) {
    'rep_ss' => 'طلب تقرير طبي',
    'sit_rep2' => 'طلب إفادة - استفسار مطالبة مالية',
    'financial_claim_notice' => 'إشعار مطالبة مالية',
    'archives' => 'استفسار من قسم الشؤون القانونية',
    'lawsuit_complete_documents' => 'استكمال مستندات المطالبة المالية',
    'executive_title' => 'السند التنفيذي',
    'executive_title_complete_documents' => 'استكمال مستندات السند التنفيذي',
    'lawsuitapproval' => 'اعتماد المطالبة المالية',
    'administrative_cases' => 'قضية إدارية',
    'commercial_cases' => 'قضية تجارية',
    'labor_cases' => 'قضية عمالية',
    'medical_cases' => 'قضية طبية',
    default => 'سجل مالي',
})
<h1>{{ $title }}</h1>
<div class="grid">
@foreach((array) $record as $key => $value)
    @if($key !== 'id' && is_scalar($value))<div class="item"><div class="label">{{ str((string) $key)->headline() }}</div><div class="value">{{ $value }}</div></div>@endif
@endforeach
</div>
@if($attachments !== [])<h2>المرفقات</h2><ul>@foreach($attachments as $attachment)<li>{{ $attachment->name ?? basename($attachment->file_name ?? $attachment->file ?? 'مرفق') }}</li>@endforeach</ul>@endif
@if($history !== [])<h2>سجل الإجراءات</h2><ul>@foreach($history as $event)<li>{{ $event->status_name ?? 'إجراء' }} — {{ $event->becuse ?? '' }}</li>@endforeach</ul>@endif
</body></html>
