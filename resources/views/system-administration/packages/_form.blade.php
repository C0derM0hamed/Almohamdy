@php
    $isCreate = $isCreate ?? $package === null;
    $publishChecked = old('publish', $isCreate || $package?->publish === '1');
@endphp

<form method="POST" enctype="multipart/form-data" action="{{ $isCreate ? route('modules.system-admin.packages.store') : route('modules.system-admin.packages.update', $package->id) }}" class="dda-form">
    @csrf
    @unless($isCreate)
        @method('PUT')
    @endunless

    @if ($errors->any())
        <div class="dda-form-alert" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>{{ __('system_administration.form_has_errors') }}</span></div>
    @endif

    <div class="dda-form-section">
        <header class="dda-form-section__head"><span class="dda-form-section__icon" aria-hidden="true"><i class="bi bi-hospital"></i></span><div><h2>بيانات الخدمة</h2><p>الحقول الأساسية المستخدمة في شاشة الخدمات وباقات التنويم.</p></div></header>
        <div class="dda-form-grid dda-form-grid--2">
            <div class="dda-form-field dda-form-field--full"><label for="service_id">القسم</label><select id="service_id" name="service_id" class="dda-form-control" required><option value="">اختر القسم</option>@foreach($sectionOptions as $sectionId => $label)<option value="{{ $sectionId }}" @selected((string) old('service_id', $package?->service_id) === (string) $sectionId)>{{ $label }}</option>@endforeach</select>@error('service_id')<div class="dda-form-error">{{ $message }}</div>@enderror</div>
            <div class="dda-form-field"><label for="code1">رمز الخدمة</label><input type="text" id="code1" name="code1" value="{{ old('code1', $package?->code1) }}" class="dda-form-control{{ $errors->has('code1') ? ' is-invalid' : '' }}" maxlength="100" required>@error('code1')<div class="dda-form-error">{{ $message }}</div>@enderror</div>
            <div class="dda-form-field"><label for="price">السعر</label><input type="text" id="price" name="price" value="{{ old('price', $package?->price) }}" class="dda-form-control" maxlength="100"></div>
            <div class="dda-form-field"><label for="name_ar">الاسم عربي</label><input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar', $package?->name_ar) }}" class="dda-form-control" maxlength="500" required></div>
            <div class="dda-form-field"><label for="name_en">الاسم English</label><input type="text" id="name_en" name="name_en" value="{{ old('name_en', $package?->name_en) }}" class="dda-form-control" maxlength="500"></div>
            <div class="dda-form-field"><label for="notice_ar">ملاحظة عربي</label><textarea id="notice_ar" name="notice_ar" class="dda-form-control" rows="3">{{ old('notice_ar', $package?->notice_ar) }}</textarea></div>
            <div class="dda-form-field"><label for="notice_en">ملاحظة English</label><textarea id="notice_en" name="notice_en" class="dda-form-control" rows="3">{{ old('notice_en', $package?->notice_en) }}</textarea></div>
        </div>
    </div>

    <div class="dda-form-section">
        <header class="dda-form-section__head"><span class="dda-form-section__icon dda-form-section__icon--green" aria-hidden="true"><i class="bi bi-card-text"></i></span><div><h2>التفاصيل والخصومات</h2><p>نفس أعمدة الملاحظات والخصومات التي كان يستقبلها الاستيراد القديم.</p></div></header>
        <div class="dda-form-grid dda-form-grid--2">
            <div class="dda-form-field"><label for="notice1_ar">زمن النتيجة عربي</label><textarea id="notice1_ar" name="notice1_ar" class="dda-form-control" rows="2">{{ old('notice1_ar', $package?->notice1_ar) }}</textarea></div>
            <div class="dda-form-field"><label for="notice1_en">زمن النتيجة English</label><textarea id="notice1_en" name="notice1_en" class="dda-form-control" rows="2">{{ old('notice1_en', $package?->notice1_en) }}</textarea></div>
            <div class="dda-form-field dda-form-field--full"><label for="service_details">تفاصيل الخدمة</label><textarea id="service_details" name="service_details" class="dda-form-control" rows="4">{{ old('service_details', $package?->service_details) }}</textarea></div>
            @foreach(['consultation_discount' => 'خصم الاستشارة', 'lab_x_rays_discount' => 'خصم المختبر والأشعة', 'operations_hypnosis_discount' => 'خصم العمليات والتخدير', 'delivery_discount' => 'خصم الولادة'] as $field => $label)
                <div class="dda-form-field"><label for="{{ $field }}">{{ $label }}</label><input type="text" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $package?->{$field}) }}" class="dda-form-control" maxlength="100"></div>
            @endforeach
        </div>
    </div>

    <div class="dda-form-section">
        <header class="dda-form-section__head"><span class="dda-form-section__icon dda-form-section__icon--green" aria-hidden="true"><i class="bi bi-paperclip"></i></span><div><h2>المرفقات</h2><p>يمكن رفع صور أو مستندات مرتبطة بالخدمة.</p></div></header>
        <input class="dda-form-control" type="file" name="attachment_files[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx">
        @if(!$isCreate && $package)
            <div class="list-group mt-3">@forelse($package->attachments as $attachment)<div class="list-group-item d-flex justify-content-between align-items-center"><a href="{{ route('modules.system-admin.packages.attachments.download', [$package->id, $attachment->id]) }}">{{ basename($attachment->file_name) }}</a><form method="post" action="{{ route('modules.system-admin.packages.attachments.destroy', [$package->id, $attachment->id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">حذف</button></form></div>@empty<span class="text-muted mt-2">لا توجد مرفقات.</span>@endforelse</div>
        @endif
    </div>

    <div class="dda-form-section"><label class="dda-form-switch" for="publish"><input type="checkbox" id="publish" name="publish" value="1" class="dda-form-switch__input" @checked($publishChecked)><span class="dda-form-switch__track" aria-hidden="true"></span><span class="dda-form-switch__copy"><strong>منشورة</strong><span>تظهر الخدمة في دليل الخدمات عندما تكون مفعلة.</span></span></label></div>

    @if(!$isCreate && $package)
        <div class="dda-form-meta">
            <div class="dda-form-meta__item"><small>{{ __('system_administration.fields.id') }}</small><strong>#{{ $package->id }}</strong></div>
            @if($package->created_at)
                <div class="dda-form-meta__item"><small>{{ __('system_administration.fields.created_at') }}</small><strong>{{ $package->created_at }}</strong></div>
            @endif
            @if($package->updated_at)
                <div class="dda-form-meta__item"><small>{{ __('system_administration.fields.updated_at') }}</small><strong>{{ $package->updated_at }}</strong></div>
            @endif
        </div>
    @endif
    <div class="dda-form-actions"><button type="submit" class="btn hm-btn hm-btn--primary dda-btn"><i class="bi bi-check-lg" aria-hidden="true"></i> حفظ</button><a href="{{ route('modules.system-admin.packages.index') }}" class="btn hm-btn hm-btn--outline dda-btn">إلغاء</a></div>
</form>

@if(!$isCreate && $package)
    <form method="POST" action="{{ route('modules.system-admin.packages.destroy', $package->id) }}" class="mt-3" onsubmit="return confirm(@json(__('system_administration.confirm_delete')));">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn hm-btn hm-btn--outline dda-btn text-danger"><i class="bi bi-trash" aria-hidden="true"></i> {{ __('system_administration.delete') }}</button>
    </form>
@endif
