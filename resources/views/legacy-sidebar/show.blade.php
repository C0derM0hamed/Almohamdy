@extends('layouts.app')
@php($labels = ['name'=>'الاسم','name_ar'=>'الاسم بالعربية','name_en'=>'الاسم بالإنجليزية','info'=>'الوصف','message'=>'الرسالة','content'=>'المحتوى','note'=>'ملاحظات','notes'=>'ملاحظات','subject'=>'الموضوع','requests'=>'طلبات الدعوى','patient_name'=>'اسم المريض','file_number'=>'رقم الملف','mobile'=>'الجوال','email'=>'البريد الإلكتروني','claimant_name'=>'اسم المدعي','claimant_id'=>'هوية المدعي','claimant_cr_number'=>'السجل التجاري للمدعي','case_date'=>'تاريخ القضية','defendant_name'=>'اسم المدعى عليه','defendant_id'=>'هوية المدعى عليه','defendant_cr_number'=>'السجل التجاري للمدعى عليه','claim_type'=>'نوع المطالبة','claim_type_other'=>'نوع المطالبة الآخر','claim_amount'=>'قيمة المطالبة','case_summary'=>'ملخص القضية','liable_mobile'=>'جوال المسؤول','nationality'=>'الجنسية','id_number'=>'رقم الهوية','covered_amount'=>'المبلغ المغطى','uncovered_amount'=>'المبلغ غير المغطى','received_date'=>'تاريخ الاستلام','attachments_name'=>'وصف المرفقات','specialty'=>'التخصص','agency_number'=>'رقم الوكالة','admission_date'=>'تاريخ التنويم','discharge_date'=>'تاريخ الخروج','patient_nationality'=>'جنسية المريض','patient_idno'=>'هوية المريض','issued_date'=>'تاريخ الإصدار','due_date'=>'تاريخ الاستحقاق','liable_name'=>'اسم المسؤول','liable_idno'=>'هوية المسؤول','liable_nationality'=>'جنسية المسؤول','value'=>'القيمة','value_writing'=>'القيمة كتابةً','entity'=>'الجهة','company_representative'=>'ممثل الشركة','adjudicated_amount'=>'المبلغ المحكوم به','instrumen_number'=>'رقم السند','instrumen_number_date'=>'تاريخ السند','transferred_from'=>'محول من','decision_number'=>'رقم القرار','decision_date'=>'تاريخ القرار','notice_date'=>'تاريخ التبليغ','objecting_party'=>'الطرف المعترض','status'=>'الحالة','publish'=>'الحالة','created_at'=>'تاريخ الإنشاء','date'=>'التاريخ','case_number'=>'رقم القضية','request_number'=>'رقم الطلب','Answer'=>'الإجابة','becuse'=>'سبب الإجراء'])
@section('title', $spec['label'])
@section('figma_page_header', 'true')
@push('workflow_styles')
<link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-workflow" dir="rtl">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => 'الخدمات التشغيلية'], ['label' => $spec['label']]], 'title' => $spec['label'], 'subtitle' => 'تفاصيل السجل والمرفقات والإجراءات المرتبطة', 'heroIconSrc' => asset('images/figma/workflows/references.svg'), 'heroIconSize' => 32])
    <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn btn-outline-secondary" href="{{ route('modules.legacy-sidebar.index', $page) }}">رجوع</a>
        @if($page === 'medica_report' || $caseStatuses !== [])
            <a class="btn btn-outline-primary" href="{{ route('modules.legacy-sidebar.pdf', [$page, $row->id]) }}"><i class="bi bi-filetype-pdf"></i> PDF</a>
        @endif
        @if($spec['fields'] !== [])
            <a class="btn btn-primary" href="{{ route('modules.legacy-sidebar.edit', [$page, $row->id]) }}">تعديل</a>
        @endif
    </div>

    <section class="card border-0 shadow-sm mt-3"><div class="card-body"><div class="row g-3">
        @foreach($columns as $column)
            @php($rawValue = $row->{$column} ?? null)
            @php($displayValue = $options[$column][$rawValue] ?? $rawValue ?? '—')
            <div class="col-md-6"><div class="text-muted small">{{ $labels[$column] ?? str($column)->headline() }}</div><div class="fw-semibold text-break">{!! nl2br(e((string) $displayValue)) !!}</div></div>
        @endforeach
    </div></div></section>

    @if($workflowHistory !== [])
        <section class="card border-0 shadow-sm mt-3"><div class="card-body"><h2 class="h6 mb-3">سجل الإجراءات والجلسات</h2><div class="list-group">
            @foreach($workflowHistory as $event)
                <div class="list-group-item"><div class="d-flex justify-content-between gap-3"><strong>{{ $event->status_name ?? 'إجراء' }}</strong><span class="text-muted small">{{ $event->created_at ?? '' }}</span></div>
                    @if(!empty($event->reason_name) || !empty($event->details) || !empty($event->notes))<div class="small mt-1">{{ $event->reason_name ?? $event->details ?? $event->notes }}</div>@endif
                    @if(!empty($event->has_request_file) || !empty($event->has_session_file))<div class="d-flex gap-2 mt-2">
                        @if(!empty($event->has_request_file))<a class="btn btn-sm btn-outline-primary" href="{{ route('modules.legacy-sidebar.case-actions.download', [$page, $row->id, $event->id, 'request_file']) }}">ملف الطلب</a>@endif
                        @if(!empty($event->has_session_file))<a class="btn btn-sm btn-outline-primary" href="{{ route('modules.legacy-sidebar.case-actions.download', [$page, $row->id, $event->id, 'session_file']) }}">ملف الجلسة</a>@endif
                    </div>@endif
                </div>
            @endforeach
        </div></div></section>
    @endif

    @if($caseStatuses !== [])
        <section id="case-action" class="card border-0 shadow-sm mt-3"><div class="card-body"><h2 class="h6 mb-3">تحرير الحالة وإضافة إجراء للقضية</h2><form class="row g-3" method="post" enctype="multipart/form-data" action="{{ route('modules.legacy-sidebar.case-actions.store', [$page, $row->id]) }}">@csrf
            <div class="col-md-4"><label class="form-label">الحالة</label><select class="form-select" name="status_id" required><option value="">اختر</option>@foreach($caseStatuses as $statusId => $statusLabel)<option value="{{ $statusId }}">{{ $statusLabel }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">رقم الطلب</label><input class="form-control" name="request_number"></div><div class="col-md-4"><label class="form-label">رقم القضية</label><input class="form-control" name="case_number"></div>
            <div class="col-md-4"><label class="form-label">تاريخ الطلب</label><input class="form-control" type="date" name="request_date"></div><div class="col-md-4"><label class="form-label">تاريخ الجلسة</label><input class="form-control" type="date" name="sessions_date"></div><div class="col-md-4"><label class="form-label">الجلسة القادمة</label><input class="form-control" type="date" name="next_sessions_date"></div>
            <div class="col-12"><label class="form-label">التفاصيل</label><textarea class="form-control" name="details" rows="3"></textarea></div><div class="col-md-6"><label class="form-label">ملف الطلب</label><input class="form-control" type="file" name="request_file"></div><div class="col-md-6"><label class="form-label">ملف الجلسة</label><input class="form-control" type="file" name="session_file"></div>
            <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-circle"></i> حفظ الإجراء</button></div>
        </form></div></section>
        <section class="card border-0 shadow-sm mt-3"><div class="card-body"><h2 class="h6 mb-3">طلبات الإفادة</h2><form class="row g-3" method="post" enctype="multipart/form-data" action="{{ route('modules.legacy-sidebar.case-statements.store', [$page, $row->id]) }}">@csrf
            <div class="col-md-6"><label class="form-label">القسم المرسل إليه</label><input class="form-control" type="number" min="1" name="section"></div><div class="col-md-6"><label class="form-label">ملف الطلب</label><input class="form-control" type="file" name="file"></div><div class="col-md-6"><label class="form-label">ملخص الطلب</label><input class="form-control" name="summary"></div><div class="col-md-6"><label class="form-label">التفاصيل</label><textarea class="form-control" name="details" rows="2" required></textarea></div><div class="col-12"><button class="btn btn-outline-primary">إرسال طلب إفادة</button></div>
        </form>
        @if($caseStatements !== [])<div class="list-group mt-3">@foreach($caseStatements as $statement)<div class="list-group-item"><strong>طلب #{{ $statement->id }}</strong><div class="small text-muted">{{ $statement->summary ?: $statement->details }}</div>@if(filled($statement->reply ?? null))<div class="mt-2 text-success"><strong>الإفادة:</strong> {{ $statement->reply }}</div>@elseif($canReplyToCaseStatements)<form class="row g-2 mt-2" method="post" enctype="multipart/form-data" action="{{ route('modules.legacy-sidebar.case-statements.reply', [$page, $row->id, $statement->id]) }}">@csrf<div class="col-md-8"><textarea class="form-control" name="reply" rows="2" required></textarea></div><div class="col-md-4"><input class="form-control mb-2" type="file" name="file"><button class="btn btn-sm btn-success">حفظ الإفادة</button></div></form>@endif</div>@endforeach</div>@endif
        </div></section>
    @endif

    @if($supportsAttachments)
        <section class="card border-0 shadow-sm mt-3"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h6 mb-0">المرفقات</h2><form method="post" enctype="multipart/form-data" action="{{ route('modules.legacy-sidebar.attachments.store', [$page, $row->id]) }}">@csrf<input type="file" name="attachment" required><button class="btn btn-sm btn-primary">رفع</button></form></div><div class="list-group">@forelse($attachments as $attachment)<a class="list-group-item list-group-item-action" href="{{ route('modules.legacy-sidebar.attachments.download', [$page, $row->id, $attachment->id]) }}"><i class="bi bi-paperclip"></i> {{ $attachment->name ?? basename($attachment->file_name ?? $attachment->file ?? 'مرفق') }}</a>@empty<span class="text-muted">لا توجد مرفقات.</span>@endforelse</div></div></section>
    @endif
</div>
@endsection
