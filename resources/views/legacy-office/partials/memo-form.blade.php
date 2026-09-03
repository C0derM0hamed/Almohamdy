@php
    $v = fn ($field, $default = '') => old($field, $record?->{$field} ?? $default);
    $selectedRecipients ??= [];
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="memo-type-{{ $record?->id ?? 'new' }}">نوع المذكرة</label>
        <select required class="form-select" id="memo-type-{{ $record?->id ?? 'new' }}" name="memo_types_id">
            @foreach($types as $type)
                <option value="{{ $type->id }}" @selected((string) $v('memo_types_id') === (string) $type->id)>{{ $type->name_ar }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="memo-title-{{ $record?->id ?? 'new' }}-field">العنوان</label>
        <input class="form-control" id="memo-title-{{ $record?->id ?? 'new' }}-field" name="title" value="{{ $v('title') }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="memo-body-{{ $record?->id ?? 'new' }}">المذكرة</label>
        <textarea required rows="5" class="form-control" id="memo-body-{{ $record?->id ?? 'new' }}" name="memo">{{ $v('memo') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="memo-recipients-{{ $record?->id ?? 'new' }}">المستلمون</label>
        <select required multiple class="form-select" id="memo-recipients-{{ $record?->id ?? 'new' }}" name="recipients[]" size="6">
            @foreach($users as $user)
                <option value="{{ $user->hr_id }}" @selected(in_array((int) $user->hr_id, $selectedRecipients, true))>
                    {{ trim($user->hr_first_name.' '.$user->hr_last_name) }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">يمكن اختيار أكثر من مستلم باستخدام Ctrl أو Cmd.</small>
    </div>
    <div class="col-md-2"><label class="form-label">الدقائق</label><input type="number" min="0" class="form-control" name="minutes" value="{{ $v('minutes') }}"></div>
    <div class="col-md-2"><label class="form-label">الأيام</label><input type="number" min="0" class="form-control" name="days" value="{{ $v('days') }}"></div>
    <div class="col-md-2"><label class="form-label">الشهر</label><input type="month" class="form-control" name="month_year" value="{{ $v('month_year') }}"></div>
    <div class="col-md-3"><label class="form-label">الحضور</label><input class="form-control" name="check_in" value="{{ $v('check_in') }}"></div>
    <div class="col-md-3"><label class="form-label">الانصراف</label><input class="form-control" name="check_out" value="{{ $v('check_out') }}"></div>
    <div class="col-md-3"><label class="form-label">تاريخ الخروج</label><input type="date" class="form-control" name="exit_date" value="{{ $v('exit_date') }}"></div>
    <div class="col-md-3"><label class="form-label">وقت الخروج</label><input type="time" class="form-control" name="exit_time" value="{{ $v('exit_time') }}"></div>
    <div class="col-md-3"><label class="form-label">استفسارات مغلقة</label><input type="number" min="0" class="form-control" name="closed_inquiries" value="{{ $v('closed_inquiries') }}"></div>
    <div class="col-md-3"><label class="form-label">استفسارات معلقة</label><input type="number" min="0" class="form-control" name="pending_inquiries" value="{{ $v('pending_inquiries') }}"></div>
    <div class="col-md-3"><label class="form-label">من</label><input type="date" class="form-control" name="begin_date" value="{{ $v('begin_date') }}"></div>
    <div class="col-md-3"><label class="form-label">إلى</label><input type="date" class="form-control" name="end_date" value="{{ $v('end_date') }}"></div>
    <input type="hidden" name="current_begin_time" value="{{ $v('current_begin_time') }}">
    <input type="hidden" name="current_end_time" value="{{ $v('current_end_time') }}">
    <input type="hidden" name="new_begin_time" value="{{ $v('new_begin_time') }}">
    <input type="hidden" name="new_end_time" value="{{ $v('new_end_time') }}">
    <input type="hidden" name="hours" value="{{ $v('hours') }}">
</div>
