@extends('layouts.app')

@php
    $labels = ['name' => 'الاسم', 'name_ar' => 'الاسم بالعربية', 'name_en' => 'الاسم بالإنجليزية', 'name_ch' => 'الاسم بالصينية', 'info' => 'الوصف', 'message' => 'الرسالة', 'content' => 'المحتوى', 'note' => 'ملاحظات', 'notes' => 'ملاحظات', 'subject' => 'الموضوع', 'description' => 'الوصف', 'patient_name' => 'اسم المريض', 'file_number' => 'رقم الملف', 'mobile' => 'الجوال', 'email' => 'البريد الإلكتروني', 'email_to' => 'إلى', 'email_cc' => 'نسخة إلى', 'claimant_name' => 'اسم المدعي', 'claimant_id' => 'هوية المدعي', 'claimant_cr_number' => 'السجل التجاري للمدعي', 'case_date' => 'تاريخ القضية', 'defendant_name' => 'اسم المدعى عليه', 'defendant_id' => 'هوية المدعى عليه', 'defendant_cr_number' => 'السجل التجاري للمدعى عليه', 'claim_type' => 'نوع المطالبة', 'claim_type_other' => 'نوع المطالبة الآخر', 'claim_amount' => 'قيمة المطالبة', 'case_summary' => 'ملخص القضية', 'liable_mobile' => 'جوال المسؤول', 'covered_amount' => 'المبلغ المغطى', 'uncovered_amount' => 'المبلغ غير المغطى', 'received_date' => 'تاريخ الاستلام', 'attachments_name' => 'وصف المرفقات', 'objecting_party' => 'الطرف المعترض', 'decision_number' => 'رقم القرار', 'decision_date' => 'تاريخ القرار', 'notice_date' => 'تاريخ الإشعار', 'id_number' => 'رقم الهوية', 'nationality' => 'الجنسية', 'patient_nationality' => 'جنسية المريض', 'liable_nationality' => 'جنسية المسؤول', 'liable_name' => 'اسم المسؤول', 'liable_idno' => 'هوية المسؤول', 'issued_date' => 'تاريخ الإصدار', 'due_date' => 'تاريخ الاستحقاق', 'value' => 'قيمة السند', 'value_writing' => 'قيمة السند كتابةً', 'entity' => 'الجهة', 'company_representative' => 'ممثل الشركة', 'adjudicated_amount' => 'المبلغ المحكوم به', 'transferred_from' => 'محول من', 'request_number' => 'رقم الطلب', 'case_number' => 'رقم القضية', 'entry_date' => 'تاريخ الدخول', 'exit_date' => 'تاريخ الخروج', 'medical_diagnosis' => 'التشخيص', 'treatment' => 'العلاج', 'recommendation' => 'التوصية', 'report_type' => 'نوع التقرير', 'visit_date' => 'تاريخ الزيارة', 'father_full_name' => 'اسم الأب', 'mother_full_name' => 'اسم الأم', 'newborn_name' => 'اسم المولود', 'newborn_file_number' => 'رقم ملف المولود', 'mother_file_number' => 'رقم ملف الأم', 'birth_notification_obstetrics' => 'اسم من قام بالتوليد', 'language' => 'اللغة', 'date' => 'التاريخ', 'period' => 'الفترة', 'location' => 'الموقع', 'code' => 'الكود', 'code_reason' => 'سبب النداء', 'floor_id' => 'الدور', 'section_id' => 'القسم', 'branches_id' => 'الفرع', 'subsection' => 'القسم الفرعي', 'eventTitle' => 'الطبيب', 'eventLabel' => 'نوع المناوبة', 'eventStartDate' => 'بداية المناوبة', 'eventEndDate' => 'نهاية المناوبة', 'alternative_name' => 'الاسم البديل'];
@endphp

