@extends('layouts.app')
@section('title', $definition['title'])
@section('content')
@php
    $usesRoom = in_array($type, ['bed-reservation', 'accept-referral', 'crisis-management', 'red-crescent'], true);
    $usesApology = in_array($type, ['referral-apology', 'crisis-management', 'red-crescent'], true);
    $usesIdentity = in_array($type, ['bed-reservation', 'accept-referral', 'referral-apology'], true);
    $usesUser = in_array($type, ['bed-reservation', 'accept-referral'], true);
    $roomNames = $rooms->pluck('name_ar', 'id');
    $periodNames = $periods->pluck('name_ar', 'id');
    $apologyNames = $apologies->pluck('name_ar', 'id');
    $countryNames = $countries->pluck('country_nationality_ar', 'id');
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h1 class="h4 mb-0">{{ $definition['title'] }}</h1>
    <a class="btn btn-primary" href="{{ route('modules.medical-referrals.create', $type) }}"><i class="bi bi-plus-lg"></i> إضافة جديد</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form class="card border-0 shadow-sm mb-4" method="get">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-2"><label class="form-label">من تاريخ</label><input class="form-control" type="date" name="from" value="{{ $filters['from'] }}"></div>
        <div class="col-md-2"><label class="form-label">إلى تاريخ</label><input class="form-control" type="date" name="to" value="{{ $filters['to'] }}"></div>
        @if($usesRoom)<div class="col-md-2"><label class="form-label">{{ in_array($type, ['crisis-management','red-crescent'], true) ? 'الوحدة' : 'القسم' }}</label><select class="form-select" name="room_type"><option value="">الكل</option>@foreach($rooms as $option)<option value="{{ $option->id }}" @selected($filters['room_type'] === (int) $option->id)>{{ $option->name_ar }}</option>@endforeach</select></div>@endif
        @if($usesApology)<div class="col-md-2"><label class="form-label">سبب الاعتذار</label><select class="form-select" name="apology"><option value="">الكل</option>@foreach($apologies as $option)<option value="{{ $option->id }}" @selected($filters['apology'] === (int) $option->id)>{{ $option->name_ar }}</option>@endforeach</select></div>@endif
        @if($usesUser)<div class="col-md-2"><label class="form-label">المدخل</label><select class="form-select" name="user_id"><option value="">الكل</option>@foreach($users as $option)<option value="{{ $option->hr_id }}" @selected($filters['user_id'] === (int) $option->hr_id)>{{ $option->hr_first_name }}</option>@endforeach</select></div>@endif
        @if($usesIdentity)<div class="col-md-2"><label class="form-label">رقم الهوية</label><input class="form-control" name="identity" value="{{ $filters['identity'] }}"></div>@endif
        <div class="col-auto"><button class="btn btn-dark" type="submit"><i class="bi bi-search"></i> بحث</button> <a class="btn btn-outline-secondary" href="{{ route('modules.medical-referrals.index', $type) }}">استعادة</a></div>
    </div>
</form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr>
    @if($type === 'pulse-status')<th>الرقم</th><th>التاريخ</th><th>الاسم</th><th>رقم الهوية</th><th>حالة الطلب</th>
    @elseif($type === 'bed-reservation')<th>تاريخ الإصدار</th><th>اسم المريض</th><th>رقم الهوية</th><th>القسم</th><th>الطبيب</th><th>مصدر الإشعار</th>
    @elseif($type === 'accept-referral')<th>تاريخ الإصدار</th><th>اسم المريض</th><th>رقم الهوية</th><th>رقم الإحالة</th><th>القسم</th><th>الطبيب المعالج</th><th>مصدر الإشعار</th>
    @elseif($type === 'referral-apology')<th>التاريخ</th><th>اسم المريض</th><th>الجنسية</th><th>رقم الهوية</th><th>رقم الإحالة</th><th>سبب الاعتذار</th><th>مصدر الإشعار</th>
    @else<th>تاريخ الإصدار</th><th>الوحدة</th><th>المدة المتوقعة</th><th>سبب الاعتذار</th><th>مصدر الإشعار</th>@endif
    <th>الإجراء</th>
