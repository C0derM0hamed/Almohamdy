@extends('layouts.app')
@section('title', 'اتفاقيات تقديم الخدمات الطبية')
@section('content')
@php($titles=['standard'=>'اتفاقية تقديم خدمات طبية','sadq'=>'اتفاقية تقديم خدمات طبية — صادق','sadq-manual'=>'اتفاقية تقديم خدمات طبية — صادق (إدخال يدوي)'])
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><h1 class="h4 mb-1">{{ $titles[$variant] }}</h1><p class="text-muted mb-0">السجلات المطابقة لنطاق الفرع الحالي</p></div><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMedicalAgreementModal" data-medical-agreement-open><i class="bi bi-plus-lg"></i> اتفاقية جديدة</button></div>
<form class="card border-0 shadow-sm mb-3" method="get"><div class="card-body row g-3 align-items-end">
<div class="col-md-2"><label class="form-label">من تاريخ</label><input class="form-control" type="date" name="from" value="{{ $filters['from'] }}"></div><div class="col-md-2"><label class="form-label">إلى تاريخ</label><input class="form-control" type="date" name="to" value="{{ $filters['to'] }}"></div>
<div class="col-md-2"><label class="form-label">الحالة</label><select class="form-select" name="status"><option value="">الكل</option>@if($variant==='standard')<option value="pending" @selected($filters['status']==='pending')>معلق</option><option value="authenticated" @selected($filters['status']==='authenticated')>تمت المصادقة</option>@else<option value="In-progress" @selected($filters['status']==='In-progress')>قيد التوقيع</option><option value="Completed" @selected($filters['status']==='Completed')>مكتمل</option><option value="Rejected" @selected($filters['status']==='Rejected')>مرفوض</option>@endif</select></div>
<div class="col-md-2"><label class="form-label">المدخل</label><select class="form-select" name="creator"><option value="">الكل</option>@foreach($creators as $creator)<option value="{{ $creator->hr_id }}" @selected($filters['creator']==(string)$creator->hr_id)>{{ $creator->hr_first_name }} {{ $creator->hr_last_name }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">اللغة</label><select class="form-select" name="language"><option value="">الكل</option><option value="1" @selected($filters['language']==='1')>العربية</option><option value="2" @selected($filters['language']==='2')>English</option></select></div>
<div class="col-md-2"><label class="form-label">رقم الهوية</label><input class="form-control" name="id_number" value="{{ $filters['id_number'] }}"></div><div class="col-12 d-flex gap-2 justify-content-end"><a class="btn btn-outline-secondary" href="{{ route('modules.medical-agreements.index',$variant) }}">استعادة</a><button class="btn btn-primary"><i class="bi bi-search"></i> بحث</button></div>
</div></form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>التاريخ</th><th>اسم المريض</th><th>رقم الهوية</th><th>رقم الملف الطبي</th><th>مدخل البيانات</th><th>رقم هوية المتعهد</th><th>المستند</th><th>الخط الزمني</th></tr></thead><tbody>
@forelse($agreements as $agreement)<tr><td dir="ltr">{{ $agreement->created_at }}</td><td>{{ $agreement->patient_name_ar ?: $agreement->patient_name_en }}</td><td>{{ $agreement->patient_idno }}</td><td>{{ $agreement->patient_file_number }}</td><td>{{ $agreement->creator_name }}</td><td>{{ $agreement->contractor_idno }}</td><td><a class="btn btn-sm btn-outline-danger" href="{{ route('modules.medical-agreements.pdf',[$variant,$agreement->id]) }}"><i class="bi bi-file-earmark-pdf"></i> PDF</a></td><td><button type="button" class="btn btn-sm btn-outline-primary hm-agreement-timeline-trigger" data-medical-agreement-timeline data-timeline-url="{{ route('modules.medical-agreements.timeline',[$variant,$agreement->id]) }}" aria-label="عرض الخط الزمني للاتفاقية #{{ $agreement->id }}" title="عرض الخط الزمني"><i class="bi bi-clock-history" aria-hidden="true"></i></button></td></tr>@empty<tr><td colspan="8" class="text-center text-muted py-5">لا توجد سجلات</td></tr>@endforelse
</tbody></table></div><div class="p-3">{{ $agreements->links() }}</div></div>
<div class="modal fade hm-medical-agreement-modal" id="newMedicalAgreementModal" tabindex="-1" aria-hidden="true" data-medical-agreement-modal>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إنشاء {{ $titles[$variant] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">@include('legacy-workflows.medical-agreements._form', ['modal' => true])</div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('newMedicalAgreementModal');
        if (!modal) return;

        // The legacy client keeps this form in a real popup. Move it to the
        // document body before opening so page wrappers, transforms, and the
        // sidebar cannot clip or hide the modal.
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        var fallbackBackdrop = null;

        function closeModalFallback() {
            modal.classList.remove('show', 'hm-medical-agreement-modal--fallback');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            if (fallbackBackdrop) {
                fallbackBackdrop.remove();
                fallbackBackdrop = null;
            }
        }

        function showModalFallback() {
            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
                return;
            }

            if (!fallbackBackdrop) {
                fallbackBackdrop = document.createElement('div');
                fallbackBackdrop.className = 'modal-backdrop fade show hm-medical-agreement-backdrop';
                fallbackBackdrop.addEventListener('click', closeModalFallback);
                document.body.appendChild(fallbackBackdrop);
            }

            modal.classList.add('show', 'hm-medical-agreement-modal--fallback');
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-modal', 'true');
            modal.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
        }

        document.querySelectorAll('[data-medical-agreement-open]').forEach(function (button) {
            // Capture the click so the popup still works if Bootstrap's CDN
            // is blocked or another page script throws before its data API.
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                showModalFallback();
            }, true);
        });

        modal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (window.bootstrap && window.bootstrap.Modal) return;
                closeModalFallback();
            });
        });

        modal.addEventListener('hidden.bs.modal', function () {
            modal.setAttribute('aria-hidden', 'true');
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal && !(window.bootstrap && window.bootstrap.Modal)) {
                closeModalFallback();
            }
        });

        window.openNewMedicalAgreementModal = showModalFallback;
    });