@section('title', ($row ? 'تعديل' : 'إضافة').' - '.$spec['label'])
@section('figma_page_header', 'true')
@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
@endpush
@section('content')
<div class="hm-fm hm-workflow" dir="rtl">
    @include('layouts.partials.figma-module-header', ['crumbs' => [['label' => 'الخدمات التشغيلية'], ['label' => $spec['label']]], 'title' => ($row ? 'تعديل: ' : 'إضافة: ').$spec['label'], 'subtitle' => 'إدخال البيانات المعتمدة ضمن نطاق الفرع', 'heroIconSrc' => asset('images/figma/workflows/references.svg'), 'heroIconSize' => 32, 'actionUrl' => route('modules.legacy-sidebar.index', $page), 'actionLabel' => 'رجوع', 'actionIconSrc' => asset('images/figma/technical-failures/add.svg')])
    @if($errors->any())<div class="alert alert-danger mt-3"><strong>تعذر حفظ البيانات.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form class="card border-0 shadow-sm" method="post" enctype="multipart/form-data" action="{{ $row ? route('modules.legacy-sidebar.update', [$page, $row->id]) : route('modules.legacy-sidebar.store', $page) }}">
        @csrf @if($row) @method('PUT') @endif
        <div class="card-body row g-3">
            @foreach($spec['fields'] as $field)
                @continue(!in_array($field, $columns, true))
                @php($value = old($field, $row?->{$field} ?? ($defaults[$field] ?? '')))
                <div class="col-md-6">
                    <label class="form-label" for="legacy-{{ $field }}">{{ $labels[$field] ?? str($field)->headline() }}</label>
                    @if(isset($options[$field]) && $options[$field] !== [])
                        <select class="form-select" id="legacy-{{ $field }}" name="{{ $field }}"><option value="">اختر</option>@foreach($options[$field] as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>@endforeach</select>
                    @elseif(in_array($field, ['info','message','content','note','notes','subject','description','case_summary','medical_diagnosis','treatment','recommendation','requests','family_support'], true))
                        <textarea class="form-control" id="legacy-{{ $field }}" name="{{ $field }}" rows="4">{{ $value }}</textarea>
                    @elseif(in_array($field, ['date','case_date','birth_date','visit_date','admission_date','discharge_date','entry_date','exit_date','paid_date','eventStartDate','eventEndDate','instrumen_number_date','decision_date','received_date','notice_date','issued_date','due_date'], true))
                        <input class="form-control" id="legacy-{{ $field }}" type="date" name="{{ $field }}" value="{{ $value }}">
                    @else
                        <input class="form-control" id="legacy-{{ $field }}" name="{{ $field }}" value="{{ $value }}">
                    @endif
                    @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            @endforeach
            @if($page === 'emergency_new_call')
                <div class="col-12"><label class="form-label">المسؤولون</label><textarea class="form-control" name="responsibles" rows="3" placeholder="أرقام المستخدمين، رقم في كل سطر أو افصل بينها بفاصلة">{{ old('responsibles') }}</textarea><div class="form-text">يتم حفظ كل مسؤول في جدول نداءات السنترال الأصلي.</div></div>
            @endif
            @if($page === 'medical_approval_notifications')
                <div class="col-md-6"><label class="form-label">حالة الموافقة الطبية</label><select class="form-select" name="medical_approval_status_id" required><option value="">اختر</option>@foreach($options['medical_approval_status_id'] ?? [] as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected((string) old('medical_approval_status_id', $workflowState?->medical_approval_status_id) === (string) $optionValue)>{{ $optionLabel }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">سبب الرفض</label><select class="form-select" name="rejection_reason_id"><option value="">بدون سبب</option>@foreach($options['rejection_reason_id'] ?? [] as $optionValue => $optionLabel)<option value="{{ $optionValue }}" @selected((string) old('rejection_reason_id', $workflowState?->rejection_reason_id) === (string) $optionValue)>{{ $optionLabel }}</option>@endforeach</select></div>
            @endif
            @if(in_array($page, ['rep_ss', 'sit_rep2'], true))
                <div class="col-12 border-top pt-3 mt-2">
                    <label class="form-label">المرفقات</label>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-7"><input class="form-control" type="file" name="fileToUpload[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" @required(!$row)></div>
                        <div class="col-md-5"><input class="form-control" type="text" name="filename[]" placeholder="وصف المرفق" @required(!$row)></div>
                    </div>
                    <div class="form-text">يمكن اختيار أكثر من ملف، ويُستخدم وصف المرفق في شاشة الطلب.</div>
                    @error('fileToUpload')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('filename')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            @endif
        </div>
        <div class="card-footer text-start"><button class="btn btn-primary"><i class="bi bi-check2"></i> حفظ البيانات</button></div>
    </form>
</div>
@endsection