</tr></thead><tbody>
@forelse($records as $item)<tr>
    @if($type === 'pulse-status')
        <td>{{ $item->id }}</td><td dir="ltr">{{ $item->create_at }}</td><td>{{ $item->name }}</td><td>{{ $item->no }}</td><td><span class="badge {{ (int)$item->status === 1 ? 'text-bg-success' : ((int)$item->status === 2 ? 'text-bg-danger' : 'text-bg-warning') }}">{{ (int)$item->status === 1 ? 'تمت الموافقة' : ((int)$item->status === 2 ? 'غير موافق' : 'تحت الإجراء') }}</span></td>
    @elseif($type === 'bed-reservation')
        <td dir="ltr">{{ date('Y-m-d g:i A', (int)$item->date) }}</td><td>{{ $item->patient_name }}</td><td>{{ $item->idno }}</td><td>{{ $roomNames[(int)$item->room_type] ?? $item->room_type }}</td><td>{{ $item->doctor }}</td><td>{{ $item->creator_name }}</td>
    @elseif($type === 'accept-referral')
        <td dir="ltr">{{ date('Y-m-d g:i A', (int)$item->date) }}</td><td>{{ $item->patient_name }}</td><td>{{ $item->idno }}</td><td>{{ $item->ehala_number }}</td><td>{{ $roomNames[(int)$item->room_type] ?? $item->room_type }}</td><td>{{ $item->doctor }}</td><td>{{ $item->creator_name }}</td>
    @elseif($type === 'referral-apology')
        <td dir="ltr">{{ date('Y-m-d H:i', (int)$item->date) }}</td><td>{{ $item->patient_name }}</td><td>{{ $countryNames[(int)$item->nationality] ?? $item->nationality }}</td><td>{{ $item->idno }}</td><td>{{ $item->ehala_number }}</td><td>{{ $apologyNames[(int)$item->apology] ?? $item->apology }}</td><td>{{ $item->creator_name }}</td>
    @else
        <td dir="ltr">{{ date('Y-m-d g:i A', (int)$item->date) }}</td><td>{{ $roomNames[(int)$item->room_type] ?? $item->room_type }}</td><td>{{ $periodNames[(int)$item->booking_period] ?? $item->booking_period }}</td><td>{{ $apologyNames[(int)$item->apology] ?? $item->apology }}</td><td>{{ $item->creator_name }}</td>
    @endif
    <td class="text-nowrap"><a class="btn btn-sm btn-outline-danger" target="_blank" href="{{ route('modules.medical-referrals.pdf', [$type, $item->id]) }}" aria-label="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
    @if($type === 'pulse-status')<form class="d-inline" method="post" action="{{ route('modules.medical-referrals.email', [$type, $item->id]) }}">@csrf<button class="btn btn-sm btn-outline-primary" type="submit" title="إرسال إلى الهلال الأحمر"><i class="bi bi-send"></i></button></form>
    @else<button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#emailRecord{{ $item->id }}" title="إرسال بريد"><i class="bi bi-envelope"></i></button>@endif
    @if((int)$item->user_id === (int)session('hr_user_id'))<form class="d-inline" method="post" action="{{ route('modules.medical-referrals.destroy', [$type, $item->id]) }}" onsubmit="return confirm('هل أنت متأكد؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" aria-label="حذف"><i class="bi bi-trash"></i></button></form>@endif</td>
</tr>@empty<tr><td class="text-center text-muted py-5" colspan="9">لا توجد سجلات</td></tr>@endforelse
</tbody></table></div><div class="p-3">{{ $records->links() }}</div></div>
@if($type !== 'pulse-status')@foreach($records as $item)<div class="modal fade" id="emailRecord{{ $item->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('modules.medical-referrals.email', [$type, $item->id]) }}">@csrf<div class="modal-header"><h2 class="modal-title fs-5">إرسال بريد بالنموذج</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">مرسل إلى</label><input class="form-control mb-3" type="email" name="mail_to" required><label class="form-label">نسخة إلى</label><input class="form-control" type="email" name="mail_cc"></div><div class="modal-footer"><button class="btn btn-primary" type="submit">إرسال</button></div></form></div></div>@endforeach@endif
@endsection