</script>
@endpush

@push('modals')
<div class="hm-agreement-timeline-modal" id="medicalAgreementTimelineModal" data-medical-agreement-timeline-modal hidden>
    <div class="hm-agreement-timeline-dialog" role="dialog" aria-modal="true" aria-labelledby="medicalAgreementTimelineTitle" aria-describedby="medicalAgreementTimelineSubtitle">
        <header class="hm-agreement-timeline-header">
            <div class="hm-agreement-timeline-heading">
                <span class="hm-agreement-timeline-heading-icon" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <p class="hm-agreement-timeline-eyebrow">الخط الزمني للاتفاقية</p>
                    <h2 id="medicalAgreementTimelineTitle" data-timeline-title>تفاصيل الاتفاقية</h2>
                    <p id="medicalAgreementTimelineSubtitle" data-timeline-subtitle>جارٍ تحميل مراحل الاتفاقية...</p>
                </div>
            </div>
            <button type="button" class="hm-agreement-timeline-close" data-timeline-close aria-label="إغلاق الخط الزمني"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </header>
        <div class="hm-agreement-timeline-body">
            <div class="hm-agreement-timeline-summary" data-timeline-summary>
                <div class="hm-agreement-timeline-summary-card"><span>رقم الاتفاقية</span><strong data-timeline-id>—</strong></div>
                <div class="hm-agreement-timeline-summary-card"><span>اسم المريض</span><strong data-timeline-patient>—</strong></div>
                <div class="hm-agreement-timeline-summary-card"><span>رقم الملف</span><strong data-timeline-file>—</strong></div>
                <div class="hm-agreement-timeline-summary-card"><span>الحالة الحالية</span><strong data-timeline-status>—</strong></div>
            </div>
            <div class="hm-agreement-timeline-section-head">
                <div><h3>مراحل الاتفاقية</h3><p>تسلسل الإجراءات والتحديثات المسجلة على الاتفاقية</p></div>
                <span class="hm-agreement-timeline-count" data-timeline-count>0 مرحلة</span>
            </div>
            <div class="hm-agreement-timeline-state" data-timeline-state="loading" role="status">
                <span class="hm-agreement-timeline-spinner" aria-hidden="true"></span><span data-timeline-state-text>جارٍ تحميل الخط الزمني...</span>
            </div>
            <div class="hm-agreement-timeline-scroll" data-timeline-scroll hidden>
                <ol class="hm-agreement-timeline-events" data-timeline-events></ol>
            </div>
        </div>
        <footer class="hm-agreement-timeline-footer">
            <button type="button" class="btn btn-light" data-timeline-close>إغلاق</button>
            <div class="hm-agreement-timeline-footer-actions">
                <a class="btn btn-outline-primary" data-timeline-detail href="#"><i class="bi bi-box-arrow-up-left" aria-hidden="true"></i> تفاصيل الاتفاقية</a>
                <a class="btn btn-primary" data-timeline-pdf href="#"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
            </div>
        </footer>
    </div>
</div>
@endpush

@push('workflow_styles')
<link rel="stylesheet" href="{{ asset('css/hm-medical-agreement-timeline.css') }}?v={{ filemtime(public_path('css/hm-medical-agreement-timeline.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('js/hm-medical-agreement-timeline.js') }}?v={{ filemtime(public_path('js/hm-medical-agreement-timeline.js')) }}" defer></script>
@endpush

@push('styles')
<style>
    .hm-medical-agreement-modal {
        z-index: 1060;
    }

    .hm-medical-agreement-modal .modal-dialog {
        max-width: min(920px, calc(100vw - 2rem));
    }

    .hm-medical-agreement-modal--fallback {
        overflow-x: hidden;
        overflow-y: auto;
    }

    .hm-medical-agreement-backdrop {
        z-index: 1055;
    }
</style>
@endpush
@if($errors->any() || session('open_new_medical_agreement'))
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('newMedicalAgreementModal');
            if (!modal) return;

            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
                return;
            }

            // Keep the old modal behavior even if the optional Bootstrap CDN
            // is temporarily unavailable. The form remains usable in place.
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-modal', 'true');
            modal.removeAttribute('aria-hidden');
            document.body.classList.add('modal-open');
        });
    </script>
    @endpush
@endif
@endsection
