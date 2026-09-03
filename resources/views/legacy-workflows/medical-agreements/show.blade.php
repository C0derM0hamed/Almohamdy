@extends('layouts.app')

@section('title', 'تفاصيل اتفاقية الخدمات الطبية')
@section('figma_page_header', true)

@php
    $isSadq = $variant !== 'standard';
    $latestTransaction = $agreement->transactions->last();
    $patientName = trim((string) ($agreement->patient_name_ar ?: $agreement->patient_name_en)) ?: 'غير محدد';
    $contractorName = trim((string) ($agreement->contractor_name_ar ?: $agreement->contractor_name_en)) ?: 'غير محدد';
    $agreementTitle = match ($variant) {
        'sadq' => 'اتفاقية تقديم خدمات طبية — صادق',
        'sadq-manual' => 'اتفاقية تقديم خدمات طبية — صادق (إدخال يدوي)',
        default => 'اتفاقية تقديم خدمات طبية',
    };
    $statusKey = 'pending';
    $statusLabel = 'قيد الانتظار';
    if ($isSadq) {
        $statusKey = match ($latestTransaction?->signStatus) {
            'Completed' => 'completed',
            'Rejected' => 'rejected',
            'In-progress' => 'progress',
            default => 'failed',
        };
        $statusLabel = match ($latestTransaction?->signStatus) {
            'Completed' => 'مكتمل',
            'Rejected' => 'مرفوض',
            'In-progress' => 'قيد التوقيع',
            default => 'تعذر الإرسال',
        };
    } elseif (filled($agreement->emdha_output ?? null)) {
        $statusKey = 'completed';
        $statusLabel = 'تمت المصادقة';
    }
    $birthDate = collect([$agreement->birth_day, $agreement->birth_month, $agreement->birth_year])
        ->filter(fn ($value) => filled($value) && (int) $value > 0)
        ->implode(' / ');
    $birthDate = $birthDate !== '' ? $birthDate.' — '.((int) $agreement->date_type === 1 ? 'هجري' : 'ميلادي') : 'غير محدد';
@endphp

