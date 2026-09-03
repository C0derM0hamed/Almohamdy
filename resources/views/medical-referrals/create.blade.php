@extends('layouts.app')
@section('title', $definition['title'])
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h4 mb-0">إضافة: {{ $definition['title'] }}</h1><a class="btn btn-outline-secondary" href="{{ route('modules.medical-referrals.index', $type) }}">رجوع</a></div>
<div class="card border-0 shadow-sm"><form method="post" action="{{ route('modules.medical-referrals.store', $type) }}">@csrf<div class="card-body row g-3">
@if(in_array($type, ['bed-reservation','accept-referral','referral-apology'], true))
<div class="col-md-6"><label class="form-label">اسم المريض</label><input class="form-control" name="patient_name" value="{{ old('patient_name') }}" required></div>
@endif
@if(in_array($type, ['accept-referral','referral-apology'], true))
<div class="col-md-6"><label class="form-label">{{ app()->getLocale() === 'ar' ? 'الجنسية' : 'Nationality' }}</label><select class="form-select" name="nationality" required><option value="">{{ app()->getLocale() === 'ar' ? 'اختر...' : 'Choose...' }}</option>@foreach($countries as $option)<option value="{{ $option->id }}" @selected(old('nationality') == $option->id)>{{ \App\Support\LocaleText::localizedValue($option->country_nationality_ar ?? null, $option->country_nationality_en ?? null) }}</option>@endforeach</select></div>
@endif
@if(in_array($type, ['bed-reservation','accept-referral','referral-apology'], true))
<div class="col-md-6"><label class="form-label">رقم الهوية</label><input class="form-control" inputmode="numeric" minlength="10" maxlength="10" name="idno" value="{{ old('idno') }}" required></div>
@endif
@if($type === 'bed-reservation')
<div class="col-md-3"><label class="form-label">العمر</label><input class="form-control" name="age" maxlength="4" value="{{ old('age') }}" required></div>
<div class="col-md-3"><label class="form-label">الجنس</label><select class="form-select" name="gender" required><option value="1">ذكر</option><option value="2">أنثى</option></select></div>
<div class="col-md-6"><label class="form-label">الطبيب</label><input class="form-control" name="doctor" value="{{ old('doctor') }}" required></div>
<div class="col-md-6"><label class="form-label">جهة الخطاب</label><input class="form-control" name="letter_side" value="{{ old('letter_side') }}" required></div>
<div class="col-md-6"><label class="form-label">لغة النموذج</label><select class="form-select" name="lang"><option value="ar" @selected(($legacyLanguage ?? 'ar') === 'ar')>عربي</option><option value="en" @selected(($legacyLanguage ?? 'ar') === 'en')>English</option></select></div>
@endif
@if($type === 'accept-referral')
<div class="col-md-6"><label class="form-label">رقم التواصل</label><input class="form-control" name="contact_number" maxlength="14" value="{{ old('contact_number') }}" required></div>
<div class="col-md-6"><label class="form-label">رقم الإحالة</label><input class="form-control" name="ehala_number" maxlength="14" value="{{ old('ehala_number') }}" required></div>
<div class="col-md-6"><label class="form-label">الطبيب المعالج</label><input class="form-control" name="doctor" value="{{ old('doctor') }}" required></div>
@endif
@if($type === 'referral-apology')<div class="col-md-6"><label class="form-label">رقم الإحالة</label><input class="form-control" name="ehala_number" maxlength="14" value="{{ old('ehala_number') }}" required></div>@endif
@if($type === 'crisis-management')<div class="col-md-6"><label class="form-label">رقم التواصل</label><input class="form-control" name="contact_number" maxlength="14" value="{{ old('contact_number') }}" required></div>@endif
@if(in_array($type, ['bed-reservation','accept-referral','crisis-management','red-crescent'], true))
<div class="col-md-6"><label class="form-label">{{ app()->getLocale() === 'ar' ? (in_array($type, ['crisis-management','red-crescent'], true) ? 'الوحدة' : 'القسم') : (in_array($type, ['crisis-management','red-crescent'], true) ? 'Unit' : 'Department') }}</label><select class="form-select" name="room_type" required><option value="">{{ app()->getLocale() === 'ar' ? 'اختر...' : 'Choose...' }}</option>@foreach($rooms as $option)<option value="{{ $option->id }}">{{ \App\Support\LocaleText::localizedValue($option->name_ar ?? null, $option->name_en ?? null) }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">{{ app()->getLocale() === 'ar' ? ($type === 'bed-reservation' ? 'مدة الحجز' : 'المدة المتوقعة') : ($type === 'bed-reservation' ? 'Booking period' : 'Expected duration') }}</label><select class="form-select" name="booking_period" required><option value="">{{ app()->getLocale() === 'ar' ? 'اختر...' : 'Choose...' }}</option>@foreach($periods as $option)<option value="{{ $option->id }}">{{ \App\Support\LocaleText::localizedValue($option->name_ar ?? null, $option->name_en ?? null) }}</option>@endforeach</select></div>
@endif
@if(in_array($type, ['referral-apology','crisis-management','red-crescent'], true))
<div class="col-md-6"><label class="form-label">{{ app()->getLocale() === 'ar' ? 'سبب الاعتذار' : 'Apology reason' }}</label><select class="form-select" name="apology" required><option value="">{{ app()->getLocale() === 'ar' ? 'اختر...' : 'Choose...' }}</option>@foreach($apologies as $option)<option value="{{ $option->id }}">{{ \App\Support\LocaleText::localizedValue($option->name_ar ?? null, $option->name_en ?? null) }}</option>@endforeach</select></div>
@endif
@if($type === 'pulse-status')
<div class="col-md-6"><label class="form-label">الاسم</label><input class="form-control" name="name" required></div><div class="col-md-6"><label class="form-label">رقم البلاغ</label><input class="form-control" name="Report_number" required></div>
<div class="col-md-6"><label class="form-label">رقم الهوية</label><input class="form-control" name="no" minlength="10" maxlength="10" required></div><div class="col-md-6"><label class="form-label">اسم الطبيب</label><select class="form-select" name="doctor" required><option value="">اختر...</option>@foreach($doctors as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">تاريخ استلام الحالة</label><input class="form-control" type="datetime-local" name="date_dlivry" required></div><div class="col-md-6"><label class="form-label">تاريخ الإشعار</label><input class="form-control" type="datetime-local" name="Notification_date" required></div>
@endif
@if($errors->any())<div class="col-12"><div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
</div><div class="card-footer bg-transparent"><button class="btn btn-dark" type="submit">حفظ</button></div></form></div>
@endsection
