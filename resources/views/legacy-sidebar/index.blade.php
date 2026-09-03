@extends('layouts.app')

@php
    $labels = [
        'name' => 'الاسم', 'name_ar' => 'الاسم بالعربية', 'name_en' => 'الاسم بالإنجليزية', 'name_ch' => 'الاسم بالصينية',
        'info' => 'الوصف', 'message' => 'الرسالة', 'content' => 'المحتوى', 'note' => 'ملاحظات', 'notes' => 'ملاحظات',
        'subject' => 'الموضوع', 'description' => 'الوصف', 'patient_name' => 'اسم المريض', 'file_number' => 'رقم الملف',
        'file_no' => 'رقم الملف', 'mobile' => 'الجوال', 'email' => 'البريد الإلكتروني', 'email_to' => 'إلى', 'email_cc' => 'نسخة إلى',
        'administrative_cases_payment_type_id' => 'نوع سداد القضية الإدارية', 'commercial_cases_payment_type_id' => 'نوع سداد القضية التجارية', 'labor_cases_payment_type_id' => 'نوع سداد القضية العمالية', 'medical_cases_payment_type_id' => 'نوع سداد القضية الطبية', 'executive_title_payment_type_id' => 'نوع سداد السند التنفيذي',
        'claimant_name' => 'اسم المدعي', 'claimant_id' => 'هوية المدعي', 'claimant_cr_number' => 'السجل التجاري للمدعي', 'case_date' => 'تاريخ القضية', 'defendant_name' => 'اسم المدعى عليه', 'defendant_id' => 'هوية المدعى عليه', 'defendant_cr_number' => 'السجل التجاري للمدعى عليه', 'claim_type' => 'نوع المطالبة', 'claim_type_other' => 'نوع المطالبة الآخر', 'claim_amount' => 'قيمة المطالبة', 'case_summary' => 'ملخص القضية', 'liable_mobile' => 'جوال المسؤول', 'nationality' => 'الجنسية', 'id_number' => 'رقم الهوية', 'covered_amount' => 'المبلغ المغطى', 'uncovered_amount' => 'المبلغ غير المغطى', 'received_date' => 'تاريخ الاستلام', 'attachments_name' => 'وصف المرفقات', 'requests' => 'طلبات الدعوى',
        'specialty' => 'التخصص', 'agency_number' => 'رقم الوكالة', 'admission_date' => 'تاريخ التنويم', 'discharge_date' => 'تاريخ الخروج', 'patient_nationality' => 'جنسية المريض', 'patient_idno' => 'هوية المريض', 'issued_date' => 'تاريخ الإصدار', 'due_date' => 'تاريخ الاستحقاق', 'liable_name' => 'اسم المسؤول', 'liable_idno' => 'هوية المسؤول', 'liable_nationality' => 'جنسية المسؤول', 'value' => 'القيمة', 'value_writing' => 'القيمة كتابةً', 'entity' => 'الجهة', 'company_representative' => 'ممثل الشركة', 'adjudicated_amount' => 'المبلغ المحكوم به', 'instrumen_number' => 'رقم السند', 'instrumen_number_date' => 'تاريخ السند', 'transferred_from' => 'محول من', 'decision_number' => 'رقم القرار', 'decision_date' => 'تاريخ القرار', 'notice_date' => 'تاريخ التبليغ', 'objecting_party' => 'الطرف المعترض',
        'case_number' => 'رقم القضية', 'request_number' => 'رقم الطلب', 'status' => 'الحالة',
        'publish' => 'الحالة', 'created_at' => 'تاريخ الإنشاء', 'date' => 'التاريخ', 'eventLabel' => 'نوع المناوبة',
        'eventStartDate' => 'بداية المناوبة', 'eventEndDate' => 'نهاية المناوبة', 'alternative_name' => 'الاسم البديل',
    ];
    $title = $spec['label'];
@endphp

@section('title', $title)
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush

