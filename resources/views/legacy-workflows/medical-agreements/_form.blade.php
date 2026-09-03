@php
    $isSadq = $variant === 'sadq';
    $isManual = $variant === 'sadq-manual';
    $isApi = in_array($variant, ['standard', 'sadq'], true);
    $formId = 'medical-agreement-form-'.str_replace('-', '_', $variant).($modal ? '-modal' : '');
    $saudiCode = (int) config('services.yakeen.saudi_nationality_code', 113);
@endphp

<form id="{{ $formId }}" class="hm-medical-agreement-form" method="post" action="{{ route('modules.medical-agreements.store', $variant) }}"
      data-medical-agreement-form data-yakeen="{{ $isApi ? 'true' : 'false' }}">
    @csrf

    @if($isApi)
        <div class="alert alert-info d-flex gap-2 align-items-start small mb-3">
            <i class="bi bi-shield-check fs-5"></i>
            <span>اختر اللغة أولاً، ثم أكمل الحقول التي تظهر بالتتابع. مسار صادق يستخدم يقين لجلب بيانات الهوية؛ الهوية الوطنية بتاريخ هجري وباقي الهويات بتاريخ ميلادي.</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- The old screen starts with language radios and loads the rest below them. --}}
    <section class="agreement-step" data-language-step>
        <label class="form-label fw-semibold d-block">اللغة</label>
        <div class="d-flex flex-wrap gap-4 border rounded p-3 bg-light">
            <label class="form-check d-flex align-items-center gap-2 mb-0">
                <input class="form-check-input m-0" type="radio" name="language" value="1"
                       @checked(old('language') == 1) required data-language-choice>
                <span>عربي</span>
            </label>
            <label class="form-check d-flex align-items-center gap-2 mb-0">
                <input class="form-check-input m-0" type="radio" name="language" value="2"
                       @checked(old('language') == 2) data-language-choice>
                <span dir="ltr">English</span>
            </label>
        </div>
    </section>

    <div class="d-none" data-agreement-flow>
        <div class="border-top mt-4 pt-4" data-step="contractor-type">
            <h2 class="h6 mb-3">بيانات الاتفاقية</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نوع المتعهد</label>
                    <select class="form-select" name="contractor_type" data-contractor-type data-required="true">
                        <option value="">اختر...</option>
                        <option value="1" @selected(old('contractor_type') == 1)>بالنيابة عن غيره</option>
                        <option value="2" @selected(old('contractor_type') == 2)>أصالة عن نفسه</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="border-top mt-4 pt-4 d-none" data-step="patient-identity">
            <h2 class="h6 mb-3">بيانات المريض</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نوع هوية المريض</label>
                    <select class="form-select" name="patient_id_type" data-patient-id-type data-required="true">
                        <option value="">اختر نوع الهوية</option>
                        @foreach($idTypes as $item)
                            <option value="{{ $item->id }}" @selected(old('patient_id_type') == $item->id)>{{ $item->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="border-top mt-4 pt-4 d-none" data-step="patient-identity-fields">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" data-patient-id-label>رقم الهوية</label>
                    @include('legacy-workflows.medical-agreements._digit-input', [
                        'name' => 'patient_idno',
                        'dataAttribute' => 'data-patient-id',
                        'label' => 'رقم هوية المريض',
                        'maxLength' => 12,
                    ])
                </div>
                <div class="col-md-6" data-patient-nationality-field>
                    <label class="form-label">جنسية المريض</label>
                    <select class="form-select" name="patient_nationality" data-patient-nationality data-required="true">
                        <option value="">اختر الجنسية</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->CODE }}" @selected(old('patient_nationality', $saudiCode) == $country->CODE)>{{ $country->DESCRIPTION }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12" data-patient-date-fields>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" data-patient-year-label>سنة الميلاد</label>
                            <select class="form-select" name="birth_year" data-patient-birth-year data-year-select data-initial-value="{{ old('birth_year') }}" data-required="true">
                                <option value="">اختر السنة</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">شهر الميلاد</label>
                            <select class="form-select" name="birth_month" data-patient-birth-month data-required="true">
                                <option value="">اختر الشهر</option>
                                @for ($month = 1; $month <= 12; $month++)
                                    <option value="{{ $month }}" @selected(old('birth_month') == $month)>{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4 d-none" data-patient-day-field>
                            <label class="form-label">يوم الميلاد</label>
                            <input class="form-control" type="number" name="birth_day" data-patient-birth-day min="1" max="31">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="date_type" data-patient-date-type value="{{ old('date_type') }}">
                @if($isApi)
                    <div class="col-12">
                        <button class="btn btn-outline-primary" type="button" data-yakeen-lookup="patient">
                            <i class="bi bi-search"></i> جلب بيانات المريض من يقين
                        </button>
                    </div>
                @endif
                <div class="col-12" data-yakeen-message="patient"></div>
            </div>
        </div>

        <div class="border-top mt-4 pt-4 d-none" data-step="patient-details">
            <h2 class="h6 mb-3">تفاصيل المريض</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم المريض بالعربية</label>
                    <input class="form-control" name="patient_name_ar" data-patient-name-ar value="{{ old('patient_name_ar') }}" {{ $isApi ? 'readonly' : '' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">اسم المريض بالإنجليزية</label>
                    <input class="form-control" dir="ltr" name="patient_name_en" data-patient-name-en value="{{ old('patient_name_en') }}" {{ $isApi ? 'readonly' : '' }}>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الجنس</label>
                    <select class="form-select" name="sex_code" data-patient-sex>
                        <option value="0">—</option>
                        <option value="1" @selected(old('sex_code') == 1)>ذكر</option>
                        <option value="2" @selected(old('sex_code') == 2)>أنثى</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">رقم الملف الطبي</label>
                    <input class="form-control" name="patient_file_number" maxlength="20" data-required="true" value="{{ old('patient_file_number') }}">
                </div>
            </div>
        </div>

        <div class="border-top mt-4 pt-4 d-none" data-step="contractor-identity">
            <h2 class="h6 mb-3">بيانات المتعهد</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نوع هوية المتعهد</label>
                    <select class="form-select" name="contractor_id_type" data-contractor-id-type data-required="true">
                        <option value="">اختر نوع الهوية</option>
                        @foreach($idTypes as $item)
                            <option value="{{ $item->id }}" @selected(old('contractor_id_type') == $item->id)>{{ $item->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="border-top mt-4 pt-4 d-none" data-step="contractor-identity-fields">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" data-contractor-id-label>رقم هوية المتعهد</label>
                    @include('legacy-workflows.medical-agreements._digit-input', [
                        'name' => 'contractor_idno',
                        'dataAttribute' => 'data-contractor-id',
                        'label' => 'رقم هوية المتعهد',
                        'maxLength' => 15,
                    ])
                </div>
                <div class="col-md-6">
                    <label class="form-label">جنسية المتعهد</label>
                    <select class="form-select" name="contractor_nationality" data-contractor-nationality data-required="true">
                        <option value="">اختر الجنسية</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->CODE }}" @selected(old('contractor_nationality') == $country->CODE)>{{ $country->DESCRIPTION }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12" data-contractor-date-fields>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" data-contractor-year-label>سنة ميلاد المتعهد</label>
                            <select class="form-select" name="contractor_birth_year" data-contractor-birth-year data-year-select data-initial-value="{{ old('contractor_birth_year') }}" data-required="true">
                                <option value="">اختر السنة</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">شهر ميلاد المتعهد</label>
                            <select class="form-select" name="contractor_birth_month" data-contractor-birth-month data-required="true">
                                <option value="">اختر الشهر</option>
                                @for ($month = 1; $month <= 12; $month++)
                                    <option value="{{ $month }}" @selected(old('contractor_birth_month') == $month)>{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                @if($isApi)
                    <div class="col-12">
                        <button class="btn btn-outline-primary" type="button" data-yakeen-lookup="contractor">
                            <i class="bi bi-search"></i> جلب بيانات المتعهد من يقين
                        </button>
                    </div>
                @endif
                <div class="col-12" data-yakeen-message="contractor"></div>
            </div>
        </div>

        <div class="border-top mt-4 pt-4 d-none" data-step="contractor-details">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم المتعهد بالعربية</label>
                    <input class="form-control" name="contractor_name_ar" data-contractor-name-ar value="{{ old('contractor_name_ar') }}" {{ $isApi ? 'readonly' : '' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">اسم المتعهد بالإنجليزية</label>
                    <input class="form-control" dir="ltr" name="contractor_name_en" data-contractor-name-en value="{{ old('contractor_name_en') }}" {{ $isApi ? 'readonly' : '' }}>
                </div>
            </div>
        </div>

        <div class="border-top mt-4 pt-4 d-none" data-step="common-details">
            <h2 class="h6 mb-3">بيانات التواصل</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">جوال المتعهد</label>
                    <input class="form-control" name="contractor_mobile" maxlength="16" data-required="true" value="{{ old('contractor_mobile') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input class="form-control" type="email" name="email" value="{{ old('email') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">صلة القرابة</label>
                    <select class="form-select" name="relative">
                        <option value="0">بالأصالة عن نفسه</option>
                        @foreach($relatives as $relative)
                            <option value="{{ $relative->id }}" @selected(old('relative') == $relative->id)>{{ $relative->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="d-none justify-content-end gap-2 mt-4" data-step="submit">
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">إلغاء</button>
            <button class="btn btn-primary" type="submit" data-submit-agreement>
                <i class="bi bi-check-lg"></i> إنشاء الاتفاقية{{ $isSadq || $isManual ? ' وإرسالها للتوقيع' : '' }}
            </button>
        </div>
    </div>
</form>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-medical-agreement-form]').forEach(function (form) {
                    const isYakeen = form.dataset.yakeen === 'true';
                    const lookupUrl = @json(route('modules.medical-agreements.yakeen.lookup', $variant));
                    const saudiCode = String(@json($saudiCode));
                    const flow = form.querySelector('[data-agreement-flow]');
                    const contractorType = form.querySelector('[data-contractor-type]');
                    const patientType = form.querySelector('[data-patient-id-type]');
                    const contractorIdType = form.querySelector('[data-contractor-id-type]');
                    const patientDayInput = form.querySelector('[data-patient-birth-day]');

                    function initDigitInput(group) {
                        const source = group.querySelector('.hm-digit-input__value');
                        const boxes = Array.from(group.querySelectorAll('[data-digit-box]'));
                        const maxLength = Number(group.dataset.digitMax || boxes.length);
                        if (!source || !boxes.length) return;

                        function syncBoxes() {
                            const value = Array.from(String(source.value || '')).slice(0, maxLength);
                            boxes.forEach(function (box, index) {
                                box.value = value[index] || '';
                            });
                        }

                        function syncSource() {
                            source.value = boxes.map(function (box) { return box.value; }).join('').slice(0, maxLength);
                            source.setCustomValidity('');
                        }

                        source.addEventListener('hm:digit-sync', syncBoxes);

                        boxes.forEach(function (box, index) {
                            box.addEventListener('input', function () {
                                const value = Array.from(this.value || '').filter(function (character) {
                                    return character.trim() !== '';
                                }).slice(-1)[0] || '';
                                this.value = value;
                                syncSource();
                                if (value && boxes[index + 1]) boxes[index + 1].focus();
                            });

                            box.addEventListener('keydown', function (event) {
                                if (event.key === 'Backspace' && !this.value && boxes[index - 1]) {
                                    boxes[index - 1].value = '';
                                    boxes[index - 1].focus();
                                    syncSource();
                                } else if (event.key === 'ArrowLeft' && boxes[index - 1]) {
                                    event.preventDefault();
                                    boxes[index - 1].focus();
                                } else if (event.key === 'ArrowRight' && boxes[index + 1]) {
                                    event.preventDefault();
                                    boxes[index + 1].focus();
                                }
                            });

                            box.addEventListener('paste', function (event) {
                                event.preventDefault();
                                const pasted = (event.clipboardData || window.clipboardData)?.getData('text') || '';
                                const characters = Array.from(pasted.trim()).slice(0, boxes.length - index);
                                if (!characters.length) return;
                                characters.forEach(function (character, offset) {
                                    boxes[index + offset].value = character;
                                });
                                syncSource();
                                boxes[Math.min(index + characters.length, boxes.length - 1)].focus();
                            });
                        });

                        syncBoxes();
                    }

                    form.querySelectorAll('[data-digit-input]:not([data-auto-digit-input])').forEach(initDigitInput);

                    function step(name) {
                        return form.querySelector('[data-step="' + name + '"]');
                    }

                    function setStep(name, visible) {
                        const node = step(name);
                        if (!node) return;
                        node.classList.toggle('d-none', !visible);
                        if (name === 'submit') node.classList.toggle('d-flex', visible);
                        node.querySelectorAll('input, select, textarea, button').forEach(function (control) {
                            control.disabled = !visible;
                            if (visible && control.dataset.required === 'true') {
                                if (control.classList.contains('hm-digit-input__value')) {
                                    control.dataset.digitRequired = 'true';
                                    control.required = false;
                                } else {
                                    control.required = true;
                                }
                            } else if (!visible) {
                                control.required = false;
                                if (control.classList.contains('hm-digit-input__value')) {
                                    control.dataset.digitRequired = 'false';
                                }
                            }
                        });
                    }

                    function setValue(selector, value) {
                        const control = form.querySelector(selector);
                        if (control && value !== undefined && value !== null && value !== '') {
                            control.value = value;
                            if (control.closest('[data-digit-input]')) {
                                control.dispatchEvent(new Event('hm:digit-sync'));
                            }
                        }
                    }

                    function setMessage(subject, message, success) {
                        const box = form.querySelector('[data-yakeen-message="' + subject + '"]');
                        if (!box) return;
                        box.innerHTML = '';
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-' + (success ? 'success' : 'danger') + ' py-2 small mb-0';
                        alert.textContent = message;
                        box.appendChild(alert);
                    }

                    function resetAfterPatient() {
                        setStep('patient-identity-fields', false);
                        setStep('patient-details', false);
                        setStep('contractor-identity', false);
                        setStep('contractor-identity-fields', false);
                        setStep('contractor-details', false);
                        setStep('common-details', false);
                        setStep('submit', false);
                    }

                    function resetAfterContractor() {
                        setStep('contractor-identity-fields', false);
                        setStep('contractor-details', false);
                        setStep('common-details', false);
                        setStep('submit', false);
                    }

                    function setIdentityMode(subject, idType) {
                        const patient = subject === 'patient';
                        const type = String(idType || '');
                        const fields = step(subject + '-identity-fields');
                        if (!fields || type === '') return;

                        const dateFields = fields.querySelector(patient ? '[data-patient-date-fields]' : '[data-contractor-date-fields]');
                        const nationalityField = fields.querySelector(patient ? '[data-patient-nationality-field]' : '[data-contractor-nationality]')?.closest('.col-md-6');
                        const year = fields.querySelector(patient ? '[data-patient-birth-year]' : '[data-contractor-birth-year]');
                        const yearLabel = fields.querySelector(patient ? '[data-patient-year-label]' : '[data-contractor-year-label]');
                        const idLabel = fields.querySelector(patient ? '[data-patient-id-label]' : '[data-contractor-id-label]');
                        const patientDayField = patient ? fields.querySelector('[data-patient-day-field]') : null;
                        const dateRequired = [1, 2, 5].includes(Number(type));
                        const hijri = Number(type) === 1;

                        function populateYearOptions(select, isHijri) {
                            if (!select || select.tagName !== 'SELECT') return;
                            const selectedValue = select.value || select.dataset.initialValue || '';
                            const minYear = isHijri ? 1300 : 1900;
                            const maxYear = isHijri ? 1600 : 2100;
                            select.innerHTML = '<option value="">اختر السنة</option>';
                            for (let yearValue = maxYear; yearValue >= minYear; yearValue--) {
                                const option = document.createElement('option');
                                option.value = String(yearValue);
                                option.textContent = String(yearValue);
                                select.appendChild(option);
                            }
                            if (selectedValue && Number(selectedValue) >= minYear && Number(selectedValue) <= maxYear) {
                                select.value = selectedValue;
                            }
                        }

                        if (idLabel) {
                            idLabel.textContent = patient
                                ? ({1: 'رقم الهوية الوطنية', 2: 'رقم الإقامة', 3: 'رقم الهوية الخليجية', 4: 'رقم الجواز', 5: 'رقم الحدود'}[type] || 'رقم الهوية')
                                : ({1: 'رقم الهوية الوطنية للمتعهد', 2: 'رقم إقامة المتعهد', 3: 'رقم الهوية الخليجية للمتعهد', 4: 'رقم جواز المتعهد', 5: 'رقم حدود المتعهد'}[type] || 'رقم هوية المتعهد');
                        }
                        if (dateFields) dateFields.classList.toggle('d-none', !dateRequired);
                        if (patientDayField) patientDayField.classList.toggle('d-none', isYakeen);
                        if (nationalityField) nationalityField.classList.toggle('d-none', false);
                        if ([3, 4].includes(Number(type))) {
                            const nationality = fields.querySelector(patient ? '[data-patient-nationality]' : '[data-contractor-nationality]');
                            if (nationality && nationality.value === saudiCode) nationality.value = '';
                        }
                        if (year) {
                            if (year.tagName === 'SELECT') {
                                populateYearOptions(year, hijri);
                            } else {
                                year.min = hijri ? '1300' : '1900';
                                year.max = hijri ? '1600' : '2100';
                            }
                        }
                        if (yearLabel) yearLabel.textContent = hijri ? 'سنة الميلاد (هجري)' : 'سنة الميلاد (ميلادي)';

                        const dateType = patient ? form.querySelector('[data-patient-date-type]') : null;
                        if (dateType) dateType.value = hijri ? '1' : '2';
                        fields.querySelectorAll('[data-required="true"]').forEach(function (control) {
                            if (control.name.includes('birth_year') || control.name.includes('birth_month')) {
                                control.dataset.required = dateRequired ? 'true' : 'false';
                                if (control.classList.contains('hm-digit-input__value')) {
                                    control.dataset.digitRequired = dateRequired ? 'true' : 'false';
                                    control.required = false;
                                } else {
                                    control.required = dateRequired;
                                }
                                control.disabled = !dateRequired;
                            }
                        });
                    }

                    function revealAfterPatient() {
                        setStep('patient-details', true);
                        if (String(contractorType?.value) === '1') {
                            setStep('contractor-identity', true);
                            setStep('common-details', false);
                        } else {
                            setStep('contractor-identity', false);
                            setStep('contractor-identity-fields', false);
                            setStep('contractor-details', false);
                            setStep('common-details', true);
                            setStep('submit', true);
                        }
                    }

                    function revealAfterContractor() {
                        setStep('contractor-details', true);
                        setStep('common-details', true);
                        setStep('submit', true);
                    }

                    function applyResult(subject, result) {
                        if (subject === 'patient') {
                            setValue('[data-patient-name-ar]', result.name_ar || '');
                            setValue('[data-patient-name-en]', result.name_en || '');
                            setValue('[data-patient-nationality]', result.nationality || '');
                            setValue('[data-patient-sex]', result.sex_code || '0');
                            setValue('[data-patient-id-type]', result.id_type || patientType?.value || '');
                            setValue('[data-patient-birth-year]', result.birth_year || '');
                            setValue('[data-patient-birth-month]', result.birth_month || '');
                            if (patientDayInput) patientDayInput.value = result.birth_day || '';
                            setValue('[data-patient-date-type]', result.date_type || '2');
                            revealAfterPatient();
                        } else {
                            setValue('[data-contractor-name-ar]', result.name_ar || '');
                            setValue('[data-contractor-name-en]', result.name_en || '');
                            setValue('[data-contractor-nationality]', result.nationality || '');
                            setValue('[data-contractor-id-type]', result.id_type || contractorIdType?.value || '');
                            revealAfterContractor();
                        }
                    }

                    async function lookup(subject) {
                        if (!isYakeen) return;
                        const patient = subject === 'patient';
                        const idType = form.querySelector(patient ? '[data-patient-id-type]' : '[data-contractor-id-type]')?.value || '';
                        const id = form.querySelector(patient ? '[data-patient-id]' : '[data-contractor-id]')?.value || '';
                        const nationality = form.querySelector(patient ? '[data-patient-nationality]' : '[data-contractor-nationality]')?.value || '';
                        const year = form.querySelector(patient ? '[data-patient-birth-year]' : '[data-contractor-birth-year]')?.value || '';
                        const month = form.querySelector(patient ? '[data-patient-birth-month]' : '[data-contractor-birth-month]')?.value || '';

                        if (!idType || !id) {
                            setMessage(subject, 'اختر نوع الهوية وأدخل رقم الهوية أولاً.', false);
                            return;
                        }
                        if ([1, 2, 5].includes(Number(idType)) && (!year || !month)) {
                            setMessage(subject, 'أدخل سنة وشهر الميلاد أولاً.', false);
                            return;
                        }
                        if ([3, 4].includes(Number(idType)) && !nationality) {
                            setMessage(subject, 'اختر الجنسية أولاً.', false);
                            return;
                        }

                        setMessage(subject, 'جاري الاتصال بخدمة يقين...', true);
                        const params = new URLSearchParams({subject, id_type: idType, id_number: id, nationality, birth_year: year, birth_month: month});
                        try {
                            const response = await fetch(lookupUrl + '?' + params.toString(), {
                                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
                            });
                            const json = await response.json();
                            if (!response.ok) throw new Error(json.message || 'تعذر الاستعلام من يقين.');
                            applyResult(subject, json.data || {});
                            setMessage(subject, json.message || 'تم جلب البيانات بنجاح.', true);
                        } catch (error) {
                            setMessage(subject, error.message || 'تعذر الاستعلام من يقين.', false);
                        }
                    }

                    function startLanguage() {
                        const selected = form.querySelector('[data-language-choice]:checked');
                        const hasLanguage = Boolean(selected);
                        flow?.classList.toggle('d-none', !hasLanguage);
                        if (hasLanguage) {
                            setStep('contractor-type', true);
                        } else {
                            ['contractor-type', 'patient-identity', 'patient-identity-fields', 'patient-details', 'contractor-identity', 'contractor-identity-fields', 'contractor-details', 'common-details', 'submit'].forEach(function (name) { setStep(name, false); });
                        }
                    }

                    form.querySelectorAll('[data-language-choice]').forEach(function (radio) {
                        radio.addEventListener('change', startLanguage);
                    });

                    contractorType?.addEventListener('change', function () {
                        resetAfterPatient();
                        if (!this.value) {
                            setStep('patient-identity', false);
                            return;
                        }
                        setStep('patient-identity', true);
                    });

                    patientType?.addEventListener('change', function () {
                        resetAfterPatient();
                        if (!this.value) return;
                        setStep('patient-identity-fields', true);
                        setIdentityMode('patient', this.value);
                        if (!isYakeen) revealAfterPatient();
                    });

                    contractorIdType?.addEventListener('change', function () {
                        resetAfterContractor();
                        if (!this.value) return;
                        setStep('contractor-identity-fields', true);
                        setIdentityMode('contractor', this.value);
                        if (!isYakeen) revealAfterContractor();
                    });

                    form.querySelector('[data-yakeen-lookup="patient"]')?.addEventListener('click', function () { lookup('patient'); });
                    form.querySelector('[data-yakeen-lookup="contractor"]')?.addEventListener('click', function () { lookup('contractor'); });
                    form.addEventListener('submit', function (event) {
                        let invalidBox = null;
                        form.querySelectorAll('[data-digit-input]').forEach(function (group) {
                            const source = group.querySelector('.hm-digit-input__value');
                            const firstBox = group.querySelector('[data-digit-box]');
                            const isRequired = source && (source.required || source.dataset.digitRequired === 'true');
                            if (source && firstBox && !source.disabled && isRequired && !source.value) {
                                source.setCustomValidity('أدخل رقم الهوية.');
                                invalidBox = invalidBox || firstBox;
                            }
                        });
                        if (invalidBox) {
                            event.preventDefault();
                            invalidBox.focus();
                            return;
                        }

                        const submitButton = form.querySelector('[data-submit-agreement]');
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري إنشاء الاتفاقية وإرسالها...';
                        }
                    });

                    startLanguage();
                    if (patientType?.value) {
                        setStep('patient-identity', true);
                        setStep('patient-identity-fields', true);
                        setIdentityMode('patient', patientType.value);
                        if (!isYakeen) revealAfterPatient();
                    }
                    if (contractorType?.value === '1') setStep('contractor-identity', true);
                });
            });
        </script>
    @endpush
@endonce
