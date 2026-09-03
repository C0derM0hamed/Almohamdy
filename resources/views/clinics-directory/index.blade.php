@extends('layouts.app')

@section('title', 'عرض العيادات والأطباء')
@section('figma_page_header', 'true')

@push('workflow_styles')
    <link href="{{ asset('css/hm-figma-workflows.css') }}?v={{ filemtime(public_path('css/hm-figma-workflows.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/hm-clinics-directory.css') }}?v={{ filemtime(public_path('css/hm-clinics-directory.css')) }}" rel="stylesheet">
@endpush

@section('content')
    <div class="hm-fm hm-cd" dir="rtl">
        @include('layouts.partials.figma-module-header', [
            'crumbs' => [
                ['label' => 'الإجراء الطبي'],
                ['label' => 'عرض العيادات'],
            ],
            'title' => 'عرض العيادات والأطباء',
            'subtitle' => 'دليل الأطباء والعيادات والفروع المتاحة داخل النظام',
            'heroIconSrc' => asset('images/figma/workflows/references.svg'),
            'heroIconSize' => 32,
        ])

        @if (session('success'))
            <div class="alert alert-success cd-alert mt-3" role="status">{{ session('success') }}</div>
        @endif

        <section class="cd-search-panel" aria-labelledby="clinicDirectorySearchTitle">
            <div class="cd-section-heading">
                <span class="cd-section-heading__icon" aria-hidden="true"><i class="bi bi-funnel"></i></span>
                <div>
                    <h2 id="clinicDirectorySearchTitle">البحث والفلترة</h2>
                    <p>ابحث عن الطبيب حسب العيادة أو الفرع أو بيانات الطبيب</p>
                </div>
            </div>

            <form method="get" class="cd-filter-form">
                <div class="cd-filter-field">
                    <label for="clinicDirectoryClinic">العيادة</label>
                    <select id="clinicDirectoryClinic" name="clinic_id" class="form-select">
                        <option value="">كل العيادات</option>
                        @foreach ($specializedClinics as $clinic)
                            <option value="{{ $clinic->id }}" @selected($filters['clinic_id'] == $clinic->id)>{{ $clinic->subject_ar }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cd-filter-field">
                    <label for="clinicDirectoryHospital">الفرع</label>
                    <select id="clinicDirectoryHospital" name="hospital_id" class="form-select">
                        <option value="">كل الفروع</option>
                        @foreach ($hospitals as $hospital)
                            <option value="{{ $hospital->id }}" @selected($filters['hospital_id'] == $hospital->id)>{{ \App\Support\LocaleText::localizedValue($hospital->name_ar ?? null, $hospital->name_en ?? null) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cd-filter-field">
                    <label for="clinicDirectoryName">اسم الطبيب</label>
                    <input id="clinicDirectoryName" class="form-control" name="name" value="{{ $filters['name'] }}" placeholder="اكتب اسم الطبيب">
                </div>

                <div class="cd-filter-field">
                    <label for="clinicDirectoryCode">كود الطبيب</label>
                    <input id="clinicDirectoryCode" class="form-control" name="code" value="{{ $filters['code'] }}" placeholder="اكتب الكود">
                </div>

                <label class="cd-check-field" for="clinicDirectorySearchAll">
                    <input id="clinicDirectorySearchAll" type="checkbox" name="search_all" value="1" @checked($filters['search_all'])>
                    <span class="cd-check-field__box" aria-hidden="true"><i class="bi bi-check"></i></span>
                    <span>البحث في كل الأطباء</span>
                </label>

                <div class="cd-filter-actions">
                    <button class="cd-btn cd-btn--primary" type="submit"><i class="bi bi-search"></i> بحث</button>
                    <a class="cd-btn cd-btn--secondary" href="{{ route('modules.clinics-directory.index') }}">استعادة</a>
                </div>
            </form>
        </section>

        <section class="cd-results-panel" aria-labelledby="clinicDirectoryDoctorsTitle">
            <header class="cd-results-header">
                <div class="cd-results-heading">
                    <span class="cd-results-heading__icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
                    <div>
                        <h2 id="clinicDirectoryDoctorsTitle">الأطباء</h2>
                        <p>بيانات الأطباء حسب نطاق البحث المحدد</p>
                    </div>
                    <span class="cd-results-count">{{ $doctors->total() }}</span>
                </div>

                <div class="cd-results-tools" aria-label="أدوات القائمة">
                    <button type="button" data-cd-refresh title="تحديث القائمة"><i class="bi bi-arrow-clockwise"></i></button>
                </div>
            </header>

            @if ($doctors->count() > 0)
                <div class="cd-doctors-grid">
                    @foreach ($doctors as $doctor)
                        @php
                            $doctorName = \App\Support\LocaleText::localizedValue($doctor->name_ar ?? null, $doctor->name_en ?? null) ?: (app()->getLocale() === 'ar' ? 'طبيب' : 'Doctor');
                            $nameParts = preg_split('/\s+/u', $doctorName, -1, PREG_SPLIT_NO_EMPTY);
                            $initials = collect($nameParts ?: ['ط'])->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
                            $qualification = trim(strip_tags((string) ($doctor->holds_ar ?? '')));
                            $cases = trim(strip_tags((string) ($doctor->cases_ar ?? '')));
                            $clinicLabel = trim((\App\Support\LocaleText::localizedValue($doctor->clinic_name_ar ?? null, $doctor->clinic_name_en ?? null) ?: '—') . ($doctor->clinic_number ? ' — ' . $doctor->clinic_number : ''));
                            $contactLabel = trim(($doctor->mobile ?: '—') . ($doctor->ext_number ? ' — ' . $doctor->ext_number : ''));
                            $fee = $doctor->hospital_price ?? $doctor->price ?? null;
                            $canDelete = (int) session('hr_user_level', 0) === 3 || in_array((int) session('hr_branch_id', 0), [5, 6, 15], true);
                        @endphp

                        <article class="cd-doctor-card{{ ! $doctor->publish ? ' cd-doctor-card--inactive' : '' }}">
                            <div class="cd-doctor-card__topline"></div>
                            <header class="cd-doctor-card__header">
                                <div class="cd-doctor-card__identity">
                                    <div class="cd-doctor-avatar" aria-hidden="true">{{ $initials }}</div>
                                    <div class="cd-doctor-card__name-wrap">
                                        <span class="cd-doctor-card__eyebrow">طبيب عيادات</span>
                                        <h3>{{ $doctorName }}</h3>
                                        <p>{{ $doctor->specialization_ar ?: 'التخصص غير محدد' }}</p>
                                    </div>
                                </div>

                                <div class="cd-doctor-card__actions">
                                    <span class="cd-status-badge {{ $doctor->publish ? 'cd-status-badge--active' : 'cd-status-badge--inactive' }}">
                                        <i class="bi bi-circle-fill"></i>
                                        {{ $doctor->publish ? 'منشور' : 'غير منشور' }}
                                    </span>
                                    <div class="dropdown">
                                        <button class="cd-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="إجراءات الطبيب">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-start cd-action-menu">
                                            <li><a class="dropdown-item" href="{{ route('modules.doctors-admin.doctors.edit', $doctor->id) }}"><i class="bi bi-pencil-square"></i> تحرير بيانات الطبيب</a></li>
                                            <li>
                                                <form method="post" action="{{ route('modules.clinics-directory.toggle', $doctor->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="dropdown-item" type="submit"><i class="bi bi-eye{{ $doctor->publish ? '-slash' : '' }}"></i> {{ $doctor->publish ? 'إيقاف النشر' : 'تفعيل النشر' }}</button>
                                                </form>
                                            </li>
                                            @if ($canDelete)
                                                <li>
                                                    <form method="post" action="{{ route('modules.clinics-directory.destroy', $doctor->id) }}" onsubmit="return confirm('هل تريد حذف الطبيب؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-trash3"></i> حذف الطبيب</button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </header>

                            <div class="cd-doctor-card__meta">
                                <span><i class="bi bi-person-badge"></i><b>الكود</b> {{ $doctor->code ?: '—' }}</span>
                                <span><i class="bi bi-building"></i><b>{{ app()->getLocale() === 'ar' ? 'المستشفى' : 'Hospital' }}</b> {{ \App\Support\LocaleText::localizedValue($doctor->hospital_name_ar ?? null, $doctor->hospital_name_en ?? null) ?: '—' }}</span>
                            </div>

                            <div class="cd-doctor-card__body">
                                <div class="cd-info-grid">
                                    <div class="cd-info-item">
                                        <span class="cd-info-item__icon"><i class="bi bi-award"></i></span>
                                        <div><small>حاصل على</small><strong>{{ $qualification ?: '—' }}</strong></div>
                                    </div>
                                    <div class="cd-info-item">
                                        <span class="cd-info-item__icon"><i class="bi bi-geo-alt"></i></span>
                                        <div><small>مبنى العيادة / رقمها</small><strong>{{ $clinicLabel }}</strong></div>
                                    </div>
                                    <div class="cd-info-item">
                                        <span class="cd-info-item__icon"><i class="bi bi-telephone"></i></span>
                                        <div><small>الجوال / التحويلة</small><strong dir="ltr">{{ $contactLabel }}</strong></div>
                                    </div>
                                    <div class="cd-info-item">
                                        <span class="cd-info-item__icon"><i class="bi bi-cash-stack"></i></span>
                                        <div><small>سعر الكشفية</small><strong>{{ $fee !== null && $fee !== '' ? number_format((float) $fee, 2) : '—' }}</strong></div>
                                    </div>
                                </div>

                                <div class="cd-description-block">
                                    <div class="cd-description-block__heading"><i class="bi bi-heart-pulse"></i> الحالات التي يراها</div>
                                    <p>{{ $cases ?: 'لا توجد حالات مسجلة' }}</p>
                                </div>
                            </div>

                            <footer class="cd-doctor-card__footer">
                                <a class="cd-profile-btn" href="{{ route('modules.doctors-admin.doctors.edit', $doctor->id) }}"><i class="bi bi-pencil-square"></i> تحرير بيانات الطبيب</a>
                                <span class="cd-record-label"><i class="bi bi-shield-check"></i> بيانات موثقة</span>
                            </footer>
                        </article>
                    @endforeach
                </div>

                <div class="cd-pagination">{{ $doctors->links('pagination.hm') }}</div>
            @else
                <div class="cd-empty-state">
                    <span><i class="bi bi-person-x"></i></span>
                    <h3>لا يوجد أطباء مطابقون للبحث</h3>
                    <p>جرّب تغيير الفلاتر أو استعادة القائمة الكاملة.</p>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function (event) {
            const refresh = event.target.closest('[data-cd-refresh]');
            if (refresh) window.location.reload();
        });
    </script>
@endpush