@section('content')
<div class="hm-fm hm-workflow">
    @include('layouts.partials.figma-module-header', [
        'crumbs' => [['label' => 'الخدمات التشغيلية'], ['label' => $title]],
        'title' => $title,
        'subtitle' => 'إدارة البيانات والإجراءات ضمن نطاق الفرع',
        'heroIconSrc' => asset('images/figma/workflows/references.svg'),
        'heroIconSize' => 32,
        'actionUrl' => $spec['mode'] === 'sms' ? route('modules.legacy-sidebar.compose', $page) : (($available && $spec['create'] && $spec['fields'] !== []) ? route('modules.legacy-sidebar.create', $page) : null),
        'actionLabel' => $spec['mode'] === 'sms' ? 'إرسال رسالة' : 'إضافة سجل',
        'actionIconSrc' => asset('images/figma/technical-failures/add.svg'),
    ])

    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    @if($caseDashboard !== [])
        <section class="wf-table-panel wf-table-panel--contained mt-3" aria-label="ملخص حالات {{ $title }}">
            <div class="d-flex align-items-center justify-content-between px-3 pt-3">
                <h2 class="h6 mb-0">ملخص حالات {{ $title }}</h2>
                <span class="text-muted small">يُحدّث وفق البحث والفلاتر أدناه</span>
            </div>
            <div class="table-responsive p-3">
                @foreach(array_chunk($caseDashboard, 7) as $statusRow)
                    <table class="wf-table mb-3">
                        <thead><tr>@foreach($statusRow as $status)<th>{{ $status->name_ar }}</th>@endforeach</tr></thead>
                        <tbody><tr>@foreach($statusRow as $status)<td class="text-center"><strong>{{ $status->count }}</strong></td>@endforeach</tr></tbody>
                    </table>
                @endforeach
            </div>
        </section>
    @endif

    <form class="wf-search-panel mt-3" method="get">
        <h2>بحث في {{ $title }}</h2>
        <div class="wf-filter-grid wf-filter-grid--two">
            <div class="wf-field"><label for="legacy-search">بحث</label><input id="legacy-search" name="search" value="{{ $search }}" placeholder="الاسم أو الرقم أو المحتوى"></div>
            <button class="wf-search-btn wf-search-btn--wide" type="submit"><i class="bi bi-search"></i> بحث</button>
        </div>
        @if($page === 'medical_approval_notifications')
            <div class="row g-2 mt-2"><div class="col-md-3"><label class="form-label">الحالة</label><select class="form-select" name="sent_to_collection"><option value="">الكل</option><option value="0" @selected(request('sent_to_collection') === '0')>لم ترسل للتحصيل</option><option value="1" @selected(request('sent_to_collection') === '1')>أرسلت للتحصيل</option></select></div><div class="col-md-3"><label class="form-label">من تاريخ</label><input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}"></div><div class="col-md-3"><label class="form-label">إلى تاريخ</label><input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}"></div></div>
        @endif
        @if(in_array($spec['mode'], ['case', 'report', 'communication'], true))
            <div class="row g-2 mt-2"><div class="col-md-3"><label class="form-label">الحالة</label>@if($spec['mode'] === 'case' && $caseStatuses !== [])<select class="form-select" name="status"><option value="">الكل</option>@foreach($caseStatuses as $statusId => $statusLabel)<option value="{{ $statusId }}" @selected((string) request('status', request('statusId')) === (string) $statusId)>{{ $statusLabel }}</option>@endforeach</select>@else<input class="form-control" name="status" value="{{ request('status') }}" placeholder="رمز الحالة">@endif</div><div class="col-md-3"><label class="form-label">من تاريخ</label><input class="form-control" type="date" name="begin_date" value="{{ request('begin_date') }}"></div><div class="col-md-3"><label class="form-label">إلى تاريخ</label><input class="form-control" type="date" name="end_date" value="{{ request('end_date') }}"></div></div>
        @endif
    </form>

    <section class="wf-table-panel wf-table-panel--contained mt-3">
        @include('layouts.partials.figma-workflow-table-head', ['title' => $title, 'items' => $rows])
        <div class="table-responsive">
            <table class="wf-table">
                <thead>
                    <tr>
                        <th>#</th>
                        @foreach($columns as $column)
                            <th>{{ $labels[$column] ?? str($column)->headline() }}</th>
                        @endforeach
                        @if($spec['mode'] === 'case')
                            <th>تحرير الحالة</th>
                        @endif
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><span class="wf-code">{{ $row->id ?? '—' }}</span></td>
                        @foreach($columns as $column)
                            @php($value = $row->{$column} ?? null)
                            <td>{{ isset($options[$column][$value]) ? $options[$column][$value] : (is_scalar($value) ? str((string) $value)->limit(120) : '—') }}</td>
                        @endforeach
                        @if($spec['mode'] === 'case')<td>@if(isset($row->id))<a class="wf-view" href="{{ route('modules.legacy-sidebar.show', [$page, $row->id]) }}#case-action" title="تحرير الحالة"><i class="bi bi-pencil-square"></i></a>@endif</td>@endif
                        <td>
                            <div class="wf-actions">
                                @if(isset($row->id))
                                    <button class="wf-view" type="button" data-bs-toggle="modal" data-bs-target="#legacy-view-{{ $page }}-{{ $row->id }}" aria-label="عرض {{ $title }}" title="عرض"><i class="bi bi-eye"></i></button>
                                @endif
                                @if($spec['mode'] === 'case' && isset($row->id))<a class="wf-view" href="{{ route('modules.legacy-sidebar.pdf', [$page, $row->id]) }}" title="PDF تفاصيل القضية"><i class="bi bi-filetype-pdf"></i></a>@endif
                                @if($spec['fields'] !== [] && isset($row->id))<a class="wf-view" href="{{ route('modules.legacy-sidebar.edit', [$page, $row->id]) }}" title="تعديل"><i class="bi bi-pencil"></i></a>@endif
                                @if($spec['mode'] === 'reference' && (isset($row->publish) || isset($row->status)))
                                    <form method="post" action="{{ route('modules.legacy-sidebar.toggle', [$page, $row->id]) }}">@csrf @method('PATCH')<button class="wf-view" title="تغيير الحالة"><i class="bi bi-eye"></i></button></form>
                                @endif
                                @if($page === 'lawsuitapproval' && isset($row->id))<form method="post" action="{{ route('modules.legacy-sidebar.action', [$page, $row->id, 'approve']) }}">@csrf<button class="wf-view" title="اعتماد المطالبة"><i class="bi bi-check2-circle"></i></button></form>@endif
                                @if($page === 'medical_approval_notifications' && isset($row->id) && (string) ($row->sent_to_collection ?? '0') !== '1')<form method="post" action="{{ route('modules.legacy-sidebar.action', [$page, $row->id, 'send']) }}">@csrf<button class="wf-view" title="إشعار قسم التحصيل"><i class="bi bi-send-check"></i></button></form>@endif
                                @if($spec['mode'] === 'reference' && isset($row->id))
                                    <form method="post" action="{{ route('modules.legacy-sidebar.destroy', [$page, $row->id]) }}" onsubmit="return confirm('هل تريد حذف السجل؟')">@csrf @method('DELETE')<button class="wf-view is-danger" title="حذف"><i class="bi bi-trash"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) + 2 + ($spec['mode'] === 'case' ? 1 : 0) }}" class="text-center text-muted py-5">لا توجد سجلات مطابقة.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $rows->links() }}</div>
    </section>