@push('workflow_styles')
    <link href="{{ asset('css/hm-medical-agreement-detail.css') }}?v={{ filemtime(public_path('css/hm-medical-agreement-detail.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-fm hm-medical-agreement-detail" dir="rtl">
        @include('layouts.partials.figma-module-header', [
            'crumbs' => [
                ['label' => 'الخدمات'],
                ['label' => 'المطالبات المالية', 'url' => route('modules.medical-agreements.index', $variant)],
                ['label' => 'تفاصيل الاتفاقية #'.$agreement->id],
            ],
            'title' => 'تفاصيل الاتفاقية',
            'subtitle' => $agreementTitle.' — بيانات موثقة وإجراءات التوقيع',
            'heroIconSrc' => asset('images/figma/workflows/legal.svg'),
            'heroIconSize' => 32,
        ])

        @if (session('success'))
            <div class="alert alert-success mad-alert" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger mad-alert" role="alert"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
        @endif

        <section class="mad-hero" aria-labelledby="medicalAgreementTitle">
            <div class="mad-hero__identity">
                <div class="mad-hero__icon" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></div>
                <div><div class="mad-eyebrow">{{ $agreementTitle }}</div><h1 id="medicalAgreementTitle">اتفاقية #{{ $agreement->id }}</h1><p>{{ $patientName }} <span aria-hidden="true">•</span> رقم الملف {{ $agreement->patient_file_number ?: '—' }}</p></div>
            </div>
            <div class="mad-hero__actions">
                <span class="mad-status mad-status--{{ $statusKey }}"><i class="bi bi-circle-fill"></i>{{ $statusLabel }}</span>
                <a class="mad-btn mad-btn--light" href="{{ route('modules.medical-agreements.index', $variant) }}"><i class="bi bi-arrow-right"></i> العودة للقائمة</a>
                <a class="mad-btn mad-btn--primary" href="{{ route('modules.medical-agreements.pdf', [$variant, $agreement->id]) }}"><i class="bi bi-file-earmark-pdf"></i> تحميل PDF</a>
            </div>
        </section>

        <section class="mad-stat-grid" aria-label="ملخص الاتفاقية">
            <article class="mad-stat-card"><span class="mad-stat-card__icon"><i class="bi bi-hash"></i></span><div><small>رقم الاتفاقية</small><strong>{{ $agreement->id }}</strong></div></article>
            <article class="mad-stat-card"><span class="mad-stat-card__icon"><i class="bi bi-folder2-open"></i></span><div><small>رقم الملف الطبي</small><strong>{{ $agreement->patient_file_number ?: '—' }}</strong></div></article>
            <article class="mad-stat-card"><span class="mad-stat-card__icon"><i class="bi bi-shield-check"></i></span><div><small>الحالة الحالية</small><strong>{{ $statusLabel }}</strong></div></article>
            <article class="mad-stat-card"><span class="mad-stat-card__icon"><i class="bi bi-paperclip"></i></span><div><small>عدد المرفقات</small><strong>{{ $agreement->attachments->count() }}</strong></div></article>
        </section>

        <div class="mad-layout">
            <div class="mad-main-column">
                <section class="mad-panel" aria-labelledby="patientDataTitle">
                    <header class="mad-panel__header"><div class="mad-panel__heading"><span class="mad-panel__icon"><i class="bi bi-person-vcard"></i></span><div><h2 id="patientDataTitle">بيانات المريض</h2><p>المعلومات الأساسية المسجلة في الاتفاقية</p></div></div></header>
                    <div class="mad-detail-grid">
                        <div class="mad-detail-item mad-detail-item--wide"><span>اسم المريض</span><strong>{{ $patientName }}</strong></div>
                        <div class="mad-detail-item"><span>رقم الهوية</span><strong dir="ltr">{{ $agreement->patient_idno ?: '—' }}</strong></div>
                        <div class="mad-detail-item"><span>نوع الهوية</span><strong>{{ $agreement->pateintIDType ?: '—' }}</strong></div>
                        <div class="mad-detail-item"><span>الجنسية</span><strong>{{ $agreement->patient_nationality ?: '—' }}</strong></div>
                        <div class="mad-detail-item"><span>تاريخ الميلاد</span><strong>{{ $birthDate }}</strong></div>
                    </div>
                </section>

                <section class="mad-panel" aria-labelledby="contractorDataTitle">
                    <header class="mad-panel__header"><div class="mad-panel__heading"><span class="mad-panel__icon mad-panel__icon--teal"><i class="bi bi-person-check"></i></span><div><h2 id="contractorDataTitle">بيانات المتعهد</h2><p>بيانات الجهة أو الشخص المسؤول عن السداد</p></div></div></header>
                    <div class="mad-detail-grid">
                        <div class="mad-detail-item mad-detail-item--wide"><span>اسم المتعهد</span><strong>{{ $contractorName }}</strong></div>
                        <div class="mad-detail-item"><span>نوع العلاقة</span><strong>{{ $agreement->relative ?: '—' }}</strong></div>
                        <div class="mad-detail-item"><span>رقم الهوية</span><strong dir="ltr">{{ $agreement->contractor_idno ?: '—' }}</strong></div>
                        <div class="mad-detail-item"><span>رقم الجوال</span><strong dir="ltr">{{ $agreement->contractor_mobile ?: '—' }}</strong></div>
                        <div class="mad-detail-item"><span>البريد الإلكتروني</span><strong dir="ltr">{{ $agreement->email ?: '—' }}</strong></div>
                    </div>
                </section>

                @if ($isSadq)
                    <section class="mad-panel mad-signing-panel" aria-labelledby="signingTimelineTitle">
                        <header class="mad-panel__header">
                            <div class="mad-panel__heading"><span class="mad-panel__icon mad-panel__icon--purple"><i class="bi bi-pen"></i></span><div><h2 id="signingTimelineTitle">رحلة التوقيع الإلكتروني</h2><p>كل مراحل الدعوة والتوقيع المسجلة في منصة صادق</p></div></div>
                            @if ($latestTransaction)
                                <div class="mad-inline-actions">
                                    <form method="post" action="{{ route('modules.medical-agreements.sadq.status', [$variant, $agreement->id]) }}">@csrf<button class="mad-icon-action" type="submit" title="تحديث حالة التوقيع"><i class="bi bi-arrow-repeat"></i><span>تحديث الحالة</span></button></form>
                                    @if ($latestTransaction->signStatus === 'In-progress')
                                        <form method="post" action="{{ route('modules.medical-agreements.sadq.remind', [$variant, $agreement->id]) }}">@csrf<button class="mad-icon-action mad-icon-action--soft" type="submit" title="إعادة إرسال التذكير"><i class="bi bi-send"></i><span>إعادة التذكير</span></button></form>
                                    @endif
                                </div>
                            @endif
                        </header>
                        <div class="mad-timeline-list">
                            @forelse ($agreement->transactions as $tx)
                                @php
                                    $txKey = match ($tx->signStatus) { 'Completed' => 'completed', 'Rejected' => 'rejected', 'In-progress' => 'progress', default => 'failed' };
                                    $txLabel = match ($tx->signStatus) { 'Completed' => 'مكتمل', 'Rejected' => 'مرفوض', 'In-progress' => 'قيد التوقيع', default => 'تعذر الإرسال' };
                                    $txNote = $tx->RejectReason ?: $tx->error_message;
                                @endphp
                                <article class="mad-timeline-row mad-timeline-row--{{ $txKey }}"><span class="mad-timeline-marker"><i class="bi bi-{{ $txKey === 'completed' ? 'check-lg' : ($txKey === 'progress' ? 'send' : 'exclamation-lg') }}"></i></span><div class="mad-timeline-content"><div class="mad-timeline-content__top"><div><strong>{{ $txLabel }}</strong><small dir="ltr">{{ $tx->created_at }}</small></div><span class="mad-status mad-status--{{ $txKey }}">{{ $txLabel }}</span></div><div class="mad-timeline-meta"><span><i class="bi bi-phone"></i> {{ $tx->destination_mobile ?: '—' }}</span><span><i class="bi bi-file-earmark"></i> {{ $tx->document_id ?: '—' }}</span>@if ($txNote)<span class="mad-timeline-note"><i class="bi bi-info-circle"></i> {{ $txNote }}</span>@endif</div></div></article>
                            @empty
                                <div class="mad-empty"><i class="bi bi-hourglass-split"></i><strong>لم تبدأ معاملة التوقيع الإلكتروني</strong><span>ستظهر مراحل التوقيع هنا بعد إنشاء الدعوة.</span></div>
                            @endforelse
                        </div>
                    </section>
                @endif
            </div>

            <aside class="mad-side-column">
                <section class="mad-panel mad-attachments-panel" aria-labelledby="attachmentsTitle">
                    <header class="mad-panel__header"><div class="mad-panel__heading"><span class="mad-panel__icon mad-panel__icon--orange"><i class="bi bi-paperclip"></i></span><div><h2 id="attachmentsTitle">مرفقات الاتفاقية</h2><p>الملفات المرتبطة بهذا السجل</p></div></div><span class="mad-count-badge">{{ $agreement->attachments->count() }}</span></header>
                    <div class="mad-attachments-list">
                        @forelse ($agreement->attachments as $attachment)
                            <div class="mad-attachment-item"><span class="mad-attachment-item__icon"><i class="bi bi-file-earmark-text"></i></span><div class="mad-attachment-item__name"><a href="{{ route('modules.medical-agreements.attachments.download', [$variant, $agreement->id, $attachment->id]) }}">{{ basename($attachment->file_name) }}</a><small>ملف مرفق</small></div><form method="post" action="{{ route('modules.medical-agreements.attachments.destroy', [$variant, $agreement->id, $attachment->id]) }}">@csrf @method('DELETE')<button class="mad-delete-action" type="submit" title="حذف المرفق" aria-label="حذف المرفق"><i class="bi bi-trash3"></i></button></form></div>
                        @empty
                            <div class="mad-empty mad-empty--small"><i class="bi bi-folder2-open"></i><span>لا توجد مرفقات بعد</span></div>
                        @endforelse
                    </div>
                    <form class="mad-upload-form" method="post" enctype="multipart/form-data" action="{{ route('modules.medical-agreements.attachments.store', [$variant, $agreement->id]) }}">
                        @csrf
                        <label for="medicalAgreementAttachment"><i class="bi bi-cloud-arrow-up"></i><span>إضافة ملف جديد</span><small>PDF، صور أو مستندات حتى 10MB</small></label>
                        <input id="medicalAgreementAttachment" type="file" name="attachment" required>
                        <button class="mad-btn mad-btn--primary mad-btn--full" type="submit"><i class="bi bi-upload"></i> إرفاق الملف</button>
                    </form>
                </section>

                <section class="mad-panel mad-record-panel" aria-labelledby="recordInfoTitle">
                    <header class="mad-panel__header"><div class="mad-panel__heading"><span class="mad-panel__icon mad-panel__icon--blue"><i class="bi bi-info-circle"></i></span><div><h2 id="recordInfoTitle">معلومات السجل</h2><p>بيانات الإنشاء والتتبع</p></div></div></header>
                    <dl class="mad-record-list">
                        <div><dt>تاريخ الإنشاء</dt><dd dir="ltr">{{ $agreement->created_at ?: '—' }}</dd></div>
                        <div><dt>مدخل البيانات</dt><dd>{{ trim(($agreement->creator_name ?? '').' '.($agreement->creator_last_name ?? '')) ?: '—' }}</dd></div>
                        <div><dt>لغة الاتفاقية</dt><dd>{{ (int) $agreement->language === 1 ? 'العربية' : 'الإنجليزية' }}</dd></div>
                        <div><dt>الرقم المرجعي</dt><dd dir="ltr" class="mad-reference">{{ $agreement->reference_number ?: '—' }}</dd></div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
@endsection