</div>
@endsection

{{-- Keep the record dialogs in the shared modal layer, outside the animated
     page root. This prevents Bootstrap's backdrop from covering a dialog
     that is trapped inside a transformed page container. --}}
@push('modals')
    @foreach($rows as $row)
        <div class="modal fade legacy-view-modal" id="legacy-view-{{ $page }}-{{ $row->id }}" tabindex="-1" aria-labelledby="legacy-view-title-{{ $page }}-{{ $row->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="legacy-view-modal__accent"></div>
                    <div class="modal-header">
                        <div class="legacy-view-modal__heading">
                            <span class="legacy-view-modal__eyebrow">الخدمات التشغيلية</span>
                            <h2 class="legacy-view-modal__title" id="legacy-view-title-{{ $page }}-{{ $row->id }}">{{ $title }}</h2>
                            <span class="legacy-view-modal__record">سجل رقم {{ $row->id }}</span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="legacy-view-modal__grid">
                            @foreach($columns as $column)
                                @php($rawValue = $row->{$column} ?? null)
                                @php($displayValue = $options[$column][$rawValue] ?? $rawValue)
                                @php($displayValue = is_scalar($displayValue) && trim((string) $displayValue) !== '' ? $displayValue : '—')
                                <div class="legacy-view-modal__field">
                                    <span class="legacy-view-modal__label">{{ $labels[$column] ?? str($column)->headline() }}</span>
                                    <span class="legacy-view-modal__value">{!! nl2br(e((string) $displayValue)) !!}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer legacy-view-modal__footer">
                        @if($spec['mode'] === 'case' && isset($row->id))
                            <a class="btn btn-outline-primary" href="{{ route('modules.legacy-sidebar.pdf', [$page, $row->id]) }}" target="_blank" rel="noopener"><i class="bi bi-filetype-pdf"></i> PDF</a>
                        @endif
                        @if($spec['fields'] !== [] && isset($row->id))
                            <a class="btn btn-primary" href="{{ route('modules.legacy-sidebar.edit', [$page, $row->id]) }}"><i class="bi bi-pencil"></i> تعديل</a>
                        @endif
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endpush
